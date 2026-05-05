<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création initiale : user, formation, module, question, badge, user_progress, user_badge';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE badge (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            icon VARCHAR(255) DEFAULT NULL,
            criteria JSON NOT NULL,
            UNIQUE INDEX UNIQ_FEF0481D5E237E06 (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE formation (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            difficulty VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE module (
            id INT AUTO_INCREMENT NOT NULL,
            formation_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT DEFAULT NULL,
            position INT NOT NULL,
            INDEX IDX_C2426285200282A (formation_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE question (
            id INT AUTO_INCREMENT NOT NULL,
            module_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            options JSON NOT NULL,
            correct_answer VARCHAR(255) NOT NULL,
            type VARCHAR(20) NOT NULL,
            difficulty INT NOT NULL,
            INDEX IDX_B6F7494EAFC2B591 (module_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE `user` (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            username VARCHAR(50) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            xp INT NOT NULL,
            level INT NOT NULL,
            streak INT NOT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE user_badge (
            user_id INT NOT NULL,
            badge_id INT NOT NULL,
            INDEX IDX_1C32B345A76ED395 (user_id),
            INDEX IDX_1C32B345F7A2C2FC (badge_id),
            PRIMARY KEY(user_id, badge_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE user_progress (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            module_id INT NOT NULL,
            completed TINYINT(1) NOT NULL,
            score INT NOT NULL,
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX user_module_unique (user_id, module_id),
            INDEX IDX_ECF035D8A76ED395 (user_id),
            INDEX IDX_ECF035D8AFC2B591 (module_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE module ADD CONSTRAINT FK_C2426285200282A FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494EAFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_1C32B345A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_1C32B345F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id)');
        $this->addSql('ALTER TABLE user_progress ADD CONSTRAINT FK_ECF035D8A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_progress ADD CONSTRAINT FK_ECF035D8AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE module DROP FOREIGN KEY FK_C2426285200282A');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EAFC2B591');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_1C32B345A76ED395');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_1C32B345F7A2C2FC');
        $this->addSql('ALTER TABLE user_progress DROP FOREIGN KEY FK_ECF035D8A76ED395');
        $this->addSql('ALTER TABLE user_progress DROP FOREIGN KEY FK_ECF035D8AFC2B591');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE module');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_badge');
        $this->addSql('DROP TABLE user_progress');
    }
}
