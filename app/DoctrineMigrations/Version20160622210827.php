<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160622210827 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE source (id BIGINT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, web VARCHAR(255) NOT NULL, feed_url VARCHAR(255) NOT NULL, date_add INT NOT NULL, date_up INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sources_categories (source_id BIGINT NOT NULL, sourcecategory_id BIGINT NOT NULL, INDEX IDX_78FC70C9953C1C61 (source_id), INDEX IDX_78FC70C9DB91CBE (sourcecategory_id), PRIMARY KEY(source_id, sourcecategory_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source_category (id BIGINT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, date_add INT NOT NULL, date_up INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sources_categories ADD CONSTRAINT FK_78FC70C9953C1C61 FOREIGN KEY (source_id) REFERENCES source (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sources_categories ADD CONSTRAINT FK_78FC70C9DB91CBE FOREIGN KEY (sourcecategory_id) REFERENCES source_category (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sources_categories DROP FOREIGN KEY FK_78FC70C9953C1C61');
        $this->addSql('ALTER TABLE sources_categories DROP FOREIGN KEY FK_78FC70C9DB91CBE');
        $this->addSql('DROP TABLE source');
        $this->addSql('DROP TABLE sources_categories');
        $this->addSql('DROP TABLE source_category');
    }
}
