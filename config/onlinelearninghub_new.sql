-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2025 at 04:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onlinelearninghub_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `email`, `password`, `full_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@learninghub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '2025-11-13 13:57:36', '2025-11-13 13:57:36');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `author_type` enum('instructor','admin') NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `icon`, `color`, `status`, `created_at`) VALUES
(1, 'Web Development', 'Learn modern web technologies', 'fas fa-code', '#007bff', 'active', '2025-11-13 13:57:36'),
(2, 'Data Science', 'Data analysis and machine learning', 'fas fa-chart-bar', '#28a745', 'active', '2025-11-13 13:57:36'),
(3, 'Design', 'Graphic and UI/UX design', 'fas fa-paint-brush', '#dc3545', 'active', '2025-11-13 13:57:36'),
(4, 'Business', 'Business and entrepreneurship', 'fas fa-briefcase', '#ffc107', 'active', '2025-11-13 13:57:36'),
(5, 'Marketing', 'Digital marketing strategies', 'fas fa-bullhorn', '#17a2b8', 'active', '2025-11-13 13:57:36');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `certificate_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_code` varchar(50) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','revoked') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`certificate_id`, `student_id`, `course_id`, `certificate_code`, `issued_at`, `expires_at`, `status`) VALUES
(1, 1, 4, 'CERT-691A01833F4FA', '2025-11-16 16:53:23', NULL, 'active'),
(2, 1, 3, 'CERT-691A0E980A4D9', '2025-11-16 17:49:12', NULL, 'active'),
(3, 1, 2, 'CERT-691A0F05669E4', '2025-11-16 17:51:01', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `sender_role` enum('student','instructor','admin') NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `sender_id`, `sender_name`, `sender_role`, `message`, `timestamp`, `is_deleted`) VALUES
(1, 1, 'Nabin Neupane', 'instructor', 'hello student', '2025-11-13 14:02:14', 1),
(2, 1, 'Tilak Neupane', 'student', 'hello sir', '2025-11-13 15:17:31', 1),
(3, 1, 'Nabin Neupane', 'instructor', 'provide materials', '2025-11-13 15:18:59', 1),
(4, 2, 'Jhabilal', 'instructor', 'hello admin manage duplicate data', '2025-11-13 15:52:44', 0),
(5, 1, 'Tilak Neupane', 'student', 'hello sir', '2025-11-13 16:30:59', 0),
(6, 1, 'Tilak Neupane', 'student', 'i cannot acess', '2025-11-14 04:10:57', 0),
(7, 1, 'Tilak Neupane', 'student', '?', '2025-11-16 04:04:20', 0),
(8, 1, 'Nabin Neupane', 'instructor', 'students?', '2025-11-16 04:30:10', 0),
(9, 1, 'Tilak Neupane', 'student', 'completed!', '2025-11-17 06:04:22', 0);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `contact_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`contact_id`, `name`, `email`, `subject`, `message`, `status`, `sent_at`, `replied_at`) VALUES
(1, 'tilak neupane', 'tilak@gmail.com', NULL, 'hello ADmin', 'unread', '2025-11-17 03:50:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_hours` int(11) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `instructor_id`, `category_id`, `title`, `description`, `short_description`, `thumbnail`, `price`, `duration_hours`, `difficulty_level`, `status`, `featured`, `created_at`, `updated_at`) VALUES
(2, 2, NULL, 'Web Development', 'Web development is the process of building and maintaining websites and web applications, encompassing a wide range of tasks from coding and scripting to network security configuration and database management. It is broadly divided into two main areas: front-end (what the user sees and interacts with) and back-end (the server-side logic and databases).', NULL, NULL, 0.00, NULL, 'beginner', 'draft', 0, '2025-11-13 15:46:20', '2025-11-13 15:46:20'),
(3, 1, NULL, 'Introduction to Data Science & Analytics', 'Dive into data analysis, visualization, machine learning, and statistical modeling with Python and R.', NULL, NULL, 0.00, NULL, 'beginner', 'draft', 0, '2025-11-14 04:12:59', '2025-11-14 04:12:59'),
(4, 1, NULL, 'Artificial Intelligence', 'Artificial Intelligence (AI) is a technology that enables machines and computers to perform tasks that typically require human intelligence. It helps systems learn from data, recognize patterns and make decisions to solve complex problems.', NULL, NULL, 0.00, NULL, 'beginner', 'draft', 0, '2025-11-16 15:48:54', '2025-11-16 15:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `course_lessons`
--

CREATE TABLE `course_lessons` (
  `lesson_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` longtext DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `youtube_url` varchar(500) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `external_link` varchar(500) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_free` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_lessons`
--

INSERT INTO `course_lessons` (`lesson_id`, `unit_id`, `title`, `content`, `video_url`, `youtube_url`, `file_path`, `external_link`, `duration_minutes`, `order_index`, `is_free`, `created_at`, `updated_at`) VALUES
(6, 3, 'Overview of INTRODUCTION TO WEB DEVELOPMENT', 'Introduction and learning objectives for this unit.', NULL, NULL, NULL, NULL, 15, 1, 0, '2025-11-13 15:53:38', '2025-11-13 15:53:38'),
(13, 4, 'history of data science', '<p>&nbsp;</p>\r\n<div id=\"rd_gencon_ttc\" class=\"rd_sg_ttl rd_gencon_ttle rd_gencon_ttc devmag_ttl\">\r\n<h2 class=\"b_topTitle\"><a href=\"https://www.bing.com/ck/a?!&amp;&amp;p=547b7917f3668932ea20ce3f9f2b5947567fb8736e01f469136de26971b1baabJmltdHM9MTc2MzI1MTIwMA&amp;ptn=3&amp;ver=2&amp;hsh=4&amp;fclid=1dcbad95-4c90-668d-0f0a-bbf04d7c67b8&amp;psq=history+of+data+science&amp;u=a1aHR0cHM6Ly93d3cuZGF0YXZlcnNpdHkubmV0L2FydGljbGVzL2JyaWVmLWhpc3RvcnktZGF0YS1zY2llbmNlLw&amp;ntb=1\" target=\"_blank\" rel=\"noopener\">The Origin of Data Science</a></h2>\r\n</div>\r\n<div class=\"rd_gencon_attr\"><sup id=\"rd_gencon_ai_0\" class=\"rd_gencon_ai\"></sup>The concept of&nbsp;<strong>Data Science</strong>&nbsp;emerged in the early 1960s as a response to the growing need for analyzing and interpreting large volumes of data. Initially rooted in&nbsp;<strong>statistics</strong>, it evolved into a multidisciplinary field incorporating&nbsp;<strong>computer science</strong>,&nbsp;<strong>mathematics</strong>, and domain expertise to extract insights and make predictions.</div>\r\n<div id=\"rd_single_ttip\" class=\"rd_single_ttip\">\r\n<div class=\"rd_gencon_ttip\">\r\n<div class=\"rd_gencon_tta \">&nbsp;</div>\r\n</div>\r\n</div>\r\n<div id=\"devmag_card_content\" class=\"devmag_card_content\" data-dataurl=\"/RichDeveloper/Card?QueryType=Card&amp;TableNs=RichDevCardTable&amp;CardTable=Developer&amp;CardId=790b675994319a5195a365723549c956&amp;TabId=0&amp;TabContentId=&amp;TabContentSnippetId=&amp;IsGeneratedContent=True&amp;IsActiveTab=True&amp;IsMagazine=False&amp;IsACFAnswer=True\">\r\n<div>\r\n<p>In 1962, John Tukey highlighted the shift from traditional statistics to&nbsp;<strong>data analysis</strong>, emphasizing the integration of computers for solving mathematical problems. By 1974, Peter Naur introduced the term \"Data Science\" in his work, defining it as the process of building and handling models of reality using data. The establishment of the&nbsp;<strong>International Association for Statistical Computing (IASC)</strong>&nbsp;in 1977 further solidified the connection between statistics, computer technology, and domain knowledge.</p>\r\n</div>\r\n<div id=\"devmag_card_content_dynamic\" class=\"\" data-priority=\"2\">\r\n<div data-bm=\"75\">\r\n<div id=\"rdtb_3_2A27B1\" data-isactivetab=\"true\">\r\n<div id=\"rdtb_cnt_5_2A287A\" class=\" rd_tb_cnt_wp\" data-priority=\"\" data-dataurl=\"/RichDeveloper/Card?QueryType=Card&amp;TableNs=RichDevCardTable&amp;CardTable=Developer&amp;CardId=790b675994319a5195a365723549c956&amp;TabId=0&amp;TabContentId=0&amp;TabContentSnippetId=&amp;IsGeneratedContent=True&amp;IsActiveTab=True&amp;IsMagazine=False&amp;IsACFAnswer=False\" data-firstactive=\"1\">\r\n<div class=\"rd_tb_cnt\">\r\n<div>\r\n<p>The 1990s marked a turning point with the rise of&nbsp;<strong>big data</strong>&nbsp;and the need for scalable tools to handle massive datasets. In 1999, Jacob Zahavi stressed the limitations of conventional statistical methods for analyzing large-scale data, paving the way for&nbsp;<strong>data mining</strong>&nbsp;and specialized tools. By the early 2000s, the field expanded with innovations like&nbsp;<strong>Software-as-a-Service (SaaS)</strong>&nbsp;and the release of&nbsp;<strong>Hadoop</strong>, which revolutionized data storage and processing.</p>\r\n</div>\r\n<div>\r\n<p>The term \"data scientist\" gained prominence in 2008, popularized by professionals like DJ Patil and Jeff Hammerbacher. By 2011, job listings for data scientists surged, reflecting the growing demand for expertise in handling and analyzing data. The field continued to evolve with advancements in&nbsp;<strong>machine learning</strong>,&nbsp;<strong>artificial intelligence</strong>, and&nbsp;<strong>deep learning</strong>, as seen in Google\'s 2015 breakthroughs in speech recognition.</p>\r\n</div>\r\n<div>\r\n<p>Today, Data Science is a cornerstone of industries ranging from business and healthcare to astronomy and social sciences. It leverages&nbsp;<strong>big data</strong>,&nbsp;<strong>AI</strong>, and&nbsp;<strong>IoT</strong>&nbsp;to drive decision-making, optimize processes, and foster innovation. However, the field faces challenges, such as balancing incremental improvements with the need for bold, innovative approaches.</p>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n<div class=\"rd_cnt_srcs b_hide\" data-bm=\"76\">\r\n<div class=\"rd_attr_cntnr\">\r\n<div class=\"rd_attr_items\">&nbsp;</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n<div class=\"rdShadowClass\" data-priority=\"2\">&nbsp;</div>\r\n<div id=\"rdgenconseemore\" class=\"\" data-priority=\"2\">\r\n<div id=\"rd_card_sm_expand_gencon\" class=\"rd_card_sm\" data-priority=\"2\"></div>\r\n</div>', NULL, '', NULL, 'https://www.dataversity.net/articles/brief-history-data-science/', NULL, 1, 0, '2025-11-16 12:30:31', '2025-11-16 12:30:31'),
(14, 4, 'history of data science', '<p>&nbsp;</p>\r\n<div id=\"rd_gencon_ttc\" class=\"rd_sg_ttl rd_gencon_ttle rd_gencon_ttc devmag_ttl\">\r\n<h2 class=\"b_topTitle\"><a href=\"https://www.bing.com/ck/a?!&amp;&amp;p=547b7917f3668932ea20ce3f9f2b5947567fb8736e01f469136de26971b1baabJmltdHM9MTc2MzI1MTIwMA&amp;ptn=3&amp;ver=2&amp;hsh=4&amp;fclid=1dcbad95-4c90-668d-0f0a-bbf04d7c67b8&amp;psq=history+of+data+science&amp;u=a1aHR0cHM6Ly93d3cuZGF0YXZlcnNpdHkubmV0L2FydGljbGVzL2JyaWVmLWhpc3RvcnktZGF0YS1zY2llbmNlLw&amp;ntb=1\" target=\"_blank\" rel=\"noopener\">The Origin of Data Science</a></h2>\r\n</div>\r\n<div class=\"rd_gencon_attr\"><sup id=\"rd_gencon_ai_0\" class=\"rd_gencon_ai\"></sup>The concept of&nbsp;<strong>Data Science</strong>&nbsp;emerged in the early 1960s as a response to the growing need for analyzing and interpreting large volumes of data. Initially rooted in&nbsp;<strong>statistics</strong>, it evolved into a multidisciplinary field incorporating&nbsp;<strong>computer science</strong>,&nbsp;<strong>mathematics</strong>, and domain expertise to extract insights and make predictions.</div>\r\n<div id=\"rd_single_ttip\" class=\"rd_single_ttip\">\r\n<div class=\"rd_gencon_ttip\">\r\n<div class=\"rd_gencon_tta \">&nbsp;</div>\r\n</div>\r\n</div>\r\n<div id=\"devmag_card_content\" class=\"devmag_card_content\" data-dataurl=\"/RichDeveloper/Card?QueryType=Card&amp;TableNs=RichDevCardTable&amp;CardTable=Developer&amp;CardId=790b675994319a5195a365723549c956&amp;TabId=0&amp;TabContentId=&amp;TabContentSnippetId=&amp;IsGeneratedContent=True&amp;IsActiveTab=True&amp;IsMagazine=False&amp;IsACFAnswer=True\">\r\n<div>\r\n<p>In 1962, John Tukey highlighted the shift from traditional statistics to&nbsp;<strong>data analysis</strong>, emphasizing the integration of computers for solving mathematical problems. By 1974, Peter Naur introduced the term \"Data Science\" in his work, defining it as the process of building and handling models of reality using data. The establishment of the&nbsp;<strong>International Association for Statistical Computing (IASC)</strong>&nbsp;in 1977 further solidified the connection between statistics, computer technology, and domain knowledge.</p>\r\n</div>\r\n<div id=\"devmag_card_content_dynamic\" class=\"\" data-priority=\"2\">\r\n<div data-bm=\"75\">\r\n<div id=\"rdtb_3_2A27B1\" data-isactivetab=\"true\">\r\n<div id=\"rdtb_cnt_5_2A287A\" class=\" rd_tb_cnt_wp\" data-priority=\"\" data-dataurl=\"/RichDeveloper/Card?QueryType=Card&amp;TableNs=RichDevCardTable&amp;CardTable=Developer&amp;CardId=790b675994319a5195a365723549c956&amp;TabId=0&amp;TabContentId=0&amp;TabContentSnippetId=&amp;IsGeneratedContent=True&amp;IsActiveTab=True&amp;IsMagazine=False&amp;IsACFAnswer=False\" data-firstactive=\"1\">\r\n<div class=\"rd_tb_cnt\">\r\n<div>\r\n<p>The 1990s marked a turning point with the rise of&nbsp;<strong>big data</strong>&nbsp;and the need for scalable tools to handle massive datasets. In 1999, Jacob Zahavi stressed the limitations of conventional statistical methods for analyzing large-scale data, paving the way for&nbsp;<strong>data mining</strong>&nbsp;and specialized tools. By the early 2000s, the field expanded with innovations like&nbsp;<strong>Software-as-a-Service (SaaS)</strong>&nbsp;and the release of&nbsp;<strong>Hadoop</strong>, which revolutionized data storage and processing.</p>\r\n</div>\r\n<div>\r\n<p>The term \"data scientist\" gained prominence in 2008, popularized by professionals like DJ Patil and Jeff Hammerbacher. By 2011, job listings for data scientists surged, reflecting the growing demand for expertise in handling and analyzing data. The field continued to evolve with advancements in&nbsp;<strong>machine learning</strong>,&nbsp;<strong>artificial intelligence</strong>, and&nbsp;<strong>deep learning</strong>, as seen in Google\'s 2015 breakthroughs in speech recognition.</p>\r\n</div>\r\n<div>\r\n<p>Today, Data Science is a cornerstone of industries ranging from business and healthcare to astronomy and social sciences. It leverages&nbsp;<strong>big data</strong>,&nbsp;<strong>AI</strong>, and&nbsp;<strong>IoT</strong>&nbsp;to drive decision-making, optimize processes, and foster innovation. However, the field faces challenges, such as balancing incremental improvements with the need for bold, innovative approaches.</p>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n<div class=\"rd_cnt_srcs b_hide\" data-bm=\"76\">\r\n<div class=\"rd_attr_cntnr\">\r\n<div class=\"rd_attr_items\">&nbsp;</div>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n<div class=\"rdShadowClass\" data-priority=\"2\">&nbsp;</div>\r\n<div id=\"rdgenconseemore\" class=\"\" data-priority=\"2\">\r\n<div id=\"rd_card_sm_expand_gencon\" class=\"rd_card_sm\" data-priority=\"2\"></div>\r\n</div>', NULL, '', NULL, 'https://www.dataversity.net/articles/brief-history-data-science/', NULL, 1, 0, '2025-11-16 12:31:52', '2025-11-16 12:31:52'),
(15, 6, '1.1 Types of AI', '<p><!--StartFragment --></p>\r\n<p class=\"pf0\"><span class=\"cf0\">What is Artificial Intelligence (AI)</span></p>\r\n<p class=\"pf0\">&nbsp;</p>\r\n<p class=\"pf0\"><span class=\"cf0\">Artificial Intelligence (AI) is a technology that enables machines and computers to perform tasks that typically require human intelligence. It helps systems learn from data, recognize patterns and make decisions to solve complex problems. It is used in healthcare, finance, e-commerce and transportation offering personalized recommendations and enabling self-driving cars.</span></p>\r\n<p class=\"pf0\">&nbsp;</p>\r\n<p class=\"pf0\"><span class=\"cf0\">types_of_ai_models.webptypes_of_ai_models.webp</span></p>\r\n<p class=\"pf0\"><span class=\"cf0\">Core Concepts of AI</span></p>\r\n<p class=\"pf0\"><span class=\"cf0\">AI is based on core concepts and technologies that enable machines to learn, reason and make decisions on their own. Let\'s see some of the concepts:</span></p>\r\n<p class=\"pf0\">&nbsp;</p>\r\n<p class=\"pf0\"><span class=\"cf0\">1. Machine Learning (ML)</span></p>\r\n<p class=\"pf0\"><span class=\"cf0\">Machine Learning is a subset of artificial intelligence (AI) that focuses on building systems that can learn from and make decisions based on data. Instead of being explicitly programmed to perform a task, a machine learning model uses algorithms to identify patterns within data and improve its performance over time without human intervention.</span></p>\r\n<p class=\"pf0\">&nbsp;</p>\r\n<p class=\"pf0\"><span class=\"cf0\">2. Generative AI</span></p>\r\n<p class=\"pf0\"><span class=\"cf0\">Generative AI is designed to create new content whether it\'s text, images, music or video. Unlike traditional AI which typically focuses on analyzing and classifying data, it goes a step further by using patterns it has learned from large datasets to generate new original outputs. It \"creates\" rather than just \"recognizes.\"</span></p>\r\n<p class=\"pf0\">&nbsp;</p>\r\n<p class=\"pf0\"><span class=\"cf0\">3. Natural Language Processing (NLP)</span></p>\r\n<p class=\"pf0\"><span class=\"cf0\">Natural Language Processing (NLP) allows machines to understand and interact with human language in a way that feels natural. It enables speech recognition systems like Siri or Alexa to interpret what we say and respond accordingly. It combines linguistics and computer science to help computers process, understand and generate human language allowing for tasks like language translation, sentiment analysis and real-time conversation.</span></p>\r\n<p class=\"pf0\">&nbsp;</p>\r\n<p class=\"pf0\"><span class=\"cf0\">4. Expert Systems</span></p>\r\n<p class=\"pf0\"><span class=\"cf0\">Expert Systems are designed to simulate the decision-making ability of human experts. These systems use a set of predefined \"if-then\" rules and knowledge from specialists in specific fields to make informed decisions similar to how a medical professional would diagnose a disease. They are useful in areas where expert knowledge is important but not always easily accessible.</span></p>\r\n<p class=\"pf0\">&nbsp;</p>\r\n<p class=\"pf1\">&nbsp;</p>\r\n<p><!--EndFragment --></p>', NULL, '', NULL, 'https://www.geeksforgeeks.org/artificial-intelligence/what-is-artificial-intelligence-ai/', NULL, 1, 0, '2025-11-16 15:57:00', '2025-11-16 15:57:00');

-- --------------------------------------------------------

--
-- Table structure for table `course_reviews`
--

CREATE TABLE `course_reviews` (
  `review_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_units`
--

CREATE TABLE `course_units` (
  `unit_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_units`
--

INSERT INTO `course_units` (`unit_id`, `course_id`, `title`, `description`, `order_index`, `created_at`) VALUES
(3, 2, 'INTRODUCTION TO WEB DEVELOPMENT', 'Web development is the process of creating and maintaining websites and web applications, which involves both the \"front end\" (what the user sees) and the \"back end\" (the server-side logic and database). The core technologies are HTML for structure, CSS for styling, and JavaScript for interactivity, with developers also using frameworks, content management systems (CMS), and various other tools to build more complex and dynamic sites.', 1, '2025-11-13 15:48:32'),
(4, 3, 'Data Science', 'Data science is the interdisciplinary field of studying data to extract meaningful insights and patterns, using a combination of statistics, computer science, and domain expertise to solve problems. It involves collecting, processing, analyzing, and interpreting large datasets to inform decision-making and make predictions through methods like machine learning and AI.', 1, '2025-11-14 04:14:07'),
(5, 3, 'scope of data science', 'All the field and applications of data science', 2, '2025-11-16 12:33:02'),
(6, 4, '1.Introduction to AI', 'Artificial Intelligence (AI) is a technology that enables machines and computers to perform tasks that typically require human intelligence. It helps systems learn from data, recognize patterns and make decisions to solve complex problems. It is used in healthcare, finance, e-commerce and transportation offering personalized recommendations and enabling self-driving cars.', 1, '2025-11-16 15:53:20');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_date` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','completed','dropped') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `course_id`, `enrollment_date`, `completion_date`, `progress_percentage`, `status`) VALUES
(3, 1, 2, '2025-11-13 15:53:11', '2025-11-16 17:51:01', 100.00, 'completed'),
(4, 1, 3, '2025-11-14 04:17:40', '2025-11-16 17:49:12', 100.00, 'completed'),
(5, 1, 4, '2025-11-16 15:57:57', '2025-11-16 16:53:23', 100.00, 'completed'),
(6, 4, 3, '2025-11-18 10:20:45', NULL, 0.00, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `instructor`
--

CREATE TABLE `instructor` (
  `instructor_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `expertise` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instructor`
--

INSERT INTO `instructor` (`instructor_id`, `name`, `email`, `password`, `phone`, `bio`, `expertise`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Nabin Neupane', 'nabinneupane@gmail.com', '$2y$10$CwOgOq.yEaEYK5l5SibhJuSqLpZBIkn7CmuXfk3IjDyMzcmx.D23q', NULL, NULL, NULL, NULL, 'active', '2025-11-13 14:01:31', '2025-11-13 14:01:31'),
(2, 'Jhabilal', 'jhabi@gmail.com', '$2y$10$KWS6FbXKqrslMdTEm9lNT.ncnKGLDrG8Z2.nGttz1VrVNLQgO8ha.', NULL, NULL, NULL, NULL, 'active', '2025-11-13 15:43:34', '2025-11-13 15:43:34');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `progress_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completion_date` timestamp NULL DEFAULT NULL,
  `time_spent_minutes` int(11) DEFAULT 0,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lesson_progress`
--

INSERT INTO `lesson_progress` (`progress_id`, `student_id`, `lesson_id`, `completed`, `completion_date`, `time_spent_minutes`, `last_accessed`) VALUES
(3, 1, 6, 1, '2025-11-13 15:54:30', 0, '2025-11-16 16:34:10'),
(13, 1, 13, 1, '2025-11-16 14:35:55', 0, '2025-11-16 16:33:32'),
(14, 1, 14, 1, '2025-11-16 15:22:34', 0, '2025-11-16 15:27:25'),
(55, 1, 15, 0, NULL, 0, '2025-11-16 16:36:07'),
(84, 4, 13, 1, '2025-11-18 10:21:49', 0, '2025-11-18 10:23:17'),
(88, 4, 14, 1, '2025-11-18 10:23:04', 0, '2025-11-18 10:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `material_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `file_name` varchar(200) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `sender_type` enum('student','instructor') NOT NULL,
  `receiver_type` enum('student','instructor') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('student','instructor','admin') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `user_type` enum('student','instructor','admin') NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `email`, `token`, `user_type`, `expires_at`, `used`, `created_at`) VALUES
(1, 'admin@learninghub.com', '4ca6185ec1e27e6beb1fcb79993213cebab07acf37517ffcbc54d4b9c8120dcf', 'admin', '2025-11-13 12:58:29', 0, '2025-11-13 16:43:29'),
(2, 'admin@learninghub.com', '31c41ea328ead3259fe7947445dc9aac4661d8b45056d0b4990cebc616efbf98', 'admin', '2025-11-13 21:16:19', 0, '2025-11-14 01:01:19'),
(3, 'admin@learninghub.com', '35ecaf672c1d9457b86e3c953e3162b71308f078e50963b0a09e6be9b4a29586', 'admin', '2025-11-16 11:52:31', 0, '2025-11-16 15:37:31');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `quiz_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `passing_score` decimal(5,2) DEFAULT 70.00,
  `max_attempts` int(11) DEFAULT 3,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `course_id`, `lesson_id`, `title`, `description`, `time_limit_minutes`, `passing_score`, `max_attempts`, `status`, `created_at`) VALUES
(2, 3, NULL, 'Unit Assessment Quiz', 'Test your knowledge of the unit concepts', NULL, 70.00, 3, 'active', '2025-11-16 16:03:56');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `attempt_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `total_questions` int(11) DEFAULT 0,
  `correct_answers` int(11) DEFAULT 0,
  `time_taken_minutes` int(11) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') DEFAULT 'multiple_choice',
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `correct_answer` varchar(255) NOT NULL,
  `explanation` text DEFAULT NULL,
  `points` decimal(5,2) DEFAULT 1.00,
  `order_index` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_results`
--

CREATE TABLE `quiz_results` (
  `result_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `taken_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `name`, `email`, `password`, `phone`, `profile_image`, `bio`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Tilak Neupane', 'tilak@gmail.com', '$2y$10$ROty2WRfY4xCpx09Dldn9eiADXcZNwbeny9B13GchrLme.DZysmia', NULL, NULL, NULL, 'active', '2025-11-13 13:58:36', '2025-11-13 13:58:36'),
(3, 'Bibek Kumar', 'bibek@gmail.com', '$2y$10$dNHeY.XIzb78KPuvAgYtB..YlsUSz9rYiE8MF9W.aEh0r4oSbFzHC', NULL, NULL, NULL, 'active', '2025-11-13 18:24:00', '2025-11-13 18:24:00'),
(4, 'Kisan Gyawali', 'kisan@gmail.com', '$2y$10$GtkCruWBZaMkw1deTMs/2.hlhnWBI/g0LkvXYwsasTljRx.ZZJ7B2', NULL, NULL, NULL, 'active', '2025-11-18 10:19:27', '2025-11-18 10:19:27');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Online Learning Hub', 'Website name', '2025-11-13 13:57:36'),
(2, 'site_description', 'Professional Online Learning Platform', 'Website description', '2025-11-13 13:57:36'),
(3, 'admin_email', 'admin@learninghub.com', 'Administrator email', '2025-11-13 13:57:36'),
(4, 'maintenance_mode', '0', 'Maintenance mode (0=off, 1=on)', '2025-11-13 13:57:36'),
(5, 'registration_enabled', '1', 'User registration (0=disabled, 1=enabled)', '2025-11-13 13:57:36'),
(6, 'email_verification', '0', 'Email verification required (0=no, 1=yes)', '2025-11-13 13:57:36');

-- --------------------------------------------------------

--
-- Table structure for table `unit_attempts`
--

CREATE TABLE `unit_attempts` (
  `attempt_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `score_percentage` decimal(5,2) NOT NULL,
  `time_taken_minutes` int(11) DEFAULT NULL,
  `answers` longtext DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unit_attempts`
--

INSERT INTO `unit_attempts` (`attempt_id`, `unit_id`, `student_id`, `total_questions`, `correct_answers`, `score_percentage`, `time_taken_minutes`, `answers`, `started_at`, `completed_at`, `status`) VALUES
(1, 3, 1, 1, 1, 100.00, 1, '{\"2\":[\"D\"]}', '2025-11-16 16:28:20', '2025-11-16 16:28:20', 'completed'),
(2, 6, 1, 1, 1, 100.00, 1, '{\"5\":\"A\"}', '2025-11-16 16:31:31', '2025-11-16 16:31:31', 'completed'),
(3, 4, 1, 1, 1, 100.00, 1, '{\"3\":\"A\"}', '2025-11-16 16:32:43', '2025-11-16 16:32:43', 'completed'),
(4, 4, 4, 1, 1, 100.00, 1, '{\"3\":\"A\"}', '2025-11-18 10:21:21', '2025-11-18 10:21:21', 'completed'),
(5, 4, 4, 1, 1, 100.00, 1, '{\"3\":\"A\"}', '2025-11-18 10:23:44', '2025-11-18 10:23:44', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `unit_questions`
--

CREATE TABLE `unit_questions` (
  `question_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('single_choice','multiple_choice','true_false') DEFAULT 'single_choice',
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) DEFAULT NULL,
  `option_d` varchar(500) DEFAULT NULL,
  `option_e` varchar(500) DEFAULT NULL,
  `correct_answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`correct_answers`)),
  `explanation` text DEFAULT NULL,
  `points` decimal(5,2) DEFAULT 1.00,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `unit_questions`
--

INSERT INTO `unit_questions` (`question_id`, `unit_id`, `question_text`, `question_type`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_answers`, `explanation`, `points`, `order_index`, `is_active`, `created_at`) VALUES
(2, 3, 'which one of the followings are web technology?', 'multiple_choice', 'AR.VR AND AI', 'ram ssd harddisk', 'ms-word,excel.', 'HTML,CSS,JS,PHP,', '', '[\"D\"]', 'They are used for creating interactive web contentent', 1.00, 1, 1, '2025-11-13 15:51:34'),
(3, 4, 'What is the primary goal of Data Science?', 'single_choice', 'Extracting knowledge from data', 'Web Designing', '', '', '', '[\"A\"]', 'Extracting knowledge from data is the primary goal of Data Science', 2.00, 1, 1, '2025-11-14 04:17:10'),
(4, 5, 'When was the story of data science started?', 'single_choice', 'From 1960s to present', '1960s', '', '', '', '[\"A\"]', '', 1.00, 1, 1, '2025-11-16 12:35:13'),
(5, 6, 'AI stands for', 'single_choice', 'Artificial Intelligence', 'advance intelligence', '', '', '', '[\"A\"]', '', 1.00, 1, 1, '2025-11-16 15:53:55');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('student','instructor','admin') NOT NULL,
  `action` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`log_id`, `user_id`, `user_type`, `action`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'student', 'register', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 13:58:36'),
(2, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 13:58:54'),
(3, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:00:52'),
(4, 1, 'instructor', 'register', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:01:31'),
(5, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:01:51'),
(6, 2, 'student', 'register', 'unknown', 'unknown', '2025-11-13 14:03:04'),
(7, 2, 'student', 'login', 'unknown', 'unknown', '2025-11-13 14:03:04'),
(8, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:11:15'),
(9, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:12:20'),
(10, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:28:17'),
(11, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:28:32'),
(12, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:33:59'),
(13, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:34:20'),
(14, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 14:58:34'),
(15, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 15:42:53'),
(16, 2, 'instructor', 'register', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 15:43:34'),
(17, 2, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 15:43:50'),
(18, 2, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 16:40:39'),
(19, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 18:21:04'),
(20, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 18:23:19'),
(21, 3, 'student', 'register', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 18:24:00'),
(22, 3, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 18:24:14'),
(23, 3, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 18:25:10'),
(24, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-13 18:26:20'),
(25, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 00:53:06'),
(26, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:00:39'),
(27, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:41:11'),
(28, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:57:22'),
(29, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 01:57:41'),
(30, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:56:08'),
(31, 2, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 03:56:25'),
(32, 2, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:00:47'),
(33, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:01:00'),
(34, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:11:30'),
(35, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:50:21'),
(36, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-14 04:50:29'),
(37, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:03:42'),
(38, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:12:41'),
(39, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:14:49'),
(40, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:14:54'),
(41, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:19:40'),
(42, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:28:30'),
(43, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:28:47'),
(44, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:29:26'),
(45, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:30:59'),
(46, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:44:40'),
(47, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:51:12'),
(48, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:51:37'),
(49, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 04:51:58'),
(50, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 05:42:46'),
(51, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 05:52:45'),
(52, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 07:08:19'),
(53, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 07:10:22'),
(54, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 07:10:55'),
(55, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 07:15:10'),
(56, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 10:24:45'),
(57, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 10:29:31'),
(58, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 10:31:56'),
(59, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 12:26:56'),
(60, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 13:55:59'),
(61, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 13:56:22'),
(62, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 14:31:49'),
(63, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 14:44:24'),
(64, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:42:07'),
(65, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:45:20'),
(66, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:57:38'),
(67, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 15:57:50'),
(68, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 16:52:13'),
(69, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 16:52:27'),
(70, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 16:53:45'),
(71, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 16:54:08'),
(72, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:48:04'),
(73, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:48:20'),
(74, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:49:20'),
(75, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:49:33'),
(76, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:50:33'),
(77, 2, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:50:48'),
(78, 2, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:51:09'),
(79, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:51:28'),
(80, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:52:21'),
(81, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:52:35'),
(82, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-16 17:53:56'),
(83, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 01:20:36'),
(84, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 01:21:01'),
(85, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 01:21:14'),
(86, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 01:23:26'),
(87, 2, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 01:23:39'),
(88, 2, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 01:56:04'),
(89, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 01:56:25'),
(90, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 02:13:57'),
(91, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 03:27:58'),
(92, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 04:02:14'),
(93, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 04:02:46'),
(94, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 04:03:32'),
(95, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 04:18:09'),
(96, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 04:18:42'),
(97, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 04:18:55'),
(98, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 06:02:41'),
(99, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 06:04:29'),
(100, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 06:04:43'),
(101, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 06:44:03'),
(102, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 06:45:11'),
(103, 1, 'instructor', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 06:45:28'),
(104, 1, 'instructor', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-17 06:46:25'),
(105, 1, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 10:17:01'),
(106, 1, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 10:17:43'),
(107, 4, 'student', 'register', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 10:19:27'),
(108, 4, 'student', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 10:19:46'),
(109, 4, 'student', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-18 10:25:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`certificate_id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`),
  ADD UNIQUE KEY `unique_certificate` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_code` (`certificate_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`contact_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_review` (`course_id`,`student_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `course_units`
--
ALTER TABLE `course_units`
  ADD PRIMARY KEY (`unit_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `instructor`
--
ALTER TABLE `instructor`
  ADD PRIMARY KEY (`instructor_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD UNIQUE KEY `unique_lesson_progress` (`student_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_sender` (`sender_id`,`sender_type`),
  ADD KEY `idx_receiver` (`receiver_id`,`receiver_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_score` (`score`),
  ADD KEY `idx_passed` (`passed`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `unit_attempts`
--
ALTER TABLE `unit_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `unit_questions`
--
ALTER TABLE `unit_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_unit` (`unit_id`),
  ADD KEY `idx_order` (`order_index`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`log_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `course_lessons`
--
ALTER TABLE `course_lessons`
  MODIFY `lesson_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `course_reviews`
--
ALTER TABLE `course_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_units`
--
ALTER TABLE `course_units`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `instructor`
--
ALTER TABLE `instructor`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `unit_attempts`
--
ALTER TABLE `unit_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `unit_questions`
--
ALTER TABLE `unit_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructor` (`instructor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD CONSTRAINT `course_lessons_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`unit_id`) ON DELETE CASCADE;

--
-- Constraints for table `course_reviews`
--
ALTER TABLE `course_reviews`
  ADD CONSTRAINT `course_reviews_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_reviews_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `course_units`
--
ALTER TABLE `course_units`
  ADD CONSTRAINT `course_units_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`lesson_id`) ON DELETE CASCADE;

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `materials_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`lesson_id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons` (`lesson_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `unit_attempts`
--
ALTER TABLE `unit_attempts`
  ADD CONSTRAINT `unit_attempts_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`unit_id`),
  ADD CONSTRAINT `unit_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `unit_questions`
--
ALTER TABLE `unit_questions`
  ADD CONSTRAINT `unit_questions_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`unit_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
