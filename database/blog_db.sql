-- Database dump for blog_db

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Users table
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `phone` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT 'Writer & Content Author',
  `bio` text DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user` (`id`, `username`, `email`, `password`, `role`, `phone`, `location`, `designation`, `bio`, `facebook`, `linkedin`, `twitter`, `github`, `profile_pic`, `remember_token`) VALUES
(1, 'Dul', 'dul@gmail.com', '$2y$10$1VHyrnzP8KwdqZUsbScM6eeI/8hwvktzdmo1HHBs/176xTxXQHYtG', 'user', '', '', 'Writer & Content Author', '', '', 'https://www.linkedin.com/in/dulmini-megasooriya-a026a7320/', '', '', NULL, NULL),
(2, 'Graiz', 'graiz@gmail.com', '$2y$10$KA91WyTIe3qlffIHxfvs.ewauzfM2H9PEOWwYYtzevGFTL2NniU8C', 'user', NULL, NULL, 'Writer & Content Author', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- User profiles table
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL UNIQUE,
  `designation` varchar(100) DEFAULT 'Writer & Content Author',
  `phone` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_profiles` (`id`, `user_id`, `designation`, `phone`, `location`, `bio`, `profile_pic`, `facebook`, `linkedin`, `twitter`, `github`, `updated_at`) VALUES
(1, 1, 'Writer & Content Author', '', '', '', 'avatar_1_1785737322.png', '', '', '', '', '2026-08-03 06:08:42'),
(20, 2, 'Writer & Content Author', '', '', '', 'avatar_2_1785744441.jpg', '', '', '', '', '2026-08-03 08:07:21');

-- Blog posts table
CREATE TABLE IF NOT EXISTS `blogpost` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  CONSTRAINT `blogpost_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `blogpost` (`id`, `user_id`, `title`, `content`, `created_at`, `updated_at`, `image`) VALUES
(2, 1, 'My First Blog', 'This is my first blog page', '2026-07-15 12:54:14', '2026-07-15 12:54:31', '1784120054_images.jpg'),
(3, 1, 'Feel the inspired nature', 'Its inspiring that the nature created like this.', '2026-07-15 13:11:35', '2026-08-18 17:21:37', 'blog_6a57f52da49a48.75368312.jpg'),
(4, 1, 'Animals are lives', 'Animals are living beings in this nature not only humans.', '2026-07-15 13:12:47', '2026-08-18 17:21:37', '1784120054_images.jpg'),
(6, 1, 'Moment with Coffee', 'Coffee is actually a fruit, and the "beans" we grind to make our morning brew are entirely the pit (or seed) found inside bright red or yellow berries called coffee cherries.\r\n\r\nBeyond its botanical secrets, the history, chemistry, and culture behind coffee are packed with surprising anomalies.', '2026-07-15 20:42:09', '2026-07-15 20:42:09', 'blog_6a57f0a1ad72c6.04165665.jpg'),
(7, 1, 'Culture of Korea', 'Korean culture is a dynamic blend of ancient traditions and rapid modernization. Deeply rooted in Confucian principles, society highly values community, family, and deep respect for elders. Today, it is also a global juggernaut driven by the Hallyu (Korean Wave), encompassing internationally celebrated K-pop, K-dramas, skincare, and cuisine.', '2026-07-15 20:48:43', '2026-07-15 20:57:26', 'blog_6a57f436e9a2e6.53109798.jpg'),
(8, 1, 'Pearl of the Indian ocean', 'Sri Lanka is home to beautiful, untranslatable words that perfectly capture its island soul. One such word is “Ayubowan,” the traditional greeting that translates to "may you live a long and healthy life," spoken with pressed palms and a gentle bow.', '2026-07-15 21:01:33', '2026-07-15 21:01:48', 'blog_6a57f52da49a48.75368312.jpg'),
(9, 1, 'Friends', 'All over the life friends there with us to face anything.', '2026-07-15 21:03:19', '2026-07-15 21:03:19', 'blog_6a57f5975440a0.19972536.jpg'),
(12, 1, 'Daily Productivity & Mindful Habits', 'Finding balance in a busy day isn\'t about cramming more tasks into your calendar it\'s about focusing on what truly matters. Small, consistent steps like taking brief morning walks, prioritizing your top three goals, and taking structured breaks can transform your daily energy and focus. Start small, stay consistent, and give yourself space to recharge.', '2026-08-03 08:45:03', '2026-08-03 08:58:42', 'blog_6a705754b28f39.02868986.jpg'),
(13, 1, 'The Art of Crafting', 'Writing code is easy, but writing clean code that your future self and teammates will appreciate is an art. Focus on naming variables clearly, keeping functions short and focused, and leaving meaningful documentation. Good architecture saves countless hours of debugging down the road and makes building new features a joyful process.', '2026-08-03 08:45:03', '2026-08-03 08:57:36', 'blog_6a7057a38f9a64.17170475.jpg');

-- Blog reactions table
CREATE TABLE IF NOT EXISTS `blog_reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` varchar(20) DEFAULT 'like',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY `user_blog_unique` (`user_id`,`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `blog_reactions` (`id`, `blog_id`, `user_id`, `reaction_type`, `created_at`) VALUES
(2, 3, 1, 'like', '2026-08-02 10:39:06'),
(5, 7, 1, 'like', '2026-08-02 10:39:06'),
(10, 2, 2, 'like', '2026-08-03 08:09:28'),
(11, 3, 2, 'like', '2026-08-03 08:12:10'),
(12, 9, 2, 'like', '2026-08-03 08:13:07'),
(13, 9, 1, 'like', '2026-08-03 08:13:49');

-- Reviews table
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL DEFAULT 5,
  `review_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reviews` (`id`, `blog_id`, `user_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 2, 1, 5, 'Act Like It is a brilliant, deeply captivating article. Highly recommended', '2026-08-02 10:39:06'),
(2, 3, 1, 5, 'Alone On The Wall provides such an inspiring perspective on nature and resilience.', '2026-08-02 10:39:06'),
(3, 4, 1, 5, 'The Painter\'s Daughter is exquisitely written with rich details and wonderful insights.', '2026-08-02 10:39:06'),
(4, 6, 1, 4, 'Alex Ferguson: My Autobiography is a fantastic read for sport and leadership enthusiasts.', '2026-08-02 10:39:06'),
(6, 9, 2, 3, 'Fantastic', '2026-08-03 08:12:55');
