<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200306102107 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(86) NOT NULL, business_username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, email_change_pending VARCHAR(255) DEFAULT NULL, email_change_token VARCHAR(86) DEFAULT NULL, email_change_requested_at DATETIME DEFAULT NULL, account_deletion_token VARCHAR(86) DEFAULT NULL, account_deletion_requested_at DATETIME DEFAULT NULL, roles JSON NOT NULL, registered_at DATETIME NOT NULL, activated TINYINT(1) NOT NULL, account_activation_token VARCHAR(86) DEFAULT NULL, password_reset_token VARCHAR(86) DEFAULT NULL, password_reset_requested_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D649F06E08E1 (business_username), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D64956F4FA25 (email_change_token), UNIQUE INDEX UNIQ_8D93D649B0D7B4F7 (account_deletion_token), UNIQUE INDEX UNIQ_8D93D649D7523EBF (account_activation_token), UNIQUE INDEX UNIQ_8D93D6496B7BA4B6 (password_reset_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user');
    }
}
