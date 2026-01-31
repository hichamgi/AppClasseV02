-- phpMyAdmin SQL Dump
-- version 5.2.2deb1+deb13u1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : sam. 31 jan. 2026 à 06:04
-- Version du serveur : 11.8.3-MariaDB-0+deb13u1 from Debian
-- Version de PHP : 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de données : `appclassetest`
--

-- --------------------------------------------------------

--
-- Structure de la table `ai_appreciations`
--

CREATE TABLE `ai_appreciations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `idacademicrecords` int(10) UNSIGNED NOT NULL,
  `scope` enum('monthly','s1','s2','annual') NOT NULL DEFAULT 's1',
  `period_key` varchar(20) NOT NULL DEFAULT '',
  `model` varchar(50) NOT NULL DEFAULT '',
  `prompt_hash` char(64) NOT NULL DEFAULT '',
  `result_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`result_json`)),
  `batch_id` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `annees`
--

CREATE TABLE `annees` (
  `id` int(11) UNSIGNED NOT NULL,
  `annee` varchar(15) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

CREATE TABLE `classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `classe` varchar(15) NOT NULL DEFAULT '',
  `idannee` int(11) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `dossiers_scolaires`
--

CREATE TABLE `dossiers_scolaires` (
  `id` int(10) UNSIGNED NOT NULL,
  `ideleve` int(11) UNSIGNED NOT NULL,
  `idannee` int(11) UNSIGNED NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `participation` int(11) NOT NULL DEFAULT 0,
  `obs1` varchar(100) DEFAULT NULL,
  `obs2` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `eleves`
--

CREATE TABLE `eleves` (
  `id` int(11) UNSIGNED NOT NULL,
  `numerosgs` varchar(12) DEFAULT NULL,
  `nom` varchar(150) DEFAULT NULL,
  `nomar` varchar(150) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `prenomar` varchar(50) DEFAULT NULL,
  `datenaiss` date DEFAULT NULL,
  `sexe` enum('M','F') NOT NULL DEFAULT 'M',
  `observation` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `eleves_classes`
--

CREATE TABLE `eleves_classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `ideleve` int(11) UNSIGNED NOT NULL,
  `idclasse` int(11) UNSIGNED NOT NULL,
  `depart` tinyint(4) NOT NULL DEFAULT 0,
  `numero` int(11) DEFAULT 0,
  `classementsgs` int(11) NOT NULL DEFAULT 0,
  `poste` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `groupe` varchar(2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `eleves_tags`
--

CREATE TABLE `eleves_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `idtag` int(10) UNSIGNED NOT NULL,
  `ideleve` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emplois_du_temps`
--

CREATE TABLE `emplois_du_temps` (
  `id` int(10) UNSIGNED NOT NULL,
  `idclasse` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `groupe` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `n` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `heure` time NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) UNSIGNED NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `abrev` varchar(15) DEFAULT NULL,
  `sem` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notebook_scores`
--

CREATE TABLE `notebook_scores` (
  `id` int(10) UNSIGNED NOT NULL,
  `idacademicrecords` int(10) UNSIGNED NOT NULL,
  `idmodule` int(11) UNSIGNED NOT NULL,
  `score_cours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_exercices` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `idacademicrecords` int(10) UNSIGNED NOT NULL,
  `idtypeexamen` tinyint(3) UNSIGNED NOT NULL,
  `note` decimal(5,2) DEFAULT NULL,
  `absent` tinyint(1) DEFAULT NULL,
  `triche` tinyint(1) DEFAULT NULL,
  `observation` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `titre` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications_classes`
--

CREATE TABLE `notifications_classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `idnotification` int(11) UNSIGNED NOT NULL,
  `idclasse` int(11) UNSIGNED NOT NULL,
  `afficher` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parties`
--

CREATE TABLE `parties` (
  `id` int(10) UNSIGNED NOT NULL,
  `partie` varchar(100) NOT NULL DEFAULT '',
  `idmodule` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `niv` int(11) NOT NULL DEFAULT 1,
  `num` varchar(25) NOT NULL DEFAULT '',
  `devoir` tinyint(1) NOT NULL DEFAULT 0,
  `observation` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `seances`
--

CREATE TABLE `seances` (
  `id` int(10) UNSIGNED NOT NULL,
  `idclasse` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `heured` time NOT NULL,
  `observation` mediumtext DEFAULT NULL,
  `print` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `seances_eleves`
--

CREATE TABLE `seances_eleves` (
  `id` int(10) UNSIGNED NOT NULL,
  `idseance` int(11) UNSIGNED NOT NULL,
  `ideleve` int(11) UNSIGNED NOT NULL,
  `absent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `justify` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `seances_parties`
--

CREATE TABLE `seances_parties` (
  `id` int(10) UNSIGNED NOT NULL,
  `idseance` int(11) UNSIGNED NOT NULL,
  `idpartie` int(11) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `tag` varchar(250) NOT NULL,
  `color` varchar(250) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `types_examens`
--

CREATE TABLE `types_examens` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `libellefr` varchar(50) NOT NULL,
  `libellear` varchar(50) NOT NULL,
  `idmodule` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `ramadan` tinyint(4) NOT NULL DEFAULT 0,
  `email` varchar(100) NOT NULL DEFAULT '',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `ai_appreciations`
--
ALTER TABLE `ai_appreciations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_record_scope_period` (`idacademicrecords`,`scope`,`period_key`),
  ADD KEY `idx_ai_idacademicrecords` (`idacademicrecords`);

--
-- Index pour la table `annees`
--
ALTER TABLE `annees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `annee` (`annee`);

--
-- Index pour la table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_classes_idannee` (`idannee`),
  ADD KEY `idx_classes_classe` (`classe`);

--
-- Index pour la table `dossiers_scolaires`
--
ALTER TABLE `dossiers_scolaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ideleve_idannee` (`ideleve`,`idannee`),
  ADD KEY `idx_classes_idannee` (`idannee`),
  ADD KEY `idx_classes_eleve` (`ideleve`);

--
-- Index pour la table `eleves`
--
ALTER TABLE `eleves`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numerosgs` (`numerosgs`),
  ADD KEY `idx_eleves_numerosgs` (`numerosgs`);

--
-- Index pour la table `eleves_classes`
--
ALTER TABLE `eleves_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ideleve_idclasse` (`ideleve`,`idclasse`),
  ADD KEY `idx_eleveclasse_idclasse` (`idclasse`),
  ADD KEY `idx_eleveclasse_ideleve` (`ideleve`);

--
-- Index pour la table `eleves_tags`
--
ALTER TABLE `eleves_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idtag_ideleve` (`idtag`,`ideleve`),
  ADD KEY `idx_tageleve_idtag` (`idtag`),
  ADD KEY `idx_tageleve_ideleve` (`ideleve`);

--
-- Index pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jour_heure` (`n`,`heure`),
  ADD KEY `FK_edt_classes` (`idclasse`);

--
-- Index pour la table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_modules_module` (`module`);

--
-- Index pour la table `notebook_scores`
--
ALTER TABLE `notebook_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idacademicrecords_idmodule` (`idacademicrecords`,`idmodule`),
  ADD KEY `FK_notebook_scores_modules` (`idmodule`),
  ADD KEY `idx_notebook_academic` (`idacademicrecords`);

--
-- Index pour la table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idacademicrecords_idtypeexamen` (`idacademicrecords`,`idtypeexamen`),
  ADD KEY `idx_academicrecords` (`idacademicrecords`),
  ADD KEY `FK_noteannee_type` (`idtypeexamen`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `titre` (`titre`),
  ADD KEY `idx_notifications_titre` (`titre`);

--
-- Index pour la table `notifications_classes`
--
ALTER TABLE `notifications_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idnotification_idclasse` (`idnotification`,`idclasse`),
  ADD KEY `idx_notificationclasse_idnotification` (`idnotification`),
  ADD KEY `idx_notificationclasse_idclasse` (`idclasse`);

--
-- Index pour la table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parties_idmodule` (`idmodule`);

--
-- Index pour la table `seances`
--
ALTER TABLE `seances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idclasse_date_heured` (`idclasse`,`date`,`heured`),
  ADD UNIQUE KEY `uk_seance_unique` (`date`,`heured`),
  ADD KEY `idx_seances_idclasse` (`idclasse`),
  ADD KEY `idx_seances_date` (`date`),
  ADD KEY `idx_seances_classe_date` (`idclasse`,`date`);

--
-- Index pour la table `seances_eleves`
--
ALTER TABLE `seances_eleves`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idseance_ideleve` (`idseance`,`ideleve`),
  ADD KEY `idx_seanceeleve_idseance` (`idseance`),
  ADD KEY `idx_seanceeleve_ideleve` (`ideleve`);

--
-- Index pour la table `seances_parties`
--
ALTER TABLE `seances_parties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idseance_idpartie` (`idseance`,`idpartie`),
  ADD KEY `idx_seancepartie_idseance` (`idseance`),
  ADD KEY `idx_seancepartie_idpartie` (`idpartie`);

--
-- Index pour la table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag` (`tag`),
  ADD KEY `idx_tags_tag` (`tag`);

--
-- Index pour la table `types_examens`
--
ALTER TABLE `types_examens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_typeexamen_code` (`code`),
  ADD KEY `FK_types_examens_modules` (`idmodule`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `ai_appreciations`
--
ALTER TABLE `ai_appreciations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `annees`
--
ALTER TABLE `annees`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `dossiers_scolaires`
--
ALTER TABLE `dossiers_scolaires`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `eleves`
--
ALTER TABLE `eleves`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `eleves_classes`
--
ALTER TABLE `eleves_classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `eleves_tags`
--
ALTER TABLE `eleves_tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notebook_scores`
--
ALTER TABLE `notebook_scores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications_classes`
--
ALTER TABLE `notifications_classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `seances`
--
ALTER TABLE `seances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `seances_eleves`
--
ALTER TABLE `seances_eleves`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `seances_parties`
--
ALTER TABLE `seances_parties`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `types_examens`
--
ALTER TABLE `types_examens`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `ai_appreciations`
--
ALTER TABLE `ai_appreciations`
  ADD CONSTRAINT `fk_ai_dossiers` FOREIGN KEY (`idacademicrecords`) REFERENCES `dossiers_scolaires` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `FK_classes_annees` FOREIGN KEY (`idannee`) REFERENCES `annees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `dossiers_scolaires`
--
ALTER TABLE `dossiers_scolaires`
  ADD CONSTRAINT `FK_dossiers_annees` FOREIGN KEY (`idannee`) REFERENCES `annees` (`id`),
  ADD CONSTRAINT `FK_dossiers_eleves` FOREIGN KEY (`ideleve`) REFERENCES `eleves` (`id`);

--
-- Contraintes pour la table `eleves_classes`
--
ALTER TABLE `eleves_classes`
  ADD CONSTRAINT `FK_eleves_classes_classes` FOREIGN KEY (`idclasse`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `FK_eleves_classes_eleves` FOREIGN KEY (`ideleve`) REFERENCES `eleves` (`id`);

--
-- Contraintes pour la table `eleves_tags`
--
ALTER TABLE `eleves_tags`
  ADD CONSTRAINT `FK_eleves_tags_eleves` FOREIGN KEY (`ideleve`) REFERENCES `eleves` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_eleves_tags_tags` FOREIGN KEY (`idtag`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  ADD CONSTRAINT `FK_edt_classes` FOREIGN KEY (`idclasse`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `notebook_scores`
--
ALTER TABLE `notebook_scores`
  ADD CONSTRAINT `FK_notebook_dossiers` FOREIGN KEY (`idacademicrecords`) REFERENCES `dossiers_scolaires` (`id`),
  ADD CONSTRAINT `FK_notebook_scores_modules` FOREIGN KEY (`idmodule`) REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `FK_noteannee_type` FOREIGN KEY (`idtypeexamen`) REFERENCES `types_examens` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_notes_dossiers` FOREIGN KEY (`idacademicrecords`) REFERENCES `dossiers_scolaires` (`id`);

--
-- Contraintes pour la table `notifications_classes`
--
ALTER TABLE `notifications_classes`
  ADD CONSTRAINT `notifications_classes_ibfk_1` FOREIGN KEY (`idnotification`) REFERENCES `notifications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_classes_ibfk_2` FOREIGN KEY (`idclasse`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `parties`
--
ALTER TABLE `parties`
  ADD CONSTRAINT `FK_parties_modules` FOREIGN KEY (`idmodule`) REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `seances`
--
ALTER TABLE `seances`
  ADD CONSTRAINT `FK_seances_classes` FOREIGN KEY (`idclasse`) REFERENCES `classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `seances_eleves`
--
ALTER TABLE `seances_eleves`
  ADD CONSTRAINT `FK_seances_eleves_eleves` FOREIGN KEY (`ideleve`) REFERENCES `eleves` (`id`),
  ADD CONSTRAINT `FK_seances_eleves_seances` FOREIGN KEY (`idseance`) REFERENCES `seances` (`id`);

--
-- Contraintes pour la table `seances_parties`
--
ALTER TABLE `seances_parties`
  ADD CONSTRAINT `FK_seances_parties_parties` FOREIGN KEY (`idpartie`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `FK_seances_parties_seances` FOREIGN KEY (`idseance`) REFERENCES `seances` (`id`);

--
-- Contraintes pour la table `types_examens`
--
ALTER TABLE `types_examens`
  ADD CONSTRAINT `FK_types_examens_modules` FOREIGN KEY (`idmodule`) REFERENCES `modules` (`id`);
COMMIT;