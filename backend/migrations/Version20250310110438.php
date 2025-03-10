<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250310110438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attachment (id UUID NOT NULL, message_id UUID NOT NULL, file_name VARCHAR(255) NOT NULL, file_type VARCHAR(100) NOT NULL, file_size VARCHAR(50) NOT NULL, storage_path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, metadata JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_795FD9BB537A1329 ON attachment (message_id)');
        $this->addSql('COMMENT ON COLUMN attachment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN attachment.message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE conversation (id UUID NOT NULL, creator_id UUID NOT NULL, name VARCHAR(255) DEFAULT NULL, avatar_url VARCHAR(255) DEFAULT NULL, is_group BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_encrypted BOOLEAN NOT NULL, settings JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8A8E26E961220EA6 ON conversation (creator_id)');
        $this->addSql('COMMENT ON COLUMN conversation.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN conversation.creator_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE conversation_participant (id UUID NOT NULL, user_id UUID NOT NULL, conversation_id UUID NOT NULL, role VARCHAR(50) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, left_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_admin BOOLEAN NOT NULL, is_muted BOOLEAN NOT NULL, muted_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, notifications_enabled BOOLEAN NOT NULL, is_archived BOOLEAN NOT NULL, is_pinned BOOLEAN NOT NULL, pin_position INT DEFAULT NULL, last_read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_39801661A76ED395 ON conversation_participant (user_id)');
        $this->addSql('CREATE INDEX IDX_398016619AC0396 ON conversation_participant (conversation_id)');
        $this->addSql('COMMENT ON COLUMN conversation_participant.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN conversation_participant.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN conversation_participant.conversation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE message (id UUID NOT NULL, conversation_id UUID NOT NULL, sender_id UUID NOT NULL, parent_message_id UUID DEFAULT NULL, content TEXT NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, edited_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_deleted BOOLEAN NOT NULL, metadata JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6BD307F9AC0396 ON message (conversation_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FF624B39D ON message (sender_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F14399779 ON message (parent_message_id)');
        $this->addSql('COMMENT ON COLUMN message.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message.conversation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message.sender_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message.parent_message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE message_reaction (id UUID NOT NULL, message_id UUID NOT NULL, user_id UUID NOT NULL, reaction VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_ADF1C3E6537A1329 ON message_reaction (message_id)');
        $this->addSql('CREATE INDEX IDX_ADF1C3E6A76ED395 ON message_reaction (user_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_message_user_reaction ON message_reaction (message_id, user_id, reaction)');
        $this->addSql('COMMENT ON COLUMN message_reaction.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message_reaction.message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message_reaction.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE message_receipt (id UUID NOT NULL, message_id UUID NOT NULL, user_id UUID NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C3118C49537A1329 ON message_receipt (message_id)');
        $this->addSql('CREATE INDEX IDX_C3118C49A76ED395 ON message_receipt (user_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_message_user ON message_receipt (message_id, user_id)');
        $this->addSql('COMMENT ON COLUMN message_receipt.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message_receipt.message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN message_receipt.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB537A1329 FOREIGN KEY (message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E961220EA6 FOREIGN KEY (creator_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_participant ADD CONSTRAINT FK_39801661A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_participant ADD CONSTRAINT FK_398016619AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F14399779 FOREIGN KEY (parent_message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_reaction ADD CONSTRAINT FK_ADF1C3E6537A1329 FOREIGN KEY (message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_reaction ADD CONSTRAINT FK_ADF1C3E6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_receipt ADD CONSTRAINT FK_C3118C49537A1329 FOREIGN KEY (message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_receipt ADD CONSTRAINT FK_C3118C49A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE attachment DROP CONSTRAINT FK_795FD9BB537A1329');
        $this->addSql('ALTER TABLE conversation DROP CONSTRAINT FK_8A8E26E961220EA6');
        $this->addSql('ALTER TABLE conversation_participant DROP CONSTRAINT FK_39801661A76ED395');
        $this->addSql('ALTER TABLE conversation_participant DROP CONSTRAINT FK_398016619AC0396');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307F14399779');
        $this->addSql('ALTER TABLE message_reaction DROP CONSTRAINT FK_ADF1C3E6537A1329');
        $this->addSql('ALTER TABLE message_reaction DROP CONSTRAINT FK_ADF1C3E6A76ED395');
        $this->addSql('ALTER TABLE message_receipt DROP CONSTRAINT FK_C3118C49537A1329');
        $this->addSql('ALTER TABLE message_receipt DROP CONSTRAINT FK_C3118C49A76ED395');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE conversation_participant');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE message_reaction');
        $this->addSql('DROP TABLE message_receipt');
    }
}
