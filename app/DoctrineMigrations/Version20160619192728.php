<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160619192728 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE entry');
        $this->addSql('ALTER TABLE user ADD preview TINYINT(1) NOT NULL DEFAULT false');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE entry (id BIGINT AUTO_INCREMENT NOT NULL, feed_id BIGINT NOT NULL, title VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, link VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, content LONGTEXT NOT NULL COLLATE utf8_unicode_ci, content_hash VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, author VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, category VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, is_read TINYINT(1) DEFAULT NULL, is_starred TINYINT(1) DEFAULT NULL, date_add INT NOT NULL, date_up INT NOT NULL, INDEX IDX_2B219D7051A5BC03 (feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE entry ADD CONSTRAINT FK_2B219D7051A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id)');
        $this->addSql('ALTER TABLE user DROP preview');
    }
}
