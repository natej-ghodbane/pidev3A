<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250425233151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE reclamation_event (id INT AUTO_INCREMENT NOT NULL, reclamation_id INT NOT NULL, start DATETIME NOT NULL, end DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, background_color VARCHAR(255) DEFAULT NULL, border_color VARCHAR(255) DEFAULT NULL, INDEX IDX_E6A239E92D6BA2D9 (reclamation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation_event ADD CONSTRAINT FK_E6A239E92D6BA2D9 FOREIGN KEY (reclamation_id) REFERENCES reclamation (id_rec)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse CHANGE id_rec id_rec INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7FAA12276 FOREIGN KEY (id_rec) REFERENCES reclamation (id_rec)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5FB6DEC7FAA12276 ON reponse (id_rec)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task DROP position, CHANGE title title VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation_event DROP FOREIGN KEY FK_E6A239E92D6BA2D9
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reclamation_event
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC7FAA12276
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_5FB6DEC7FAA12276 ON reponse
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse CHANGE id_rec id_rec INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task ADD position SMALLINT DEFAULT 0 NOT NULL, CHANGE title title VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE status status SMALLINT DEFAULT 0 COMMENT '0=Pending, 1=Doing, 2=Done', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
    }
}
