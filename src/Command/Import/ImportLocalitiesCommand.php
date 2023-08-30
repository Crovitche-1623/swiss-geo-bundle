<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Import;

use Crovitche\SwissGeoBundle\Command\Service\ExtractZipFromServerService;
use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Extractor;
use Doctrine\DBAL\{Connection, Exception as DBALException};
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\{Command, LockableTrait};
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\{OutputStyle, SymfonyStyle};
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: self::COMMAND_NAME,
    description: 'Import the swiss localities from cadastre.ch'
)]
class ImportLocalitiesCommand extends Command
{
    use LockableTrait;

    public const COMMAND_NAME = 'swiss-geo-bundle:import:localities';

    private OutputStyle $io;

    public function __construct(
        private readonly Extractor $extractor,
        private readonly Connection $connection,
        private readonly ExtractZipFromServerService $extractZipFromServer,
        private readonly LoggerInterface $logger,
        private readonly string $localitiesUrl
    ) {
        parent::__construct();
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('This command is already running in another process.');

            return Command::SUCCESS;
        }

        $this->io = new SymfonyStyle($input, $output);

        $this->io->note('If this command produce foreign key constraint error, it probably means that you already ran it once.');

        $this->extractor->extractFromWeb(
            $this->localitiesUrl,
            function (): void {
                $this->insertDataFromCsvFile();
            },
            '/var/lib/mysql-files'
        );

        return Command::SUCCESS;
    }

    private function insertDataFromCsvFile(): bool
    {
        $this->io->info('Inserting the data...');

        try {
            $this->connection->executeQuery(/* @lang MySQL */ "
                LOAD DATA LOCAL INFILE '/var/lib/mysql-files/PLZO_CSV_LV95.csv'
                INTO TABLE Locality
                CHARACTER SET utf8
                FIELDS TERMINATED BY ';'
                ENCLOSED BY ''
                LINES TERMINATED BY '\n'
                IGNORE 1 LINES
                (@ORTSCHAFTSNAME, @PLZ, @ZUSATZZIFFER, @GEMEINDENAME, @BFS_NR, @KANTONSKURZEL, @E, @N, @SPRACHE)
                SET
                    label = TRIM(@ORTSCHAFTSNAME),
                    postal_code = @PLZ,
                    region_abbreviation = TRIM(@KANTONSKURZEL),
                    additional_digits = @ZUSATZZIFFER
                ;
            ");
        } catch (DBALException $e) {
            $this->io->error('An error occurred when inserting the data...'.$e->getMessage());

            return false;
        }

        $this->io->success('Data has been inserted !');

        return true;
    }
}
