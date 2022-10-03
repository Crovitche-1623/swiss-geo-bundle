<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Import\Meilisearch;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MeiliSearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateDocumentCommand extends Command
{
    protected static $defaultName = 'swiss-geo-bundle:import:meilisearch:documents:generate:topography';

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct(self::$defaultName);
    }

    /**
     * {@inheritDoc}
     */
    public function configure(): void
    {
        $this
            ->setName('pesetas:import:meilisearch:documents:generate:topography')
            ->setDescription('Generate well formatted documents for meilisearch');
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
                CONCAT(CONCAT(CONCAT(CONCAT(CONCAT(CONCAT(s2.label, CONCAT(' ', a0.address_number)), ' '), l3.postal_code_and_label), ' ('), l3.region_abbreviation), ')') as title,
                l3.region_abbreviation as region_abbreviation,
                l3.postal_code_and_label as postal_code_and_label,
                s2.label as street_label,
                LENGTH(a0.address_number) AS address_number_length,
                a0.address_number AS address_number,
                a0.northing as northing,
                a0.easting as easting
            FROM Address a0
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
            'egaid', 'title', 'northing', 'easting'
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
            $memory = [];
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }
}
