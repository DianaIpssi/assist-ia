<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206130258 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agent ADD email VARCHAR(180) NOT NULL, ADD full_name VARCHAR(255) NOT NULL, ADD password VARCHAR(255) NOT NULL, ADD created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agent DROP email, DROP full_name, DROP password, DROP created_at');
    }
}
