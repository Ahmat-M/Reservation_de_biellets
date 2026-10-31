-- =======================================================
-- Base de données pour le Système de Réservation de Bus
-- Localisation : TCHAD (N'Djaména, Moundou, Abéché, Sarh, etc.)
-- =======================================================

CREATE DATABASE IF NOT EXISTS `Databasevg` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `Databasevg`;

-- 1. Table des utilisateurs
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `telephone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'agent', 'client') DEFAULT 'client',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table des véhicules
CREATE TABLE IF NOT EXISTS `vehicules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `marque` VARCHAR(100) NOT NULL,
  `immatriculation` VARCHAR(50) NOT NULL,
  `capacite` INT NOT NULL,
  `climatise` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table des chauffeurs
CREATE TABLE IF NOT EXISTS `chauffeurs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL,
  `telephone` VARCHAR(30) DEFAULT NULL,
  `permis` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table des voyages
CREATE TABLE IF NOT EXISTS `voyages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ville_depart` VARCHAR(100) NOT NULL,
  `ville_arrivee` VARCHAR(100) NOT NULL,
  `gare_depart` VARCHAR(150) DEFAULT 'Gare Routière Centrale',
  `date_depart` DATETIME NOT NULL,
  `date_arrivee` DATETIME NOT NULL,
  `prix` DECIMAL(10,2) NOT NULL,
  `places_disponibles` INT NOT NULL,
  `compagnie` VARCHAR(100) DEFAULT 'TchadBus Express',
  `vehicule_id` INT DEFAULT NULL,
  `chauffeur_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vehicule_id`) REFERENCES `vehicules`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`chauffeur_id`) REFERENCES `chauffeurs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table des réservations
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `voyage_id` INT NOT NULL,
  `nombre_places` INT NOT NULL DEFAULT 1,
  `date_reservation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `statut` VARCHAR(50) DEFAULT 'en_attente',
  `mode_reservation` VARCHAR(50) DEFAULT 'en_ligne',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`voyage_id`) REFERENCES `voyages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table des paiements
CREATE TABLE IF NOT EXISTS `paiements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT NOT NULL,
  `montant` DECIMAL(10,2) NOT NULL,
  `methode` VARCHAR(50) NOT NULL,
  `numero_transaction` VARCHAR(100) DEFAULT NULL,
  `telephone_paiement` VARCHAR(30) DEFAULT NULL,
  `statut` VARCHAR(50) DEFAULT 'payé',
  `date_paiement` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Table des tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reservation_id` INT NOT NULL,
  `numero_ticket` VARCHAR(100) NOT NULL UNIQUE,
  `statut` VARCHAR(50) DEFAULT 'valide',
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reservation_id`) REFERENCES `reservations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données initiales pour le Tchad

-- Comptes démo :
-- Admin : admin@bussystem.com / admin123
-- Client : client@bussystem.com / client123
INSERT INTO `users` (`id`, `name`, `telephone`, `email`, `password`, `role`) VALUES
(1, 'Administrateur TchadBus', '+235 66 20 00 01', 'admin@bussystem.com', '$2y$10$wUv3G/96K9X.h31w9Qo0sO8V9y.L1z9Z9z6c1t6cK2J7NqKk4uE/m', 'admin'),
(2, 'Mahamat Saleh', '+235 66 45 12 34', 'client@bussystem.com', '$2y$10$mE1z9N2Jz7r1k0y0h1w9e.v3G96K9Xh31w9Qo0sO8V9yL1z9Z9z6c', 'client')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Flotte de bus au Tchad
INSERT INTO `vehicules` (`id`, `marque`, `immatriculation`, `capacite`, `climatise`) VALUES
(1, 'Toyota Coaster VIP', '14-A-1234-TD', 30, 1),
(2, 'Mercedes-Benz Travego Grand Confort', '14-B-5678-TD', 55, 1),
(3, 'Scania Marcopolo G7', '14-C-9012-TD', 60, 1),
(4, 'Toyota HiAce Bus Interurbain', '14-D-3456-TD', 18, 0)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Chauffeurs professionnels au Tchad
INSERT INTO `chauffeurs` (`id`, `nom`, `telephone`, `permis`) VALUES
(1, 'Abdelkerim Mahamat', '+235 66 33 22 11', 'CH-TD-2018-091'),
(2, 'Djibrine Souleymane', '+235 99 44 55 66', 'CH-TD-2019-142'),
(3, 'Brahim Allamine', '+235 62 77 88 99', 'CH-TD-2020-305'),
(4, 'Nodji Joel', '+235 95 11 22 33', 'CH-TD-2021-084')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Lignes régulières interurbaines au Tchad
INSERT INTO `voyages` (`id`, `ville_depart`, `ville_arrivee`, `gare_depart`, `date_depart`, `date_arrivee`, `prix`, `places_disponibles`, `compagnie`, `vehicule_id`, `chauffeur_id`) VALUES
(1, 'N\'Djaména', 'Moundou', 'Gare Routière de Dembé', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 1 DAY), INTERVAL 8 HOUR), 12000.00, 30, 'Abou Simbil Transport', 1, 1),
(2, 'N\'Djaména', 'Abéché', 'Gare de Chagoua', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 1 DAY), INTERVAL 12 HOUR), 15000.00, 50, 'Toumaï Express Tchad', 2, 2),
(3, 'N\'Djaména', 'Sarh', 'Gare Routière de Dembé', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 2 DAY), INTERVAL 10 HOUR), 13000.00, 45, 'SNDT Transport', 3, 3),
(4, 'Moundou', 'N\'Djaména', 'Gare Centrale de Moundou', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 2 DAY), INTERVAL 8 HOUR), 12000.00, 28, 'Abou Simbil Transport', 1, 1),
(5, 'N\'Djaména', 'Kélo', 'Gare Routière de Walia', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 3 DAY), INTERVAL 6 HOUR), 10000.00, 16, 'Express du Sud', 4, 4),
(6, 'N\'Djaména', 'Bongor', 'Gare Routière de Walia', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 3 DAY), INTERVAL 4 HOUR), 7000.00, 25, 'Tchad Bus Rapide', 1, 1)
ON DUPLICATE KEY UPDATE `id`=`id`;
