<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200610093739 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_role_user (user_role_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_33CC29398E0E3CA6 (user_role_id), INDEX IDX_33CC2939A76ED395 (user_id), PRIMARY KEY(user_role_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_role_user ADD CONSTRAINT FK_33CC29398E0E3CA6 FOREIGN KEY (user_role_id) REFERENCES user_role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role_user ADD CONSTRAINT FK_33CC2939A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user DROP roles');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_role_user DROP FOREIGN KEY FK_33CC29398E0E3CA6');
        $this->addSql('DROP TABLE user_role');
        $this->addSql('DROP TABLE user_role_user');
        $this->addSql('ALTER TABLE user ADD roles JSON NOT NULL');
    }
}
