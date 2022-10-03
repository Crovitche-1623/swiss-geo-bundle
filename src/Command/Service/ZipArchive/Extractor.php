<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\ZipArchive;

use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Exception\ClosingException;
use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Exception\CopyingFileException;
use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Exception\OpeningException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author  Thibault Gattolliat
 */
class Extractor
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    )
    {}

    /**
     * @param  string  $from  The zip archive path. It must be located locally.
     *                        Example: `./my_zip_archive.zip`
     * @param  callable  $function  Execute the callable and deletes the files
     *                              used afterwards. So do not touch the files
     *                              in this function.
     * @param  string|\Stringable|SysGetTempDir  $to
     *                                  The absolute folder path where the
     *                                  content of the zip archive will be
     *                                  extracted. Do not add a trailing slash !
     *                                  The folder WILL BE CREATED if it does
     *                                  not exist.
     *
     * Note: The SysGetTempDir union type is probably unnecessary, but it's here
     *       to make PhpStorm happy.
     */
    public static function extract(
        string $from,
        callable $function,
        string|\Stringable|SysGetTempDir $to = new SysGetTempDir()
    ): void
    {
        if (false === ($zipArchive = new \ZipArchive())->open($from)) {
            throw new OpeningException($from);
        }

        $filesToDelete = [];

        $filesystem = new Filesystem();

        for ($i = 0; $i < $zipArchive->numFiles; $i++) {
            $filename = $zipArchive->getNameIndex($i);

            $filesystem->mkdir($to);

            $copyTo = sprintf(
                "$to/%s",
                // https://en.wikipedia.org/wiki/Basename
                pathinfo($filename, PATHINFO_BASENAME)
            );

            // Maybe submit a PR to include this functionality in
            // `symfony/filesystem` ?
            if (!copy($copyFrom = "zip://$from#$filename", $copyTo)) {
                throw new CopyingFileException($copyFrom, $copyTo);
            }

            $filesToDelete[] = $copyTo;
        }

        $function();

        $filesToDelete[] = $from;

        $filesystem->remove($filesToDelete);

        if (!$zipArchive->close()) {
            throw new ClosingException($from);
        }
    }


    /**
     * Extract Zip archive from the Web.
     *
     * @param  string  $url  Where the zip archive is. Example:
     *                       `https://example.com/backups/archive.zip`
     * @param  callable  $function  see Extractor::extract()
     * @param  string|\Stringable|SysGetTempDir  $to  see Extractor::extract()
     *
     * @param  string|\Stringable|SysGetTempDir  $downloadTo
     *                                          Where the zip archive will be
     *                                          copied during the process.
     *                                          Do not add a trailing slash !
     *
     * @throws TransportExceptionInterface
     *
     * Note: The SysGetTempDir union type is probably unnecessary, but it's here
     *       to make PhpStorm happy.
     */
    public function extractFromWeb(
        string $url,
        callable $function,
        string|\Stringable|SysGetTempDir $to = new SysGetTempDir(),
        string|\Stringable|SysGetTempDir $downloadTo = new SysGetTempDir()
    ): void
    {
        $response = $this->httpClient->request('GET', $url);

        $statusCode = $response->getStatusCode();

        if (200 !== $statusCode) {
            throw new CopyingFileException($url, $to, $statusCode);
        }

        $from = sprintf(
            "$downloadTo/%s.zip",
            uniqid(gethostname(), more_entropy: true)
        );

        if (false === $fileHandler = fopen($from, 'wb')) {
            throw new OpeningException($from);
        }

        // get the response content in chunks and save them in a file
        // response chunks implement Symfony\Contracts\HttpClient\ChunkInterface
        foreach ($this->httpClient->stream($response) as $chunk) {
            $chunkContent = $chunk->getContent();

            fwrite($fileHandler, $chunkContent);
        }

        if (!fclose($fileHandler)) {
            throw new ClosingException($from);
        }

        $this->logger->debug($from. ' has been copied.');

        self::extract($from, $function, $to);
    }
}
