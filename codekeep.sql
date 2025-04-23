  -- phpMyAdmin SQL Dump
  -- version 5.2.1
  -- https://www.phpmyadmin.net/
  --
  -- Host: 127.0.0.1
  -- Generation Time: Apr 22, 2025 at 11:30 PM
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
  -- Database: `codekeep`
  --

  -- --------------------------------------------------------

  --
  -- Table structure for table `bookmarks`
  --

  CREATE TABLE `bookmarks` (
    `id` int(11) NOT NULL,
    `user_email` varchar(255) NOT NULL,
    `problem_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  -- --------------------------------------------------------

  --
  -- Table structure for table `notes`
  --

  CREATE TABLE `notes` (
    `id` int(11) NOT NULL,
    `user_email` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `content` text DEFAULT NULL,
    `category` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  --
  -- Dumping data for table `notes`
  --

  INSERT INTO `notes` (`id`, `user_email`, `title`, `content`, `category`, `created_at`, `updated_at`) VALUES
  (2, 'om.thanage@somaiya.edu', 'Dynamic Programming Patterns', 'Common DP patterns to remember:\n1. Fibonacci sequence pattern\n2. 0/1 Knapsack pattern\n3. Unbounded Knapsack pattern\n4. Longest Common Subsequence pattern\n5. Palindromic Subsequence pattern', 'Algorithms', '2025-04-16 06:12:42', '2025-04-16 06:12:42'),
  (3, 'om.thanage@somaiya.edu', 'Graph Traversal Techniques', 'BFS: Use a queue, good for shortest path in unweighted graphs.\nDFS: Use a stack or recursion, good for exploring all paths.\nDijkstra: Use a priority queue, finds shortest path in weighted graphs without negative edges.\nBellman-Ford: Can handle negative edges, checks for negative cycles.', 'Algorithms', '2025-04-16 06:12:42', '2025-04-16 06:12:42'),
  (4, 'om.thanage@somaiya.edu', 'C++ STL Containers Cheat Sheet', 'vector: Dynamic array, fast random access\nlist: Doubly linked list, fast insertions/deletions\ndeque: Double-ended queue, fast at both ends\nset: Ordered unique elements\nmap: Ordered key-value pairs\nunordered_set: Hash table, faster lookups\nunordered_map: Hash table with key-value pairs', 'Programming', '2025-04-16 06:12:42', '2025-04-16 06:12:42');

  -- --------------------------------------------------------

  --
  -- Table structure for table `platform`
  --

  CREATE TABLE `platform` (
    `platform_name` varchar(100) DEFAULT NULL,
    `platformId` varchar(100) NOT NULL,
    `platformUrl` text DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  --
  -- Dumping data for table `platform`
  --

  INSERT INTO `platform` (`platform_name`, `platformId`, `platformUrl`, `email`) VALUES
  ('CodeChef', 'codechef', 'https://www.codechef.com', 'test@somaiya.edu'),
  ('CodeForces', 'codeforces', 'https://codeforces.com', 'test@somaiya.edu'),
  ('LeetCode', 'leetcode', 'https://leetcode.com', 'test@somaiya.edu');

  -- --------------------------------------------------------

  --
  -- Table structure for table `problems`
  --

  CREATE TABLE `problems` (
    `id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `difficulty` enum('Easy','Medium','Hard') NOT NULL,
    `platform` varchar(50) NOT NULL,
    `problem_url` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `tags` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  --
  -- Dumping data for table `problems`
  --

  INSERT INTO `problems` (`id`, `title`, `difficulty`, `platform`, `problem_url`, `description`, `tags`, `created_at`) VALUES
  (1, 'Two Sum', 'Easy', 'LeetCode', 'https://leetcode.com/problems/two-sum/', 'Given an array of integers nums and an integer target, return indices of the two numbers such that they add up to target.', 'Array,Hash Table', '2025-04-15 18:10:48'),
  (2, 'Add Two Numbers', 'Medium', 'LeetCode', 'https://leetcode.com/problems/add-two-numbers/', 'You are given two non-empty linked lists representing two non-negative integers. The digits are stored in reverse order, and each of their nodes contains a single digit.', 'Linked List,Math', '2025-04-15 18:10:48'),
  (3, 'Chef and Strings', 'Easy', 'CodeChef', 'https://www.codechef.com/problems/CHEFSTR1', 'Chef has a string S consisting of lowercase English alphabets. Chef defined a function F such that F(i) denotes the frequency of the character S[i] in S.', 'Strings,Implementation', '2025-04-15 18:10:48'),
  (4, 'Watermelon', 'Easy', 'Codeforces', 'https://codeforces.com/problemset/problem/4/A', 'One hot summer day Pete and his friend Billy decided to buy a watermelon. They chose the biggest and the ripest one, in their opinion.', 'Math,Brute Force', '2025-04-15 18:10:48'),
  (5, 'Theatre Square', 'Medium', 'Codeforces', 'https://codeforces.com/problemset/problem/1/A', 'Theatre Square in the capital city of Berland has a rectangular shape with the size n × m meters. On the occasion of the city\'s anniversary, a decision was taken to pave the Square with square granite flagstones.', 'Math', '2025-04-15 18:10:48');

  -- --------------------------------------------------------

  --
  -- Table structure for table `profile`
  --

  CREATE TABLE `profile` (
    `Uemail` varchar(255) NOT NULL,
    `codechef_id` varchar(255) DEFAULT NULL,
    `codeforces_id` varchar(255) DEFAULT NULL,
    `leetcode_id` varchar(255) DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  --
  -- Dumping data for table `profile`
  --

  INSERT INTO `profile` (`Uemail`, `codechef_id`, `codeforces_id`, `leetcode_id`) VALUES
  ('om.thanage@somaiya.edu', 'omitron', 'om.thanage', 'omithon');

  -- --------------------------------------------------------

  --
  -- Table structure for table `saved_contests`
  --

  CREATE TABLE `saved_contests` (
    `id` int(11) NOT NULL,
    `user_email` varchar(255) NOT NULL,
    `platform` varchar(50) NOT NULL,
    `contest_name` varchar(255) NOT NULL,
    `contest_code` varchar(100) NOT NULL,
    `contest_start_date` datetime NOT NULL,
    `contest_end_date` datetime NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  --
  -- Dumping data for table `saved_contests`
  --

  INSERT INTO `saved_contests` (`id`, `user_email`, `platform`, `contest_name`, `contest_code`, `contest_start_date`, `contest_end_date`, `created_at`) VALUES
  (1, 'om.thanage@somaiya.edu', 'CodeChef', 'Starters 182', 'START182', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-04-15 17:51:43');

  -- --------------------------------------------------------

  --
  -- Table structure for table `solved_problems`
  --

  CREATE TABLE `solved_problems` (
    `id` int(11) NOT NULL,
    `user_email` varchar(255) NOT NULL,
    `problem_id` int(11) NOT NULL,
    `solution_code` text DEFAULT NULL,
    `language` varchar(50) DEFAULT NULL,
    `time_taken` int(11) DEFAULT NULL,
    `solved_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  -- --------------------------------------------------------

  --
  -- Table structure for table `users`
  --

  CREATE TABLE `users` (
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(100) NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  --
  -- Dumping data for table `users`
  --

  INSERT INTO `users` (`name`, `email`, `password`) VALUES
  ('Om', 'om.thanage@somaiya.edu', '$2y$10$e1aLkKUQfEObWqBkYp54sO7pF8hedwofuERG65vZrrLODN7KP316i'),
  ('test', 'test@somaiya.edu', '$2y$10$Im6fyL0q8Jh4DDHUxwIcNuDnQFjF.tXUHR/1keGRrGDCZDlk.oQm2');

  -- --------------------------------------------------------

  --
  -- Table structure for table `user_statistics`
  --

  CREATE TABLE `user_statistics` (
    `id` int(11) NOT NULL,
    `user_email` varchar(255) NOT NULL,
    `total_solved` int(11) DEFAULT 0,
    `easy_solved` int(11) DEFAULT 0,
    `medium_solved` int(11) DEFAULT 0,
    `hard_solved` int(11) DEFAULT 0,
    `leetcode_solved` int(11) DEFAULT 0,
    `codechef_solved` int(11) DEFAULT 0,
    `codeforces_solved` int(11) DEFAULT 0,
    `streak_days` int(11) DEFAULT 0,
    `last_solved_date` date DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  --
  -- Dumping data for table `user_statistics`
  --

  INSERT INTO `user_statistics` (`id`, `user_email`, `total_solved`, `easy_solved`, `medium_solved`, `hard_solved`, `leetcode_solved`, `codechef_solved`, `codeforces_solved`, `streak_days`, `last_solved_date`, `updated_at`) VALUES
  (1, 'om.thanage@somaiya.edu', 0, 0, 0, 0, 0, 0, 0, 0, NULL, '2025-04-15 18:22:55');

  --
  -- Indexes for dumped tables
  --

  --
  -- Indexes for table `bookmarks`
  --
  ALTER TABLE `bookmarks`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `user_problem` (`user_email`,`problem_id`),
    ADD KEY `problem_id` (`problem_id`);

  --
  -- Indexes for table `notes`
  --
  ALTER TABLE `notes`
    ADD PRIMARY KEY (`id`);

  --
  -- Indexes for table `platform`
  --
  ALTER TABLE `platform`
    ADD PRIMARY KEY (`platformId`),
    ADD UNIQUE KEY `platformUrl` (`platformUrl`) USING HASH,
    ADD KEY `email` (`email`);

  --
  -- Indexes for table `problems`
  --
  ALTER TABLE `problems`
    ADD PRIMARY KEY (`id`);

  --
  -- Indexes for table `profile`
  --
  ALTER TABLE `profile`
    ADD PRIMARY KEY (`Uemail`);

  --
  -- Indexes for table `saved_contests`
  --
  ALTER TABLE `saved_contests`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `user_contest` (`user_email`,`platform`,`contest_code`);

  --
  -- Indexes for table `solved_problems`
  --
  ALTER TABLE `solved_problems`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `user_problem` (`user_email`,`problem_id`),
    ADD KEY `problem_id` (`problem_id`);

  --
  -- Indexes for table `users`
  --
  ALTER TABLE `users`
    ADD PRIMARY KEY (`email`);

  --
  -- Indexes for table `user_statistics`
  --
  ALTER TABLE `user_statistics`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `user_stats` (`user_email`);

  --
  -- AUTO_INCREMENT for dumped tables
  --

  --
  -- AUTO_INCREMENT for table `bookmarks`
  --
  ALTER TABLE `bookmarks`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `notes`
  --
  ALTER TABLE `notes`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

  --
  -- AUTO_INCREMENT for table `problems`
  --
  ALTER TABLE `problems`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

  --
  -- AUTO_INCREMENT for table `saved_contests`
  --
  ALTER TABLE `saved_contests`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

  --
  -- AUTO_INCREMENT for table `solved_problems`
  --
  ALTER TABLE `solved_problems`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

  --
  -- AUTO_INCREMENT for table `user_statistics`
  --
  ALTER TABLE `user_statistics`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

  --
  -- Constraints for dumped tables
  --

  --
  -- Constraints for table `bookmarks`
  --
  ALTER TABLE `bookmarks`
    ADD CONSTRAINT `bookmarks_ibfk_1` FOREIGN KEY (`problem_id`) REFERENCES `problems` (`id`) ON DELETE CASCADE;

  --
  -- Constraints for table `platform`
  --
  ALTER TABLE `platform`
    ADD CONSTRAINT `platform_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`);

  --
  -- Constraints for table `profile`
  --
  ALTER TABLE `profile`
    ADD CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`Uemail`) REFERENCES `users` (`email`) ON DELETE CASCADE;

  --
  -- Constraints for table `solved_problems`
  --
  ALTER TABLE `solved_problems`
    ADD CONSTRAINT `solved_problems_ibfk_1` FOREIGN KEY (`problem_id`) REFERENCES `problems` (`id`) ON DELETE CASCADE;
  COMMIT;

  /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
  /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
  /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
