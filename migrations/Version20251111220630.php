<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251111220630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clients (id UUID NOT NULL, tenant_id UUID NOT NULL, company_name VARCHAR(255) NOT NULL, contact_name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, is_active BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, billing_street VARCHAR(255) DEFAULT NULL, billing_city VARCHAR(100) DEFAULT NULL, billing_state VARCHAR(100) DEFAULT NULL, billing_postal_code VARCHAR(20) DEFAULT NULL, billing_country VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_client_tenant ON clients (tenant_id)');
        $this->addSql('CREATE INDEX idx_client_company_name ON clients (company_name)');
        $this->addSql('CREATE INDEX idx_client_email ON clients (email)');
        $this->addSql('CREATE INDEX idx_client_is_active ON clients (is_active)');
        $this->addSql('COMMENT ON COLUMN clients.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN clients.tenant_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN clients.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN clients.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E749033212A FOREIGN KEY (tenant_id) REFERENCES tenants (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE clients DROP CONSTRAINT FK_C82E749033212A');
        $this->addSql('DROP TABLE clients');
    }
}
