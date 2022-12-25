<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Import;

use Crovitche\SwissGeoBundle\Command\Service\Cache\GetTimestampFromCacheOrFolderService;
use Crovitche\SwissGeoBundle\Command\Service\Cache\WriteCacheWithTimestampService;
use Crovitche\SwissGeoBundle\Command\Service\ExtractZipFromServerService;
use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Extractor;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use League\Csv\Reader;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'swiss-geo-bundle:import:streets',
    description: 'Import the swiss street from the swisstopo'
)]
class ImportStreetsCommand extends Command
{
    use LockableTrait;

    private const STREETS_CACHE_NAME = 'cache___streets___timestamp';

    private OutputStyle $io;

    public function __construct(
        private readonly Extractor $extractor,
        private readonly GetTimestampFromCacheOrFolderService $timestampService,
        private readonly WriteCacheWithTimestampService $writeCacheWithTimestamp,
        private readonly Connection $connection,
        private readonly ExtractZipFromServerService $extractZipFromServer,
        private readonly LoggerInterface $logger,
        private readonly string $streetsUrl
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

        $this->extractor->extractFromWeb(
            $this->streetsUrl,
            function (): void {
                $timestamp = ($this->timestampService)($this->io, self::STREETS_CACHE_NAME, '/var/lib/mysql-files');
                $this->createCsvForBulkInsert('/var/lib/mysql-files');
                $this->insertDataFromCsvFile();
                ($this->writeCacheWithTimestamp)(self::STREETS_CACHE_NAME, $timestamp);

            },
            "/var/lib/mysql-files"
        );

        return Command::SUCCESS;
    }

    private function createCsvForBulkInsert(string $folder): void
    {
        $csvReader =
            Reader::createFromPath($folder . '/pure_str.csv')
            ->setDelimiter(';')
            ->setHeaderOffset(0)
        ;

        $records = $csvReader->getRecords();
        $recordsCount = count($csvReader);

        $streetLocalityWriter = Writer::createFromPath(
                path: '/var/lib/mysql-files/streets_localities.csv',
                open_mode: 'w'
            )
            ->setDelimiter(',')
        ;
        $streetLocalityWriter->insertOne(['id_street', 'id_locality']);

        $progressBar = $this->io->createProgressBar($recordsCount);
        unset($recordsCount);

        $this->connection->getConfiguration()->setMiddlewares([]);

        $sqlStatement = $this->connection->prepare("
            SELECT
                l0.id
            FROM
                Locality l0
            WHERE
                :ZIP_LABEL = l0.postal_code_and_label
        ");

        foreach ($records as $record) {
            foreach (explode(', ', $record['ZIP_LABEL']) as $locality) {
                $localityId = $sqlStatement
                    ->executeQuery(['ZIP_LABEL' => $locality])
                    ->fetchOne();

                if (!$localityId) {
                    $error = "The locality id cannot be found for label " .
                        "`$locality`... Did you run the ".
                        "`pesetas:import:localities` command before ?";

                    $this->io->error($error);

                    throw new \RuntimeException($error);
                }

                $streetLocalityWriter->insertOne([
                    $record['STR_ESID'],
                    $localityId
                ]);
            }
            $progressBar->advance();

            // Uncomment this line if you have exhausted memory issue
            // gc_collect_cycles();
        }
        unset($sqlStatement);

        $progressBar->finish();
    }

    private function insertDataFromCsvFile(): bool
    {
        $this->io->info('Inserting the data...');

        try {
            $this->connection->executeQuery(/** @lang  MySQL */"
                CREATE TEMPORARY TABLE t___tmp___Street_to_be_inserted (
                    esid INT(11) PRIMARY KEY NOT NULL,
                    label VARCHAR(150) NOT NULL,
                    type VARCHAR(6),
                    completion_status VARCHAR(8) NOT NULL,
                    is_official TINYINT(1) NOT NULL,
                    is_valid TINYINT(1) NOT NULL,
                    last_modification_date DATE
                );
            ");

            $this->connection->executeQuery(/** @lang  MySQL */"
                LOAD DATA LOCAL INFILE '/var/lib/mysql-files/pure_str.csv'
                INTO TABLE t___tmp___Street_to_be_inserted
                CHARACTER SET utf8
                FIELDS TERMINATED BY ';'
                ENCLOSED BY ''
                LINES TERMINATED BY '\n'
                IGNORE 1 LINES
                (@STR_ESID, @STN_LABEL, @ZIP_LABEL, @COM_FOSNR, @COM_NAME, @COM_CANTON, @STR_TYPE, @STR_STATUS, @STR_OFFICIAL, @STR_VALID, @STR_MODIFIED, @STR_EASTING, @STR_NORTHING)
                SET
                    esid = @STR_ESID,
                    label = @STN_LABEL,
                    type = NULLIF(@STR_TYPE, ''),
                    completion_status = NULLIF(@STR_STATUS, ''),
                    is_official = IF(@STR_OFFICIAL = 'true', 1, 0),
                    is_valid = IF(@ADR_VALID = 'true', 1, 0),
                    last_modification_date = IFNULL(NULLIF(@STR_MODIFIED, ''), STR_TO_DATE(@STR_MODIFIED, '%d.%m.%Y'))
                ;
             ");

            // Supprime les données qui n'existent pas dans la table d'insert
            $this->connection->executeQuery(/** @lang  MySQL */"
                DELETE s0 FROM Street s0
                    LEFT JOIN t___tmp___Street_to_be_inserted s1 ON s0.esid = s1.esid
                WHERE
                    s1.esid IS NULL;
            ");

            // Insert les données à partir de la table d'insert si elles n'existent pas dans la table principale.
            // Les données sont remplacés si la date de modification (STR_MODIFIED) est plus récente
            $this->connection->executeQuery(/** @lang  MySQL */"
                INSERT INTO Street (esid, label, type, completion_status, is_official, is_valid, last_modification_date)
                SELECT
                    p0.esid,
                    p0.label,
                    p0.type,
                    p0.completion_status,
                    p0.is_official,
                    p0.is_valid,   
                    p0.last_modification_date
                FROM
                    t___tmp___Street_to_be_inserted p0
                    LEFT JOIN Street p1 ON p0.esid = p1.esid
                WHERE
                    p1.esid IS NULL OR p0.last_modification_date > p1.last_modification_date
                ON DUPLICATE KEY UPDATE 
                    label = VALUES(label),
                    type = VALUES(type),
                    completion_status = VALUES(completion_status),
                    is_official = VALUES(is_official),
                    is_valid = VALUES(is_valid),
                    last_modification_date = VALUES(last_modification_date);
            ");

            $this->connection->executeQuery("
                DROP TEMPORARY TABLE IF EXISTS t___tmp___Street_to_be_inserted;
            ");

            /*
            $this->connection->executeQuery("
                CREATE TEMPORARY TABLE t___tmp___Street__Locality_to_be_inserted (
                    esid INT(11) UNSIGNED NOT NULL,
                    id_locality INT(11) UNSIGNED
                );
            ");

            $this->logger->info('load data...');

            $this->connection->executeQuery(/** @lang MySQL  "
                LOAD DATA LOCAL INFILE '/var/lib/mysql-files/pure_str.csv'
                INTO TABLE t___tmp___Street__Locality_to_be_inserted
                CHARACTER SET utf8
                FIELDS TERMINATED BY ';'
                ENCLOSED BY ''
                LINES TERMINATED BY '\n'
                IGNORE 1 LINES
                (@STR_ESID, @STN_LABEL, @ZIP_LABEL, @COM_FOSNR, @COM_NAME, @COM_CANTON, @STR_TYPE, @STR_STATUS, @STR_OFFICIAL, @STR_VALID, @STR_MODIFIED, @STR_EASTING, @STR_NORTHING)
                SET
                    esid = @STR_ESID,
                    id_locality = (SELECT l0.id FROM Locality l0 WHERE l0.postal_code_and_label = @ZIP_LABEL)
                ;
            ");

            $this->logger->info('fini...');

            // Supprime les données qui n'existent pas dans la table d'insert
            $this->connection->executeQuery("
                DELETE sl0 FROM Street__Locality sl0
                    LEFT JOIN t___tmp___Street__Locality_to_be_inserted sl1 ON (sl0.id_street = sl1.esid AND sl0.id_locality = sl1.id_locality)
                WHERE
                    sl1.esid IS NULL AND sl1.id_locality IS NULL;
            ");

            $this->connection->executeQuery("
                INSERT INTO Street__Locality (id_street, id_locality)
                SELECT
                    sl0.esid,
                    sl0.id_locality
                FROM
                    t___tmp___Street__Locality_to_be_inserted sl0
                    LEFT JOIN Street__Locality sl1 ON (sl0.esid = sl1.id_street AND sl0.id_locality = sl1.id_locality)
                WHERE
                    sl0.id IS NULL
                ON DUPLICATE KEY UPDATE
                    id_locality = VALUES(id_locality),
                    id_street = VALUES(sl1.id_street);
            ");

            $this->connection->executeQuery("
                DROP TEMPORARY TABLE t___tmp___Street__Locality_to_be_inserted;
            ");
            */

            $this->connection->executeQuery(/** @lang  MySQL */"
                 LOAD DATA LOCAL INFILE '/var/lib/mysql-files/streets_localities.csv'
                 IGNORE INTO TABLE Street__Locality
                 CHARACTER SET utf8
                 FIELDS TERMINATED BY ','
                 ENCLOSED BY '\"'
                 LINES TERMINATED BY '\n'
                 IGNORE 1 LINES
                 (id_street, id_locality);
             ");

        } catch (Exception $e) {
            $this->io->error('An error occurred when inserting the data...' . $e->getMessage());

            return false;
        }

        $this->io->success('Data has been inserted !');

        return true;
    }
}
