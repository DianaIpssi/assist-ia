<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles JSON column to agent table for Symfony Security';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agent ADD roles JSON DEFAULT NULL');
        $this->addSql('UPDATE agent SET roles = \'["ROLE_AGENT"]\' WHERE roles IS NULL');
        $this->addSql('ALTER TABLE agent MODIFY roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agent DROP roles');
    }
}
