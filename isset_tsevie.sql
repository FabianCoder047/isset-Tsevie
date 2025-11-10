-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : dim. 09 nov. 2025 à 22:46
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
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `nom`, `niveau`) VALUES
(1, '2nde G1', '2nde'),
(2, '2nde G2', '2nde'),
(3, '2nde G3', '2nde'),
(4, '2nde F2', '2nde'),
(5, '2nde F3', '2nde'),
(6, '2nde F4', '2nde'),
(7, '1ère G1', '1ère'),
(8, '1ère G2', '1ère'),
(9, '1ère G3', '1ère'),
(10, '1ère F2', '1ère'),
(11, '1ère F3', '1ère'),
(12, '1ère F4', '1ère'),
(13, 'Tle G1', 'Tle'),
(14, 'Tle G2', 'Tle'),
(15, 'Tle G3', 'Tle'),
(16, 'Tle F2', 'Tle'),
(17, 'Tle F3', 'Tle'),
(18, 'Tle F4', 'Tle');

-- --------------------------------------------------------

--
-- Structure de la table `eleves`
--

DROP TABLE IF EXISTS `eleves`;
CREATE TABLE IF NOT EXISTS `eleves` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `sexe` char(1) DEFAULT NULL,
  `classe_id` int DEFAULT NULL,
  `date_inscription` date DEFAULT NULL,
  `statut` enum('ancien','nouveau') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classe_id` (`classe_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enseignements`
--

DROP TABLE IF EXISTS `enseignements`;
CREATE TABLE IF NOT EXISTS `enseignements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `professeur_id` int DEFAULT NULL,
  `matiere_classe_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professeur_id` (`professeur_id`),
  KEY `matiere_classe_id` (`matiere_classe_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

DROP TABLE IF EXISTS `matieres`;
CREATE TABLE IF NOT EXISTS `matieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `matiere_classe`
--

DROP TABLE IF EXISTS `matiere_classe`;
CREATE TABLE IF NOT EXISTS `matiere_classe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matiere_id` int DEFAULT NULL,
  `classe_id` int DEFAULT NULL,
  `coefficient` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `classe_id` (`classe_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

DROP TABLE IF EXISTS `notes`;
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `eleve_id` int DEFAULT NULL,
  `matiere_classe_id` int DEFAULT NULL,
  `note` decimal(4,2) DEFAULT NULL,
  `semestre` int DEFAULT NULL,
  `date_saisie` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `eleve_id` (`eleve_id`),
  KEY `matiere_classe_id` (`matiere_classe_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('directeur','secretaire','professeur') DEFAULT NULL,
  `first_login` tinyint(1) DEFAULT '1',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `email`, `password`, `role`, `first_login`, `date_creation`) VALUES
(1, 'Directeur', 'isset.tsevie@gmail.com', '$2y$10$XQWFb2zZlyfBKmRvUQP9IeT8IZVUE4LiiTdbBRUAkwXSIJux4iffG', 'directeur', 0, '2025-11-09 22:18:37');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
