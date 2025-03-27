<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250327111535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_receipt DROP CONSTRAINT fk_c3118c49537a1329');
        $this->addSql('ALTER TABLE message_receipt DROP CONSTRAINT fk_c3118c49a76ed395');
        $this->addSql('ALTER TABLE message_reaction DROP CONSTRAINT fk_adf1c3e6537a1329');
        $this->addSql('ALTER TABLE message_reaction DROP CONSTRAINT fk_adf1c3e6a76ed395');
        $this->addSql('ALTER TABLE attachment DROP CONSTRAINT fk_795fd9bb537a1329');
        $this->addSql('DROP TABLE message_receipt');
        $this->addSql('DROP TABLE message_reaction');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('ALTER TABLE conversation DROP is_group');
        $this->addSql('ALTER TABLE conversation DROP is_encrypted');
        $this->addSql('ALTER TABLE conversation DROP settings');
        $this->addSql('ALTER TABLE conversation_participant DROP left_at');
        $this->addSql('ALTER TABLE conversation_participant DROP is_admin');
        $this->addSql('ALTER TABLE conversation_participant DROP is_muted');
        $this->addSql('ALTER TABLE conversation_participant DROP muted_until');
        $this->addSql('ALTER TABLE conversation_participant DROP notifications_enabled');
        $this->addSql('ALTER TABLE conversation_participant DROP is_archived');
        $this->addSql('ALTER TABLE conversation_participant DROP is_pinned');
        $this->addSql('ALTER TABLE conversation_participant DROP pin_position');
        $this->addSql('ALTER TABLE conversation_participant DROP last_read_at');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT fk_b6bd307f14399779');
        $this->addSql('DROP INDEX idx_b6bd307f14399779');
        $this->addSql('ALTER TABLE message DROP parent_message_id');
        $this->addSql('ALTER TABLE message DROP edited_at');
        $this->addSql('ALTER TABLE message DROP is_deleted');
        $this->addSql('ALTER TABLE message DROP metadata');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE message_receipt (id UUID NOT NULL, message_id UUID NOT NULL, user_id UUID NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX unique_message_user ON message_receipt (message_id, user_id)');
        $this->addSql('CREATE INDEX idx_c3118c49a76ed395 ON message_receipt (user_id)');
        $this->addSql('CREATE INDEX idx_c3118c49537a1329 ON message_receipt (message_id)');
        $this->addSql('COMMENT ON COLUMN message_receipt.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message_receipt.message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message_receipt.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE message_reaction (id UUID NOT NULL, message_id UUID NOT NULL, user_id UUID NOT NULL, reaction VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX unique_message_user_reaction ON message_reaction (message_id, user_id, reaction)');
        $this->addSql('CREATE INDEX idx_adf1c3e6a76ed395 ON message_reaction (user_id)');
        $this->addSql('CREATE INDEX idx_adf1c3e6537a1329 ON message_reaction (message_id)');
        $this->addSql('COMMENT ON COLUMN message_reaction.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message_reaction.message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message_reaction.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE attachment (id UUID NOT NULL, message_id UUID NOT NULL, file_name VARCHAR(255) NOT NULL, file_type VARCHAR(100) NOT NULL, file_size VARCHAR(50) NOT NULL, storage_path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, metadata JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_795fd9bb537a1329 ON attachment (message_id)');
        $this->addSql('COMMENT ON COLUMN attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN attachment.message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE message_receipt ADD CONSTRAINT fk_c3118c49537a1329 FOREIGN KEY (message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_receipt ADD CONSTRAINT fk_c3118c49a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_reaction ADD CONSTRAINT fk_adf1c3e6537a1329 FOREIGN KEY (message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_reaction ADD CONSTRAINT fk_adf1c3e6a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT fk_795fd9bb537a1329 FOREIGN KEY (message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_participant ADD left_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation_participant ADD is_admin BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE conversation_participant ADD is_muted BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE conversation_participant ADD muted_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation_participant ADD notifications_enabled BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE conversation_participant ADD is_archived BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE conversation_participant ADD is_pinned BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE conversation_participant ADD pin_position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation_participant ADD last_read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD parent_message_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD edited_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD is_deleted BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE message ADD metadata JSON NOT NULL');
        $this->addSql('COMMENT ON COLUMN message.parent_message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT fk_b6bd307f14399779 FOREIGN KEY (parent_message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_b6bd307f14399779 ON message (parent_message_id)');
        $this->addSql('ALTER TABLE conversation ADD is_group BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE conversation ADD is_encrypted BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE conversation ADD settings JSON NOT NULL');
    }
}
