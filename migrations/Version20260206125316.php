<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206125316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE property CHANGE agent_id agent_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_8BF21CDE3414710B ON property (agent_id)');
    }
    

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_8BF21CDE3414710B ON property');
        $this->addSql('ALTER TABLE property CHANGE agent_id agent_id INT NOT NULL');
    }
    
}
