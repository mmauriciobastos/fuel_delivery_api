<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251111233347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE locations (id UUID NOT NULL, tenant_id UUID NOT NULL, client_id UUID NOT NULL, address_line1 VARCHAR(255) NOT NULL, address_line2 VARCHAR(255) DEFAULT NULL, city VARCHAR(100) NOT NULL, state VARCHAR(50) NOT NULL, postal_code VARCHAR(20) NOT NULL, country VARCHAR(100) DEFAULT \'Canada\' NOT NULL, latitude NUMERIC(10, 8) DEFAULT NULL, longitude NUMERIC(11, 8) DEFAULT NULL, special_instructions TEXT DEFAULT NULL, is_primary BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_location_tenant ON locations (tenant_id)');
        $this->addSql('CREATE INDEX idx_location_client ON locations (client_id)');
        $this->addSql('CREATE INDEX idx_location_is_primary ON locations (is_primary)');
        $this->addSql('COMMENT ON COLUMN locations.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN locations.tenant_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN locations.client_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN locations.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN locations.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT FK_17E64ABA9033212A FOREIGN KEY (tenant_id) REFERENCES tenants (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT FK_17E64ABA19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE locations DROP CONSTRAINT FK_17E64ABA9033212A');
        $this->addSql('ALTER TABLE locations DROP CONSTRAINT FK_17E64ABA19EB6921');
        $this->addSql('DROP TABLE locations');
    }
}
