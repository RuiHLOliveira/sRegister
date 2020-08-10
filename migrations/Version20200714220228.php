<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200714220228 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE situation (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, situation VARCHAR(255) NOT NULL, INDEX IDX_EC2D9ACA9D86650F (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE situation ADD CONSTRAINT FK_EC2D9ACA9D86650F FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql("INSERT INTO situation (id, situation) VALUES (1, 'Tickler');");
        $this->addSql("INSERT INTO situation (id, situation) VALUES (2, 'Waiting For');");
        $this->addSql("INSERT INTO situation (id, situation) VALUES (3, 'Recurring');");
        $this->addSql("INSERT INTO situation (id, situation) VALUES (4, 'Next');");
        $this->addSql("INSERT INTO situation (id, situation) VALUES (5, 'Read List');");
        $this->addSql("INSERT INTO situation (id, situation) VALUES (6, 'Someday/Maybe');");
        $this->addSql("INSERT INTO situation (id, situation) VALUES (7, 'Project');");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE situation');
    }
}
