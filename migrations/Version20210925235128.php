<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210925235128 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE invitation_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE note_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE notebook_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE project_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE situation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE task_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_access_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE invitation_token (id INT NOT NULL, user_id INT DEFAULT NULL, invitation_token VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, active BOOLEAN DEFAULT \'true\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_33FC351AA76ED395 ON invitation_token (user_id)');
        $this->addSql('CREATE TABLE note (id INT NOT NULL, user_id INT NOT NULL, notebook_id INT NOT NULL, name VARCHAR(255) NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CFBDFA14A76ED395 ON note (user_id)');
        $this->addSql('CREATE INDEX IDX_CFBDFA14F74303D6 ON note (notebook_id)');
        $this->addSql('CREATE TABLE notebook (id INT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F67D9A2BA76ED395 ON notebook (user_id)');
        $this->addSql('CREATE TABLE project (id INT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, duedate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed BOOLEAN DEFAULT \'false\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEA76ED395 ON project (user_id)');
        $this->addSql('CREATE TABLE situation (id INT NOT NULL, user_id INT DEFAULT NULL, situation VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EC2D9ACAA76ED395 ON situation (user_id)');
        $this->addSql('CREATE TABLE task (id INT NOT NULL, user_id INT NOT NULL, situation_id INT DEFAULT NULL, project_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, duedate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed BOOLEAN DEFAULT \'false\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_527EDB25A76ED395 ON task (user_id)');
        $this->addSql('CREATE INDEX IDX_527EDB253408E8AF ON task (situation_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25166D1F9C ON task (project_id)');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE user_access (id INT NOT NULL, user_id INT NOT NULL, hash VARCHAR(255) NOT NULL, active BOOLEAN NOT NULL, login_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_usage_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, refresh_token VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_633B3069A76ED395 ON user_access (user_id)');
        $this->addSql('ALTER TABLE invitation_token ADD CONSTRAINT FK_33FC351AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14F74303D6 FOREIGN KEY (notebook_id) REFERENCES notebook (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notebook ADD CONSTRAINT FK_F67D9A2BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE situation ADD CONSTRAINT FK_EC2D9ACAA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB253408E8AF FOREIGN KEY (situation_id) REFERENCES situation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_access ADD CONSTRAINT FK_633B3069A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE note DROP CONSTRAINT FK_CFBDFA14F74303D6');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25166D1F9C');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB253408E8AF');
        $this->addSql('ALTER TABLE invitation_token DROP CONSTRAINT FK_33FC351AA76ED395');
        $this->addSql('ALTER TABLE note DROP CONSTRAINT FK_CFBDFA14A76ED395');
        $this->addSql('ALTER TABLE notebook DROP CONSTRAINT FK_F67D9A2BA76ED395');
        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EEA76ED395');
        $this->addSql('ALTER TABLE situation DROP CONSTRAINT FK_EC2D9ACAA76ED395');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25A76ED395');
        $this->addSql('ALTER TABLE user_access DROP CONSTRAINT FK_633B3069A76ED395');
        $this->addSql('DROP SEQUENCE invitation_token_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE note_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE notebook_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE project_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE situation_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE task_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE user_access_id_seq CASCADE');
        $this->addSql('DROP TABLE invitation_token');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE notebook');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE situation');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_access');
    }
}
