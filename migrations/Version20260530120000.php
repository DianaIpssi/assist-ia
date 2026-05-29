<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260530120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add chat messages, appointments and agent link on availability slots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, session_id VARCHAR(64) NOT NULL, role VARCHAR(20) NOT NULL, content LONGTEXT NOT NULL, client_name VARCHAR(120) DEFAULT NULL, client_email VARCHAR(180) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_CHAT_SESSION (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, agent_id INT NOT NULL, slot_id INT NOT NULL, client_name VARCHAR(120) NOT NULL, client_email VARCHAR(180) NOT NULL, client_phone VARCHAR(30) DEFAULT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_APPOINTMENT_AGENT (agent_id), INDEX IDX_APPOINTMENT_SLOT (slot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_APPOINTMENT_AGENT FOREIGN KEY (agent_id) REFERENCES agent (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_APPOINTMENT_SLOT FOREIGN KEY (slot_id) REFERENCES availability_slot (id)');
        $this->addSql('ALTER TABLE availability_slot ADD agent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE availability_slot ADD CONSTRAINT FK_SLOT_AGENT FOREIGN KEY (agent_id) REFERENCES agent (id)');
        $this->addSql('CREATE INDEX IDX_SLOT_AGENT ON availability_slot (agent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE availability_slot DROP FOREIGN KEY FK_SLOT_AGENT');
        $this->addSql('DROP INDEX IDX_SLOT_AGENT ON availability_slot');
        $this->addSql('ALTER TABLE availability_slot DROP agent_id');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE chat_message');
    }
}
