<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Import;

use Crovitche\SwissGeoBundle\Command\Import\Exception\InternalImportingException;
use Crovitche\SwissGeoBundle\Command\Service\Cache\{GetTimestampFromCacheOrFolderService, WriteCacheWithTimestampService};
use Crovitche\SwissGeoBundle\Command\Service\ExtractZipFromServerService;
use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Extractor;
use Doctrine\DBAL\{Connection, Exception};
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\{Command, LockableTrait};
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\{OutputStyle, SymfonyStyle};
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: self::COMMAND_NAME,
    description: 'Import the swiss building addresses from cadastre.ch'
)]
class ImportBuildingAddressesCommand extends Command
{
    use LockableTrait;

    public const COMMAND_NAME = 'swiss-geo-bundle:import:building-addresses';
    public const ADDRESSES_CACHE_NAME = 'cache___building_addresses___timestamp';

    private OutputStyle $io;
    private bool $cacheHasBeenCreated = false;

    public function __construct(
        private readonly Extractor $extractor,
        private readonly Connection $connection,
        private readonly ExtractZipFromServerService $extractZipFromServer,
        private readonly LoggerInterface $logger,
        private readonly GetTimestampFromCacheOrFolderService $timestampService,
        private readonly WriteCacheWithTimestampService $writeCacheWithTimestamp,
        private readonly string $buildingAddressesUrl
    ) {
        parent::__construct();
    }

    /**
     * @throws  TransportExceptionInterface
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln(
                \sprintf(
                    'The command %s is already running in another process.',
                    self::COMMAND_NAME
                )
            );

            return Command::FAILURE;
        }

        $this->io = new SymfonyStyle($input, $output);

        $this->extractor->extractFromWeb(
            $this->buildingAddressesUrl,
            function (): void {
                $timestamp = ($this->timestampService)($this->io, self::ADDRESSES_CACHE_NAME, '/var/lib/mysql-files');
                $this->insertDataFromCsvFile();
                ($this->writeCacheWithTimestamp)(self::ADDRESSES_CACHE_NAME, $timestamp);
            },
            '/var/lib/mysql-files'
        );

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function insertDataFromCsvFile(): void
    {
        $this->io->title('Importing the building addresses...');

        $this->connection->beginTransaction();
        try {
            $this->io->info('Creating temporary table...');

            $this->connection->executeQuery(/* @lang  MySQL */ '
                CREATE TEMPORARY TABLE IF NOT EXISTS t___tmp___Building_address_to_be_inserted (
                    egaid INT(11) UNSIGNED PRIMARY KEY NOT NULL,
                    id_street INT(10) UNSIGNED NOT NULL,
                    postal_code_and_label VARCHAR(110) NOT NULL,
                    building_id VARCHAR(10) NOT NULL,
                    entrance_number SMALLINT(6) NOT NULL,
                    address_number VARCHAR(12) DEFAULT NULL,
                    building_name VARCHAR(50) DEFAULT NULL,
                    building_category VARCHAR(18) NOT NULL,
                    completion_status VARCHAR(8) NOT NULL,
                    is_official TINYINT(1) NOT NULL,
                    lv95_northing INT(11) DEFAULT NULL,
                    lv95_easting INT(11) DEFAULT NULL,
                    last_modification_date DATE NOT NULL
                );');
            $this->io->success('Done !');

            $this->io->info('Loading data from CSV file in it (This may take a while)...');
            $this->connection->executeQuery(/* @lang  MySQL */ "
                LOAD DATA
                    LOCAL
                    INFILE '/var/lib/mysql-files/amtliches-gebaeudeadressverzeichnis_ch_2056.csv'
                    INTO TABLE t___tmp___Building_address_to_be_inserted
                    CHARACTER SET utf8mb4
                    FIELDS TERMINATED BY ';'
                    ENCLOSED BY ''
                    LINES TERMINATED BY '\n'
                    IGNORE 1 LINES
                    (@ADR_EGAID, @STR_ESID, @BDG_EGID, @ADR_EDID, @STN_LABEL, @ADR_NUMBER, @BDG_CATEGORY, @BDG_NAME, @ZIP_LABEL, @COM_FOSNR, @COM_CANTON, @ADR_STATUS, @ADR_OFFICIAL, @ADR_MODIFIED, @ADR_EASTING, @ADR_NORTHING)
                    SET
                        egaid = @ADR_EGAID,
                        id_street = @STR_ESID,
                        building_id = @BDG_EGID,
                        entrance_number = @ADR_EDID,
                        address_number = NULLIF(@ADR_NUMBER, ''),
                        building_category = @BDG_CATEGORY,
                        building_name = NULLIF(@BDG_NAME, ''),
                        postal_code_and_label = @ZIP_LABEL,
                        completion_status = @ADR_STATUS,
                        is_official = IF(@ADR_OFFICIAL = 'true', 1, 0),
                        lv95_northing = NULLIF(@ADR_NORTHING, ''),
                        lv95_easting = NULLIF(@ADR_EASTING, ''),
                        last_modification_date = STR_TO_DATE(@ADR_MODIFIED, '%d.%m.%Y');
            ");

            $this->io->success('Done !');

            $this->io->info('Deleting existing building addresses that do not exist in the temporary table...');

            $this->connection->executeQuery(/* @lang  MySQL */ "
                # Supprime les adresses existantes qui n'existent plus dans la table d'insert
                DELETE a0 FROM Building_address a0
                    LEFT JOIN t___tmp___Building_address_to_be_inserted a1 ON a0.egaid = a1.egaid
                WHERE
                    a1.egaid IS NULL;
            ");

            $this->io->success('Done.');

            $this->io->info('Adding new or more recent building addresses...');
            $this->connection->executeQuery(/* @lang  MySQL */ "
                # Insert les données à partir de la table d'insert si elles n'existent pas dans la table principale.
                # Les données sont remplacés si la date de modification (STR_MODIFIED) est plus récente
                INSERT INTO Building_address (
                    egaid, id_street_locality, building_id, entrance_number,
                    address_number, building_name, building_category,
                    completion_status, is_official, lv95_northing,
                    lv95_easting, last_modification_date
                )
                SELECT
                    a0.egaid,
                    (SELECT s2.id FROM Street__Locality s2 INNER JOIN Locality l3 ON s2.id_locality = l3.id WHERE s2.id_street = a0.id_street AND l3.postal_code_and_label = a0.postal_code_and_label),
                    a0.building_id,
                    a0.entrance_number,
                    a0.address_number,
                    a0.building_name,
                    a0.building_category,
                    a0.completion_status,
                    a0.is_official,
                    a0.lv95_northing,
                    a0.lv95_easting,
                    a0.last_modification_date
                FROM
                    t___tmp___Building_address_to_be_inserted a0
                    LEFT JOIN Building_address a1 ON a0.egaid = a1.egaid
                WHERE
                    (a1.egaid IS NULL OR a0.last_modification_date > a1.last_modification_date) AND
                    (SELECT s2.id FROM Street__Locality s2 INNER JOIN Locality l3 ON s2.id_locality = l3.id WHERE s2.id_street = a0.id_street AND l3.postal_code_and_label = a0.postal_code_and_label) IS NOT NULL
                ON DUPLICATE KEY UPDATE
                    id_street_locality = VALUES(id_street_locality),
                    building_id = VALUES(building_id),
                    entrance_number = VALUES(entrance_number),
                    address_number = VALUES(address_number),
                    building_name = VALUES(building_name),
                    building_category = VALUES(building_category),
                    completion_status = VALUES(completion_status),
                    is_official = VALUES(is_official),
                    lv95_northing = VALUES(lv95_northing),
                    lv95_easting = VALUES(lv95_easting),
                    last_modification_date = VALUES(last_modification_date);
            ");
            $this->io->success('Done !');

            $this->io->info('Deletion of the temporary table...');
            $this->connection->executeQuery(/* @lang  MySQL */ '
                DROP TEMPORARY TABLE IF EXISTS t___tmp___Building_address_to_be_inserted;
            ');
            $this->io->success('Done !');

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();

            throw new InternalImportingException('building addresses'.$e->getMessage(), $e);
        }

        $this->io->success('Building addresses has been imported!');
    }
}
