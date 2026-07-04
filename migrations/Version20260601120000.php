<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token fields on agent';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agent ADD reset_token VARCHAR(64) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_AGENT_RESET_TOKEN ON agent (reset_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_AGENT_RESET_TOKEN ON agent');
        $this->addSql('ALTER TABLE agent DROP reset_token, DROP reset_token_expires_at');
    }
}
