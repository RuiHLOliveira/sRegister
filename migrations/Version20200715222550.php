<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200715222550 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE situation DROP FOREIGN KEY FK_EC2D9ACA9D86650F');
        $this->addSql('DROP INDEX idx_ec2d9aca9d86650f ON situation');
        $this->addSql('CREATE INDEX IDX_EC2D9ACAA76ED395 ON situation (user_id)');
        $this->addSql('ALTER TABLE situation ADD CONSTRAINT FK_EC2D9ACA9D86650F FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB252C192E8F');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB259D86650F');
        $this->addSql('ALTER TABLE task ADD created_at DATETIME NOT NULL DEFAULT NOW(), ADD updated_at DATETIME NOT NULL DEFAULT NOW()');
        $this->addSql('DROP INDEX idx_527edb259d86650f ON task');
        $this->addSql('CREATE INDEX IDX_527EDB25A76ED395 ON task (user_id)');
        $this->addSql('DROP INDEX idx_527edb252c192e8f ON task');
        $this->addSql('CREATE INDEX IDX_527EDB253408E8AF ON task (situation_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB252C192E8F FOREIGN KEY (situation_id) REFERENCES situation (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB259D86650F FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE situation DROP FOREIGN KEY FK_EC2D9ACAA76ED395');
        $this->addSql('DROP INDEX idx_ec2d9acaa76ed395 ON situation');
        $this->addSql('CREATE INDEX IDX_EC2D9ACA9D86650F ON situation (user_id)');
        $this->addSql('ALTER TABLE situation ADD CONSTRAINT FK_EC2D9ACAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25A76ED395');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB253408E8AF');
        $this->addSql('ALTER TABLE task DROP created_at, DROP updated_at');
        $this->addSql('DROP INDEX idx_527edb25a76ed395 ON task');
        $this->addSql('CREATE INDEX IDX_527EDB259D86650F ON task (user_id)');
        $this->addSql('DROP INDEX idx_527edb253408e8af ON task');
        $this->addSql('CREATE INDEX IDX_527EDB252C192E8F ON task (situation_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB253408E8AF FOREIGN KEY (situation_id) REFERENCES situation (id)');
    }
}
