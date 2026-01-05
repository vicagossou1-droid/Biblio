-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 23 déc. 2025 à 15:40
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ma_bibliothèque`
--

-- --------------------------------------------------------

--
-- Structure de la table `auteurs`
--

CREATE TABLE `auteurs` (
  `auteur_id` int(11) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `biographie` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `auteurs`
--

INSERT INTO `auteurs` (`auteur_id`, `prenom`, `nom`, `biographie`) VALUES
(1, 'George', 'Orwell', 'George Orwell (1903-1950) était un écrivain et journaliste britannique célèbre pour son engagement contre l\'oppression et le totalitarisme.'),
(2, 'Robert C.', 'Martin', 'Robert Cecil Martin est un ingénieur logiciel et auteur américain. Il est né le 5 décembre 1952. Il est co-auteur du Manifeste Agile. Il dirige maintenant la société de conseil Uncle Bob Consulting LLC et le site web Clean Coders, qui héberge des vidéos basées sur son expérience et ses publications. Wikipédia\nDate/Lieu de naissance : 1952 (Âge: 73 ans), Palo Alto, Californie, États-Unis\nEnfants : Micah Martin'),
(4, 'Moïse O.', 'Inadjo', 'Moïse Inandjo de son vrai nom à l\'état civil Olouwadara Inandjo, né le 20 juillet 1981 à Kaboli dans la préfecture de Tchamba au Togo, est un écrivain, romancier, nouvelliste et essayiste togolais.'),
(5, 'Steve', 'Bodjona', 'Steve Bodjona est un diplomate et écrivain togolais. Passionné de littérature depuis l\'enfance, il est l\'auteur de plus de 25 ouvrages. Son livre \"Des larmes au crépuscule\" est d\'ailleurs inscrit au programme scolaire au Togo.'),
(6, 'Marthe', 'Fare', 'Marthe Fare est une journaliste, écrivaine et blogueuse togolaise reconnue. Elle est une voix importante de la littérature contemporaine au Togo, engagée sur les questions de société.'),
(7, 'Chinua', 'Achebe', 'Chinua Achebe (1930-2013) était un écrivain nigérian, souvent considéré comme le père de la littérature africaine moderne en langue anglaise.'),
(8, 'Mariama', 'Bâ', 'Mariama Bâ (1929-1981) était une femme de lettres sénégalaise dont l\'œuvre dénonce les injustices subies par les femmes en Afrique.'),
(9, 'Paulo', 'Coelho', 'Paulo Coelho est un romancier brésilien mondialement connu. Ses livres, traduits dans des dizaines de langues, explorent souvent des thèmes spirituels et métaphysiques.'),
(10, 'Sami', 'Tchak', 'Né en 1960 au Togo, Sami Tchak est un écrivain, essayiste et sociologue de renommée internationale. Après une licence de philosophie à l\'Université de Lomé, il obtient un doctorat en sociologie à la Sorbonne. Son œuvre, riche et audacieuse, explore les complexités de la condition humaine, de la sexualité et des rapports entre l\'Afrique et l\'Occident.'),
(11, 'Kangni', 'Alem', 'Kangni Alem est un écrivain, dramaturge et universitaire togolais. Il a reçu le Grand Prix Littéraire d\'Afrique Noire pour l\'ensemble de son œuvre.'),
(12, 'Chimamanda Ngozi', 'Adichie', 'Écrivaine nigériane de renommée mondiale, elle est devenue une icône du féminisme contemporain et de la nouvelle voix littéraire africaine.'),
(13, 'Gaël', 'Faye', 'Gaël Faye est un auteur-compositeur-interprète et écrivain franco-rwandais. \"Petit Pays\" a été un immense succès critique et public, adapté au cinéma.'),
(14, 'Albert', 'Camus', 'Albert Camus était un écrivain et philosophe français né en Algérie. Il a reçu le Prix Nobel de littérature pour son œuvre qui analyse la condition humaine.'),
(15, 'Yuval Noah', 'Harari', 'Historien israélien, il est devenu l\'un des penseurs les plus influents du XXIe siècle grâce à ses analyses sur l\'avenir de l\'homme.'),
(16, 'Mouloud', 'Feraoun', 'Écrivain algérien né en 1913, assassiné en 1962. Il a consacré son œuvre à la description de la vie rurale et à l\'importance de l\'éducation.'),
(17, 'Félix', 'Couchoro', 'Né en 1900 et décédé en 1968, Félix Couchoro est considéré comme le véritable père des lettres togolaises. Écrivain prolifique (plus de 20 romans), il a passé une grande partie de sa vie entre le Bénin et le Togo.'),
(18, 'Seydou', 'Badian', 'Écrivain et homme politique malien (1928-2018). Il est l\'auteur de l\'hymne national du Mali et l\'une des figures majeures de la littérature africaine post-indépendance.'),
(19, 'Camara', 'Laye', 'Camara Laye (1928-1980) était un écrivain guinéen. Ce livre est l\'un des plus célèbres de la littérature africaine francophone et reste au programme de nombreux collèges en Afrique.'),
(20, 'Jean', 'Anouilh', 'Dramaturge français (1910-1987). Il a réécrit ce mythe grec en 1944 pour parler de la résistance et du refus du compromis politique.'),
(21, 'Amadou', 'Koné', 'Écrivain ivoirien né en 1953. Ce livre est extrêmement populaire dans les établissements secondaires pour les thématiques morales qu\'il aborde.');

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `avis_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `livre_id` int(11) NOT NULL,
  `note` int(11) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `cree_le` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `categorie_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`categorie_id`, `nom`) VALUES
(1, 'Science-Fiction'),
(2, 'Développement Personnel'),
(3, 'Informatique'),
(4, 'Roman Social / Drame'),
(5, 'Roman / Fiction'),
(6, 'Classique Africain / Historique'),
(7, 'Roman Épistolaire / Féminisme'),
(8, 'Conte Philosophique / Aventure'),
(9, 'Science-Fiction / Dystopie'),
(10, 'Roman / Société'),
(11, 'Roman / Drame Historique'),
(12, 'Roman / Immigration / Romance'),
(13, 'Roman / Récit de formation'),
(14, 'Philosophie / Absurde'),
(15, 'Essai / Histoire / Sciences'),
(16, 'Roman / Autobiographie'),
(17, 'Roman / Drame Social'),
(18, 'Roman / Conflit des générations'),
(19, 'Récit Autobiographique'),
(20, 'Théâtre / Tragédie Moderne'),
(21, 'Roman / Jeunesse'),
(22, 'Roman / Littérature contemporaine'),
(23, 'Roman classique / Spiritualité et Morale');

-- --------------------------------------------------------

--
-- Structure de la table `emprunts`
--

CREATE TABLE `emprunts` (
  `emprunt_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `livre_id` int(11) NOT NULL,
  `date_emprunt` date DEFAULT curdate(),
  `date_echeance` date NOT NULL,
  `statut` varchar(20) DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `emprunts`
--

INSERT INTO `emprunts` (`emprunt_id`, `utilisateur_id`, `livre_id`, `date_emprunt`, `date_echeance`, `statut`) VALUES
(4, 2, 4, '2025-12-20', '2026-01-10', 'rendu'),
(7, 3, 4, '2025-12-20', '2025-12-30', 'rendu'),
(11, 7, 4, '2025-12-20', '2025-12-30', 'rendu'),
(14, 4, 13, '2025-12-22', '2026-02-01', 'rendu'),
(15, 4, 10, '2025-12-22', '2026-01-01', 'en_cours'),
(16, 4, 20, '2025-12-22', '2026-01-01', 'en_cours'),
(17, 8, 21, '2025-12-22', '2026-01-01', 'en_cours'),
(18, 8, 17, '2025-12-22', '2026-01-01', 'en_cours'),
(19, 4, 13, '2025-12-23', '2026-01-02', 'en_cours');

-- --------------------------------------------------------

--
-- Structure de la table `frais`
--

CREATE TABLE `frais` (
  `frais_id` int(11) NOT NULL,
  `emprunt_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `raison` varchar(255) DEFAULT NULL,
  `genere_le` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut` varchar(20) DEFAULT 'non_paye',
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `frais`
--

INSERT INTO `frais` (`frais_id`, `emprunt_id`, `montant`, `raison`, `genere_le`, `statut`, `date_creation`) VALUES
(6, 16, 500.00, 'retards', '2025-12-23 13:21:47', 'payé', '2025-12-23 13:21:47'),
(7, 19, 200.00, 'retards', '2025-12-23 13:35:25', 'payé', '2025-12-23 13:35:25');

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

CREATE TABLE `livres` (
  `livre_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `auteur_id` int(11) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `maison_edition` varchar(255) DEFAULT NULL,
  `date_publication` date DEFAULT NULL,
  `total_exemplaires` int(11) DEFAULT 1,
  `exemplaires_disponibles` int(11) DEFAULT 1,
  `url_image_couverture` varchar(255) DEFAULT NULL,
  `cree_le` timestamp NOT NULL DEFAULT current_timestamp(),
  `resume` longtext DEFAULT NULL
) ;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`livre_id`, `titre`, `auteur_id`, `categorie_id`, `maison_edition`, `date_publication`, `total_exemplaires`, `exemplaires_disponibles`, `url_image_couverture`, `cree_le`, `resume`) VALUES
(4, 'Sur les routes sanglantes de l\'exile', 4, 1, NULL, NULL, 6, 6, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQFB3NA78Yv4VNmCTCVCPDGoVHtaagaR9tXTg&s', '2025-12-16 16:08:32', 'rtyuieusdikfxmc.s d'),
(5, 'Des larmes au crépuscule', 5, 4, NULL, NULL, 15, 15, 'https://librairiemirev.com/wp-content/uploads/2022/07/155-Des-larmes-au-crepuscule-scaled.jpg', '2025-12-20 23:12:30', 'Ce roman aborde avec sensibilité la thématique de la prostitution et ses conséquences dévastatrices sur la jeunesse. Il suit le parcours de personnages confrontés aux dures réalités sociales du pays.'),
(6, 'La Sirène des bas-fonds', 6, 5, NULL, NULL, 13, 13, 'https://benjaminseyram.home.blog/wp-content/uploads/2019/08/blueroses_2019824162329336-1.jpg', '2025-12-20 23:14:46', 'Une œuvre qui explore les complexités de la vie urbaine à Lomé, mêlant intrigues, désirs et réalités sociales à travers le portrait de personnages féminins forts.'),
(7, 'Tout s\'effondre (Things Fall Apart)', 7, 6, 'qqw', '0000-00-00', 11, 11, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSPe0-qJvqJJ3U61_OhmJdGSYDyVhxae4cOhg&s', '2025-12-20 23:21:52', 'Le roman raconte l\'histoire d\'Okonkwo, un chef de clan nigérian dont le monde bascule avec l\'arrivée des missionnaires européens et de l\'administration coloniale. Un chef-d\'œuvre sur le choc des cultures.'),
(8, 'Une si longue lettre', 8, 7, NULL, NULL, 18, 18, 'https://m.media-amazon.com/images/I/51LEhC5AXpL._SX195_.jpg', '2025-12-20 23:23:14', 'Ramatoulaye écrit à son amie Aïssatou pour lui confier ses peines et ses réflexions sur la condition de la femme, la polygamie et la trahison après le décès de son mari.'),
(9, 'L\'Alchimiste', 9, 8, NULL, NULL, 15, 15, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT8gwjjBf64Sld5MsAc165uW9whq8Cm6EfXxA&s', '2025-12-20 23:24:50', 'L\'histoire de Santiago, un jeune berger andalou qui part à la recherche d\'un trésor enfoui au pied des pyramides d\'Égypte. Un voyage spirituel sur la poursuite de sa \"Légende Personnelle\".'),
(10, '1984', 1, 9, NULL, NULL, 14, 13, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTB1sWr4sn8YtSOh3imkYafuZgE4Z_QXQlfrg&s', '2025-12-20 23:26:19', 'Dans un monde sous surveillance constante dirigé par le \"Big Brother\", Winston Smith tente de se rebeller contre un régime totalitaire qui contrôle les actes et les pensées.'),
(11, 'Le rendez-vous de la Saint-Sylvestre', 10, 10, NULL, NULL, 23, 23, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTlSeYsAmn3urjYUUtmBJxx-XEv1LOp35wpRg&s', '2025-12-20 23:28:28', '•	Un récit puissant qui explore les désillusions, les rapports de force et les réalités sociales du Togo et de l\'Afrique à travers une plume audacieuse et sans tabou.'),
(12, 'Esclaves', 11, 11, NULL, NULL, 32, 32, 'https://imgv2-2-f.scribdassets.com/img/document/672049308/original/3aa010ab1f/1?v=1', '2025-12-20 23:29:45', '•	Ce roman mêle fiction et réalités politiques. Il dépeint avec ironie et finesse les travers du pouvoir et les espoirs d\'une population face aux changements de l\'histoire.'),
(13, 'Americanah', 12, 12, NULL, NULL, 21, 20, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQsJFhc7a9ghyysW_5IcLZotNGnExGVw1JnMA&s', '2025-12-20 23:31:08', 'Ifemelu et Obinze sont amoureux au Nigeria. Ifemelu part étudier aux États-Unis, où elle découvre le racisme et les nuances de l\'identité noire. Un portrait magistral de la diaspora africaine moderne.'),
(14, 'Petit Pays', 13, 13, NULL, NULL, 43, 43, 'https://m.media-amazon.com/images/I/91WgG6YNwRL.jpg', '2025-12-20 23:32:32', '•	À travers les yeux du jeune Gabriel, le livre raconte la fin de l\'insouciance au Burundi au moment où la guerre civile et le génocide des Tutsi au Rwanda voisin éclatent.'),
(15, 'L\'Étranger', 14, 14, NULL, NULL, 19, 19, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRW-IF9NTxpxYNCwh1QehzwEJGZ6qlSOZI3Zw&s', '2025-12-20 23:34:51', '\"Aujourd\'hui, maman est morte. Ou peut-être hier, je ne sais pas.\" Meursault, un homme qui semble étranger au monde et à ses propres émotions, commet un meurtre et assiste à son propre procès avec indifférence.'),
(16, 'Sapiens : Une brève histoire de l\'humanité', 15, 15, NULL, NULL, 1, 1, 'https://www.hominides.com/wp-content/uploads/2022/08/une-breve-histoire-de-l-humanite.jpg', '2025-12-20 23:36:16', 'Comment notre espèce a-t-elle réussi à dominer la Terre ? De l\'âge de pierre à l\'ère de la Silicon Valley, Harari retrace l\'évolution de l\'humanité avec une perspective fascinante.'),
(17, 'Le Fils du pauvre', 16, 16, NULL, NULL, 24, 23, 'https://m.media-amazon.com/images/I/81lY-JKDNSL._AC_UF894,1000_QL80_.jpg', '2025-12-20 23:39:01', 'Ce récit raconte l\'enfance de Fouroulou en Kabylie, un fils de paysan qui, grâce à l\'école, parvient à s\'élever socialement. Une œuvre sur la persévérance et le choc des cultures.'),
(19, 'Sous l\'orage', 18, 18, NULL, NULL, 31, 31, 'https://www.presenceafricaine.com/840-large_default/sous-l-orage-la-mort-de-chaka.jpg', '2025-12-20 23:41:41', 'L\'histoire suit Kany et Samou, deux jeunes amoureux qui veulent se marier, mais font face à l\'opposition du père de Kany qui préfère un mariage traditionnel. Un duel entre modernité et tradition.'),
(20, 'L\'Enfant noir', 19, 19, NULL, NULL, 42, 41, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTKAeYZEDsJaTs-zFb3DsvEJWWqpx7skk9Dnw&s', '2025-12-20 23:43:03', '•	L\'auteur raconte son enfance heureuse en Guinée, entre la forge de son père, les mystères des traditions africaines et son départ vers la France pour poursuivre ses études.'),
(21, 'Antigone', 20, 20, NULL, NULL, 34, 33, 'https://images.leslibraires.ca/books/9782710381419/front/9782710381419_large.webp', '2025-12-20 23:44:18', 'Antigone refuse d\'obéir à l\'ordre du roi Créon qui interdit d\'enterrer son frère. Elle choisit de mourir pour rester fidèle à ses convictions. Une réflexion sur le pouvoir et la révolte.'),
(22, 'Les Frasques d\'Ebinto', 21, 21, NULL, NULL, 1, 1, 'https://ecx.images-amazon.com/images/I/41MOE2gcjnL._SX210_.jpg', '2025-12-20 23:46:00', '•	Ebinto est un jeune homme brillant dont l\'avenir est brisé après avoir mis une jeune fille enceinte. Le roman explore les thèmes du remords, de la responsabilité et de la fatalité.'),
(23, 'Mélodie pour une douleur', 10, 22, 'Éditions Continents', '2024-01-01', 17, 17, 'https://www.mediatheques.grasse.fr/images/com_droppics/179/melodie-pour-une-douleur.jpg?1573816434', '2025-12-22 21:30:30', 'Ce livre a reçu le Prix international de littérature Cheikh Hamidou Kane en 2024. À travers une écriture profonde et poétique, l\'auteur explore la condition humaine, les souvenirs et la quête de sens. C\'est une œuvre magistrale d\'un des auteurs togolais les plus célèbres à l\'international.'),
(24, 'L\'Esclave', 17, 23, 'Éditions Akpagnon', '0000-00-00', 21, 21, 'https://togolitteraire.haverford.edu/LE_TOGO_LITTERAIRE/COUCHORO,_F._%282%29_files/Couchoro.jpg', '2025-12-22 21:48:17', 'Bien que ce soit un drame social, la question de la justice divine, de la Providence et de la foi traverse toute l\'œuvre. L\'auteur, très imprégné par sa foi chrétienne, explore comment l\'homme se tourne vers Dieu face à l\'injustice humaine et à la souffrance. C\'est un livre où la morale et la présence de Dieu sont les piliers de la narration.');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `livre_id` int(11) NOT NULL,
  `date_reservation` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut` varchar(20) DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `nom_role` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`role_id`, `nom_role`, `description`) VALUES
(1, 'admin', 'Accès total à la gestion'),
(2, 'membre', 'Peut emprunter et réserver des livres');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `utilisateur_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe_hash` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_adhesion` date DEFAULT curdate(),
  `est_actif` tinyint(1) DEFAULT 1,
  `cree_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`utilisateur_id`, `role_id`, `prenom`, `nom`, `email`, `mot_de_passe_hash`, `telephone`, `date_adhesion`, `est_actif`, `cree_le`) VALUES
(1, 1, 'User', 'Admin', 'admin@gmail.com', '$2y$10$U3CVkazweoIyZAxVHRMW5eHR84C2XDFwaDJ5zqQZ9fWuJQxvTrYZS', NULL, '2025-12-16', 1, '2025-12-16 11:30:59'),
(2, 2, 'Geoffroy', 'AGBOKOU', 'geoffroyagbokou0@gmail.com', '$2y$10$tvH16PYscmdDwECB1yy8je3/EWTGDBKksNPSsS0wBBI7XYJ.O.iSK', NULL, '2025-12-16', 1, '2025-12-16 11:30:59'),
(3, 2, 'Jash', 'VIC', 'vicjash@gmail.com', '$2y$10$qTf6rjJOH8R.08rCfDEOyuciZk4ZZc2.zbPUAQsrxfM7NW1pMGSY.', NULL, '2025-12-18', 1, '2025-12-18 17:23:44'),
(4, 2, 'Hope', 'SAMA', 'hopesama@gmail.com', '$2y$10$ZF6/0hYO6d6ISgpgPJL0JuibNGfiXeue8yj.1zqItz4EE17nXf9UC', NULL, '2025-12-19', 1, '2025-12-19 17:35:46'),
(5, 2, 'vic', 'vic', 'vic@gmail.com', '$2y$10$VhKXekZlb7.olRF0ks.DvufROPOhrK3huwuBBf9ykNfXfwoDg6NhK', NULL, '2025-12-20', 1, '2025-12-20 11:21:53'),
(6, 1, 'Man', 'MAN', 'man@gmail.com', '$2y$10$2JKRj7QHbJmqUS9h7/ep2OE2y7JUj4R9Tg4JvlwY/W9YbSzEa2Dvi', '+228-90000000', '2025-12-20', 1, '2025-12-20 11:26:23'),
(7, 2, 'erjkl', '3rtyio', 'tyui@gmail.com', '$2y$10$c6Dmr53dkEqHYOwqwFU0xO0YScd2iyoLwHiyBpUt7yiKaAK3P2rY6', NULL, '2025-12-20', 1, '2025-12-20 17:10:32'),
(8, 2, 'ytrewq', 'qwerty', 'qwerty@gmail.com', '$2y$10$rlWeg21ivrVmSkrVYpCfg.u.jz92up97xixsHcZEiBDdqY9othg1e', NULL, '2025-12-20', 1, '2025-12-20 20:52:58'),
(9, 2, 'erty', 'wer', 'S@gmail.com', '$2y$10$NzPmpNAdjwAkQwUB73C1oeMeVjO7pAXpfRzHha.IQAw0KIRbG2Hpm', NULL, '2025-12-20', 1, '2025-12-20 21:18:53'),
(10, 2, 'qwert', 'qwert', 'q@gmail.com', '$2y$10$3mJwUqWm0.uOgkvBdx78kuZsjYh1yhPcH4Ct/LKNmQzV8ZBwwLqui', '0690000000', '2025-12-22', 1, '2025-12-22 12:09:59');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `auteurs`
--
ALTER TABLE `auteurs`
  ADD PRIMARY KEY (`auteur_id`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`avis_id`),
  ADD KEY `fk_avis_user` (`utilisateur_id`),
  ADD KEY `fk_avis_livre` (`livre_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`categorie_id`);

--
-- Index pour la table `emprunts`
--
ALTER TABLE `emprunts`
  ADD PRIMARY KEY (`emprunt_id`),
  ADD KEY `fk_emprunt_user` (`utilisateur_id`),
  ADD KEY `fk_emprunt_livre` (`livre_id`),
  ADD KEY `idx_emprunts_statut` (`statut`);

--
-- Index pour la table `frais`
--
ALTER TABLE `frais`
  ADD PRIMARY KEY (`frais_id`),
  ADD KEY `fk_frais_emprunt` (`emprunt_id`);

--
-- Index pour la table `livres`
--
ALTER TABLE `livres`
  ADD PRIMARY KEY (`livre_id`),
  ADD KEY `fk_livre_auteur` (`auteur_id`),
  ADD KEY `fk_livre_categorie` (`categorie_id`),
  ADD KEY `idx_livres_titre` (`titre`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `fk_res_user` (`utilisateur_id`),
  ADD KEY `fk_res_livre` (`livre_id`),
  ADD KEY `idx_res_statut` (`statut`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `nom_role` (`nom_role`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`utilisateur_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_utilisateur_role` (`role_id`),
  ADD KEY `idx_utilisateurs_email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `auteurs`
--
ALTER TABLE `auteurs`
  MODIFY `auteur_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `avis_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `categorie_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `emprunts`
--
ALTER TABLE `emprunts`
  MODIFY `emprunt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `frais`
--
ALTER TABLE `frais`
  MODIFY `frais_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `livres`
--
ALTER TABLE `livres`
  MODIFY `livre_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `utilisateur_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_livre` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`livre_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avis_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emprunts`
--
ALTER TABLE `emprunts`
  ADD CONSTRAINT `fk_emprunt_livre` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`livre_id`),
  ADD CONSTRAINT `fk_emprunt_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`);

--
-- Contraintes pour la table `frais`
--
ALTER TABLE `frais`
  ADD CONSTRAINT `fk_frais_emprunt` FOREIGN KEY (`emprunt_id`) REFERENCES `emprunts` (`emprunt_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `livres`
--
ALTER TABLE `livres`
  ADD CONSTRAINT `fk_livre_auteur` FOREIGN KEY (`auteur_id`) REFERENCES `auteurs` (`auteur_id`),
  ADD CONSTRAINT `fk_livre_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`categorie_id`);

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_res_livre` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`livre_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_utilisateur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
