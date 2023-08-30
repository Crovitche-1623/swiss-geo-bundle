<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service;

use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Extractor;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExtractZipFromServerService
{
    private string $zipDirectory;

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
        $this->zipDirectory = \sys_get_temp_dir().'/';
    }

    /**
     * @param  OutputStyle  $io  The parameter used to write things in the
     *                           console
     *
     * @return  bool  whether the process worked or not
     */
    public function __invoke(
        OutputStyle $io,
        string $url,
        string $fileName
    ): bool {
        if (!$this->copyZipFromServer($io, $url, $fileName)) {
            return false;
        }

        Extractor::extract($this->zipDirectory.$fileName);

        return true;
    }

    private function copyZipFromServer(
        OutputStyle $io,
        string $url,
        string $fileName
    ): bool {
        $io->info(\sprintf('Sending a request to %s...', $url.$fileName));

        try {
            $response = $this->httpClient->request(
                method: 'GET',
                url: $url.$fileName
            );

            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface) {
            $io->error(
                'A network error occurred or an unsupported configuration has
                been passed to the HTTP client'
            );

            return false;
        }

        if (Response::HTTP_OK !== $statusCode) {
            $io->error(
                'The server responded with a different status code than
                200'
            );

            return false;
        }

        // //////////////////////////////////////////////////////////////////////
        $io->info(
            message: \sprintf(
                'Trying to copy the received zip archive %s to %s...',
                $fileName, $this->zipDirectory
            )
        );
        // //////////////////////////////////////////////////////////////////////

        if (false === $fileHandler = \fopen($this->zipDirectory.$fileName, 'w')) {
            $io->error('Unable to create the zip file...');

            return false;
        }

        // get the response content in chunks and save them in a file
        // response chunks implement Symfony\Contracts\HttpClient\ChunkInterface
        foreach ($this->httpClient->stream($response) as $chunk) {
            try {
                $chunkContent = $chunk->getContent();
            } catch (TransportExceptionInterface) {
                $io->error(
                    'A network error occurred or the idle timeout has
                    been reached'
                );

                return false;
            }

            \fwrite($fileHandler, $chunkContent);
        }

        $io->success(\sprintf('Zip archive %s has been copied...', $fileName));

        return true;
    }

    private function extractZipAndDeleteIt(
        OutputStyle $io,
        string $file
    ): bool {
        // //////////////////////////////////////////////////////////////////////
        $io->info(\sprintf('Trying to open the Zip archive %s...', $file));
        // //////////////////////////////////////////////////////////////////////

        $zipArchive = new \ZipArchive();
        if (true !== $zipArchive->open($this->zipDirectory.$file)) {
            $io->error(
                \sprintf('The zip file %s cannot be opened... Maybe a permission
                issue ?', $file)
            );

            return false;
        }

        // //////////////////////////////////////////////////////////////////////
        $io->info('Trying to extract the content...');
        // //////////////////////////////////////////////////////////////////////

        $location = $this->zipDirectory.\pathinfo($file, \PATHINFO_FILENAME).'/';

        if ($zipArchive->extractTo($location) &&
            $zipArchive->close()) {
            $io->success('Content has been extracted...');
        } else {
            $io->error('Unable to extract the zip archive...');

            return false;
        }

        // //////////////////////////////////////////////////////////////////////
        $io->info('Trying to delete the zip archive...');
        // //////////////////////////////////////////////////////////////////////

        if (\unlink($this->zipDirectory.$file)) {
            $io->success('The zip archive has been deleted');
        } else {
            $io->error('Unable to delete the Zip Archive previously downloaded...');

            return false;
        }

        return true;
    }
}
