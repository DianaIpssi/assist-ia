<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_url column to property table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property ADD image_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property DROP image_url');
    }
}
