<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table setting pour la configuration de l\'application';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE setting (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            value LONGTEXT DEFAULT NULL,
            UNIQUE INDEX UNIQ_9F74B8985E237E06 (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE setting');
    }
}
