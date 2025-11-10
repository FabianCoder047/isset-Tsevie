-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 10 nov. 2025 à 13:38
-- Version du serveur : 8.2.0
-- Version de PHP : 8.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `isset_tsevie`
--

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `nom`, `niveau`) VALUES
(1, '2nde', 'G1'),
(2, '2nde', 'G2'),
(3, '2nde', 'G3'),
(4, '2nde ', 'F2'),
(5, '2nde ', 'F3'),
(6, '2nde', 'F4'),
(7, '1ère ', 'G1'),
(8, '1ère', 'G2'),
(9, '1ère', 'G3'),
(10, '1ère', 'F2'),
(11, '1ère', 'F3'),
(12, '1ère', 'F4'),
(13, 'Tle', 'G1'),
(14, 'Tle', 'G2'),
(15, 'Tle', 'G3'),
(16, 'Tle', 'F2'),
(17, 'Tle', 'F3'),
(18, 'Tle', 'F4');

-- --------------------------------------------------------

--
-- Structure de la table `eleves`
--

DROP TABLE IF EXISTS `eleves`;
CREATE TABLE IF NOT EXISTS `eleves` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_naissance` date NOT NULL,
  `lieu_naissance` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexe` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe_id` int NOT NULL,
  `contact_parent` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_inscription` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_matricule` (`matricule`),
  KEY `fk_eleves_classe` (`classe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `eleves`
--

INSERT INTO `eleves` (`id`, `matricule`, `nom`, `prenom`, `date_naissance`, `lieu_naissance`, `sexe`, `classe_id`, `contact_parent`, `date_inscription`, `created_at`, `updated_at`) VALUES
(8, '2025-00001', 'AKAKPO', 'Prisca', '2003-04-03', 'KPALIME', 'F', 10, '+228 91 22 21 01', '2025-11-10 10:56:47', '2025-11-10 10:56:47', '2025-11-10 10:56:47');

-- --------------------------------------------------------

--
-- Structure de la table `enseignements`
--

DROP TABLE IF EXISTS `enseignements`;
CREATE TABLE IF NOT EXISTS `enseignements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professeur_id` int DEFAULT NULL,
  `matiere_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_affectation` (`professeur_id`,`matiere_id`),
  KEY `fk_enseignements_matiere` (`matiere_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `enseignements`
--

INSERT INTO `enseignements` (`id`, `professeur_id`, `matiere_id`, `created_at`, `updated_at`) VALUES
(3, 3, 3, '2025-11-10 01:37:34', '2025-11-10 01:37:34'),
(4, 3, 1, '2025-11-10 01:39:54', '2025-11-10 01:39:54'),
(6, 3, 4, '2025-11-10 01:42:01', '2025-11-10 01:42:01');

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

DROP TABLE IF EXISTS `matieres`;
CREATE TABLE IF NOT EXISTS `matieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `classe_id` int NOT NULL,
  `coefficient` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `classe_id` (`classe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `matieres`
--

INSERT INTO `matieres` (`id`, `nom`, `classe_id`, `coefficient`) VALUES
(1, 'Mathématiques', 4, 3),
(3, 'Mathématiques', 10, 4),
(4, 'Mathématiques', 16, 3);

-- --------------------------------------------------------

--
-- Structure de la table `matieres_backup`
--

DROP TABLE IF EXISTS `matieres_backup`;
CREATE TABLE IF NOT EXISTS `matieres_backup` (
  `id` int NOT NULL DEFAULT '0',
  `nom` varchar(100) DEFAULT NULL,
  `classe_id` int NOT NULL,
  `coefficient` int NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `matieres_backup`
--

INSERT INTO `matieres_backup` (`id`, `nom`, `classe_id`, `coefficient`) VALUES
(1, 'Mathématiques', 4, 3),
(3, 'Mathématiques', 10, 4),
(4, 'Mathématiques', 16, 3);

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

DROP TABLE IF EXISTS `notes`;
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `eleve_id` int DEFAULT NULL,
  `matiere_id` int DEFAULT NULL,
  `classe_id` int DEFAULT NULL,
  `interro1` decimal(4,2) DEFAULT NULL COMMENT 'Note de la première interrogation',
  `interro2` decimal(4,2) DEFAULT NULL COMMENT 'Note de la deuxième interrogation',
  `devoir` decimal(4,2) DEFAULT NULL COMMENT 'Note du devoir',
  `compo` decimal(4,2) DEFAULT NULL COMMENT 'Note de la composition',
  `semestre` int NOT NULL COMMENT '1 ou 2',
  `date_saisie` datetime DEFAULT CURRENT_TIMESTAMP,
  `derniere_maj` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `saisie_par` int DEFAULT NULL COMMENT 'ID du professeur qui a saisi ou modifié la note',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_note` (`eleve_id`,`matiere_id`,`classe_id`,`semestre`),
  KEY `eleve_id` (`eleve_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `classe_id` (`classe_id`),
  KEY `saisie_par` (`saisie_par`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table des notes des élèves par matière, classe et semestre';

--
-- Déclencheurs `notes`
--
DROP TRIGGER IF EXISTS `before_update_notes`;
DELIMITER $$
CREATE TRIGGER `before_update_notes` BEFORE UPDATE ON `notes` FOR EACH ROW BEGIN
        SET NEW.derniere_maj = CURRENT_TIMESTAMP;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `periodes`
--

DROP TABLE IF EXISTS `periodes`;
CREATE TABLE IF NOT EXISTS `periodes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `annee_scolaire` varchar(20) NOT NULL,
  `est_actif` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `periodes`
--

INSERT INTO `periodes` (`id`, `nom`, `date_debut`, `date_fin`, `annee_scolaire`, `est_actif`, `created_at`) VALUES
(1, '1er Semestre', '2025-09-01', '2026-01-31', '2025-2026', 1, '2025-11-10 00:56:24'),
(2, '2ème Semestre', '2026-02-01', '2026-05-31', '2025-2026', 0, '2025-11-10 00:56:24');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('directeur','secretaire','professeur') DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `first_login` tinyint(1) DEFAULT '1',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `username`, `password`, `role`, `statut`, `first_login`, `date_creation`) VALUES
(1, 'Directeur', '', 'isset.tsevie@gmail.com', 'directeur', '$2y$10$XQWFb2zZlyfBKmRvUQP9IeT8IZVUE4LiiTdbBRUAkwXSIJux4iffG', 'directeur', 'actif', 0, '2025-11-09 22:18:37'),
(2, 'ANOUMOU', 'Grace', 'graceanoumou@gmail.com', 'ganoumou', '$2y$10$NJDMhb7LBihHBgWize4f7.0CIFsX5PhFOkrtlqStzIKvAC8gRbI1C', 'secretaire', 'actif', 0, '2025-11-10 01:08:52'),
(3, 'DEKLO', 'Komlan', 'deklokomla@gmail.com', 'kdeklo', '$2y$10$V6LSsBqu3D5q.Idi9T3LuuwBKsPJ3nutGqy/Zr6PzIFdq5jCEvjyC', 'professeur', 'actif', 0, '2025-11-10 01:11:47'),
(4, 'AMOUSSOU', 'Patrice', 'patriceamoussou@gmail.com', 'pamoussou', '$2y$10$zAtB0PA9euVE276WL3Cgm.vpqYmdsvqhbFH01LtQ4P8.dxjf/qqOO', 'professeur', 'actif', 1, '2025-11-10 01:25:37');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `eleves`
--
ALTER TABLE `eleves`
  ADD CONSTRAINT `eleves_ibfk_1` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eleves_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`eleve_id`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`saisie_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
