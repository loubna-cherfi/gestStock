<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240624095918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, name VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_497DD63479F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE detaille_vente (id INT AUTO_INCREMENT NOT NULL, produit_id INT DEFAULT NULL, vente_id INT DEFAULT NULL, prix_unutaire DOUBLE PRECISION NOT NULL, quantite INT NOT NULL, INDEX IDX_9B1F0F04F347EFB (produit_id), INDEX IDX_9B1F0F047DC7170A (vente_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, methode VARCHAR(255) NOT NULL, INDEX IDX_B1DC7A1EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiementuser (id INT AUTO_INCREMENT NOT NULL, paiement_id INT DEFAULT NULL, vente_id INT DEFAULT NULL, date_paie DATETIME NOT NULL, numcartbancaire INT NOT NULL, INDEX IDX_F5D8126A2A4C4478 (paiement_id), UNIQUE INDEX UNIQ_F5D8126A7DC7170A (vente_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, categorie_id INT DEFAULT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, prix DOUBLE PRECISION NOT NULL, date_entreer DATETIME NOT NULL, date_expiration DATETIME DEFAULT NULL, quantite INT NOT NULL, INDEX IDX_29A5EC27BCF5E72D (categorie_id), INDEX IDX_29A5EC27A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, produit_id INT DEFAULT NULL, user_id INT DEFAULT NULL, quantite INT NOT NULL, UNIQUE INDEX UNIQ_4B365660F347EFB (produit_id), INDEX IDX_4B365660A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, mobile INT NOT NULL, adress VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vente (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, detaille_vente_id INT DEFAULT NULL, paiement_id INT DEFAULT NULL, date_vente DATETIME NOT NULL, montant DOUBLE PRECISION NOT NULL, INDEX IDX_888A2A4CA76ED395 (user_id), INDEX IDX_888A2A4C6BD87C4D (detaille_vente_id), INDEX IDX_888A2A4C2A4C4478 (paiement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE categorie ADD CONSTRAINT FK_497DD63479F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE detaille_vente ADD CONSTRAINT FK_9B1F0F04F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE detaille_vente ADD CONSTRAINT FK_9B1F0F047DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE paiementuser ADD CONSTRAINT FK_F5D8126A2A4C4478 FOREIGN KEY (paiement_id) REFERENCES paiement (id)');
        $this->addSql('ALTER TABLE paiementuser ADD CONSTRAINT FK_F5D8126A7DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C6BD87C4D FOREIGN KEY (detaille_vente_id) REFERENCES detaille_vente (id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C2A4C4478 FOREIGN KEY (paiement_id) REFERENCES paiement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie DROP FOREIGN KEY FK_497DD63479F37AE5');
        $this->addSql('ALTER TABLE detaille_vente DROP FOREIGN KEY FK_9B1F0F04F347EFB');
        $this->addSql('ALTER TABLE detaille_vente DROP FOREIGN KEY FK_9B1F0F047DC7170A');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EA76ED395');
        $this->addSql('ALTER TABLE paiementuser DROP FOREIGN KEY FK_F5D8126A2A4C4478');
        $this->addSql('ALTER TABLE paiementuser DROP FOREIGN KEY FK_F5D8126A7DC7170A');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27A76ED395');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660F347EFB');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660A76ED395');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CA76ED395');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C6BD87C4D');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C2A4C4478');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE detaille_vente');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE paiementuser');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE vente');
    }
}
