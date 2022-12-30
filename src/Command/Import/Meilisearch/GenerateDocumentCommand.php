<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Import\Meilisearch;

use Doctrine\DBAL\{Connection, Exception};
use MeiliSearch\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: self::COMMAND_NAME,
    description: 'Generate well formatted documents for meilisearch'
)]
class GenerateDocumentCommand extends Command
{
    public const COMMAND_NAME = 'swiss-geo-bundle:import:meilisearch:documents:generate';

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     *
     * @throws  Exception
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $addresses = $this->connection->executeQuery("
            SELECT
                a0.egaid as egaid,
                CONCAT(s2.label, ' ', a0.address_number, ' ', l3.postal_code_and_label, ' (', l3.region_abbreviation, ')') as title,
                l3.region_abbreviation as region_abbreviation,
                l3.postal_code_and_label as postal_code_and_label,
                s2.label as street_label,
                LENGTH(a0.address_number) AS address_number_length,
                a0.address_number AS address_number,
                a0.lv95_northing as northing,
                a0.lv95_easting as easting,
                CONCAT(
                    'https://tile.openstreetmap.org/18/',
                    WGS84LongitudeToOSMTile(
                        LV95toWGSLongitude(
                            lv95_easting,
                            lv95_northing
                            ),
                        18
                    ),
                    '/',
                    WGS84LatitudeToOSMTile(
                        LV95toWGSLatitude(
                            lv95_easting,
                            lv95_northing
                            ),
                        18
                    ),
                    '.png'
                ) AS tilemap_image
            FROM Building_address a0
                 INNER JOIN Street__Locality s1 ON a0.id_street_locality = s1.id
                 INNER JOIN Street s2 ON s1.id_street = s2.esid
                 INNER JOIN Locality l3 ON s1.id_locality = l3.id
            WHERE
                a0.address_number IS NOT NULL AND
                s2.type <> 'outdated'
        ");

        $io = new SymfonyStyle($input, $output);

        $progressBar = $io->createProgressBar($addresses->rowCount());

        // Tweak this value to match with your PHP available memory
        $batchSize = 250000;
        $i = 1;
        $memory = [];

        // Creating the index
        $client = new Client('http://meilisearch:7700');

        $client->createIndex('addresses', [
            'primaryKey' => 'egaid'
        ]);

        $client->index('addresses')->updateDisplayedAttributes([
            'egaid', 'title', 'northing', 'easting', 'tilemap_image'
        ]);

        $client->index('addresses')->updateSearchableAttributes([
            'title'
        ]);

        $client->index('addresses')->updateSortableAttributes([
            'title',
            'region_abbreviation',
            'postal_code_and_label',
            'street_label',
            'address_number_length',
            'address_number'
        ]);

        $client->index('addresses')->updateRankingRules([
            'words',
            'sort',
            'typo',
            'proximity',
            'attribute',
            'exactness'
        ]);

        foreach ($addresses->iterateAssociative() as $address) {
            $memory[] = $address;

            if ($i === $batchSize) {
                $client->index('addresses')->addDocuments($memory, 'egaid');
                $i = 0;
                $memory = [];
            }

            $i++;

            $progressBar->advance();
        }

        if (\count($memory) > 0) {
            $client->index('addresses')->addDocuments($memory);
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
