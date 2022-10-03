<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220914100446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add correct constraint for building name and address number';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE Building_address
                ADD CONSTRAINT CK___Building_address___building_name__xor__address_number
                    CHECK ((building_name IS NOT NULL XOR address_number IS NOT NULL) OR (building_name IS NULL AND address_number IS NULL));');
    }

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE Building_address DROP CONSTRAINT CK___Building_address___building_name__xor__address_number;');
    }

    /**
     * {@inheritDoc}
     */
    public function isTransactional(): bool
    {
        return false;
    }
}
