-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 02, 2022 at 03:59 AM
-- Server version: 10.3.36-MariaDB
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `infoshoreapps_aac`
--

-- --------------------------------------------------------

--
-- Table structure for table `amz_keys`
--

CREATE TABLE `amz_keys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `associate_id` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `aws_access_id` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `aws_secret_key` varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `country` varchar(255) NOT NULL,
  `aws_country` varchar(20) NOT NULL,
  `amazon_website` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_imports`
--

CREATE TABLE `bulk_imports` (
  `id` int(11) NOT NULL,
  `asin` text NOT NULL,
  `amazon_base_url` varchar(200) NOT NULL,
  `failed` text NOT NULL,
  `failed_asin` text NOT NULL,
  `total` int(100) NOT NULL,
  `user_id` int(10) NOT NULL,
  `status` int(2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `chargerequest`
--

CREATE TABLE `chargerequest` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `charge_id` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `api_client_id` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `price` float NOT NULL,
  `status` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` varchar(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `updated_at` varchar(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `response` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `conversionrates`
--

CREATE TABLE `conversionrates` (
  `basecurrency` varchar(100) NOT NULL,
  `convertcurrency` varchar(100) NOT NULL,
  `conversionrate` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `crawlinglogs`
--

CREATE TABLE `crawlinglogs` (
  `id` int(11) NOT NULL,
  `product_url` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `rawdata` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `dateofmodification` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `crons`
--

CREATE TABLE `crons` (
  `id` int(11) NOT NULL,
  `crontype` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `isrunning` tinyint(1) NOT NULL DEFAULT 0,
  `counter` bigint(20) NOT NULL DEFAULT 0,
  `lastrun` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` int(11) NOT NULL,
  `currency_code` varchar(30) NOT NULL,
  `currency` varchar(100) NOT NULL,
  `conversionrates` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `failed_productimports`
--

CREATE TABLE `failed_productimports` (
  `id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `reason` text NOT NULL,
  `type` varchar(500) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `failed_review_pages`
--

CREATE TABLE `failed_review_pages` (
  `id` int(11) NOT NULL,
  `url` varchar(1000) NOT NULL,
  `asin` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` int(2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fetchReviews`
--

CREATE TABLE `fetchReviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_asin` varchar(30) NOT NULL,
  `status` int(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `reviews_cnt` int(11) NOT NULL,
  `processed_page` int(11) NOT NULL,
  `totalpage` int(10) NOT NULL,
  `currentpage` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `importToShopify`
--

CREATE TABLE `importToShopify` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` int(5) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `legacy` tinyint(1) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `shopifylocationid` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `feature1` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feature2` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feature3` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feature4` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feature5` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `item_note` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `product_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `option1name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `option2name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `option3name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `parentasin` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `shopifyproductid` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `newflag` tinyint(4) NOT NULL,
  `quantityflag` tinyint(4) NOT NULL,
  `priceflag` tinyint(4) NOT NULL,
  `block` tinyint(1) NOT NULL,
  `duplicate` tinyint(1) NOT NULL,
  `status` enum('Already Exist','Ready to Import','Import in progress','Imported','reimport in progress','Incomplete') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Imported',
  `reviews` int(11) NOT NULL DEFAULT -1,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `asin` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `imgurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `shopifyimageid` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `sku` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `asin` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `handle` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `option1val` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `option2val` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `option3val` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `price` double(8,2) NOT NULL,
  `saleprice` double(8,2) NOT NULL,
  `currency` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `weight` float NOT NULL,
  `weight_unit` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'lb',
  `imageurl` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `newflag` tinyint(4) NOT NULL,
  `quantityflag` tinyint(4) NOT NULL,
  `priceflag` tinyint(4) NOT NULL,
  `block` tinyint(1) NOT NULL,
  `duplicate` tinyint(1) NOT NULL,
  `status` enum('Already Exist','Ready to Import','Import in progress','Imported') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Imported',
  `shopifyproductid` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `shopifyvariantid` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `shopifyinventoryid` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `amazonofferlistingid` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `detail_page_url` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `custom_link` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `review_url` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `review_exp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `shopifylocationid` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proxy`
--

CREATE TABLE `proxy` (
  `id` int(11) NOT NULL,
  `plan` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` tinyint(4) DEFAULT NULL,
  `username` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='utf8mb4_unicode_ci';

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_asin` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `review_id` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('published','unpublished','','') DEFAULT 'published',
  `rating` int(5) NOT NULL,
  `imgArr` text NOT NULL,
  `reviewTitle` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `authorName` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `authorEmail` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `reviewDetails` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `reviewDate` varchar(30) NOT NULL,
  `verifiedFlag` varchar(50) NOT NULL,
  `FoundHelpful` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `route_permission`
--

CREATE TABLE `route_permission` (
  `id` int(10) UNSIGNED NOT NULL,
  `route` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `permissions` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `roles` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_indexs`
--

CREATE TABLE `search_indexs` (
  `id` int(11) NOT NULL,
  `country` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `search_index` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `root_browse_node` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tags` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `vendor` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `product_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `inventory_policy` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `defquantity` int(11) NOT NULL,
  `price_sync` tinyint(1) NOT NULL,
  `inventory_sync` tinyint(1) NOT NULL,
  `outofstock_action` enum('unpublish','outofstock','delete') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `buynow` tinyint(1) NOT NULL,
  `buynowtext` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `scripttagid` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `markupenabled` tinyint(1) NOT NULL DEFAULT 0,
  `markuptype` enum('FIXED','PERCEN') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'FIXED',
  `markupval` float NOT NULL,
  `markupvalfixed` float NOT NULL,
  `markupround` tinyint(1) NOT NULL,
  `shopifylocationid` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `reviewenabled` tinyint(1) NOT NULL,
  `reviewwidth` int(11) NOT NULL DEFAULT 500,
  `showreviews` tinyint(1) NOT NULL DEFAULT 0,
  `starcolorreviews` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yellow',
  `paginatereviews` int(11) NOT NULL DEFAULT 10,
  `paddingreviews` int(11) NOT NULL DEFAULT 10,
  `bordercolorreviews` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yellow',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `change_status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `shopifyproducts`
--

CREATE TABLE `shopifyproducts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `handle` varchar(500) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `productid` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `variantid` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `gid_shopifyproductid` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `gid_shopifyvariantid` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `gid_shopifyinventoryid` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `gid_shopifylocationid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `ebayitemid` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `dateofmodification` datetime NOT NULL,
  `qty` int(11) NOT NULL,
  `price` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sync_failure_request`
--

CREATE TABLE `sync_failure_request` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `status` int(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `ownername` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `avatar_url` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `shopurl` text COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL,
  `catalogfetched` tinyint(4) NOT NULL,
  `shopifyimported` tinyint(4) NOT NULL,
  `fbainvnt` tinyint(4) NOT NULL,
  `tempcode` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `installationstatus` tinyint(4) NOT NULL,
  `membershiptype` enum('free','paid') COLLATE utf8_unicode_ci NOT NULL,
  `plan` int(11) NOT NULL,
  `sync` tinyint(4) NOT NULL,
  `storecreated_at` datetime NOT NULL,
  `storeupdated_at` datetime NOT NULL,
  `plan_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `skulimit` int(11) NOT NULL DEFAULT 10,
  `skuconsumed` int(11) NOT NULL,
  `review` tinyint(1) NOT NULL,
  `shopcurrency` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'USD',
  `autoCurrencyConversion` tinyint(1) NOT NULL DEFAULT 0,
  `tosaccepted` tinyint(4) NOT NULL,
  `includeoutofstock` tinyint(4) NOT NULL,
  `publishstatus` tinyint(4) NOT NULL,
  `keysemail` tinyint(4) NOT NULL,
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `usermsg` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
  `custommode` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_agents`
--

CREATE TABLE `user_agents` (
  `id` int(11) NOT NULL,
  `ua_string` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amz_keys`
--
ALTER TABLE `amz_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bulk_imports`
--
ALTER TABLE `bulk_imports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chargerequest`
--
ALTER TABLE `chargerequest`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crawlinglogs`
--
ALTER TABLE `crawlinglogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crons`
--
ALTER TABLE `crons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_productimports`
--
ALTER TABLE `failed_productimports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_review_pages`
--
ALTER TABLE `failed_review_pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fetchReviews`
--
ALTER TABLE `fetchReviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `importToShopify`
--
ALTER TABLE `importToShopify`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`),
  ADD KEY `password_resets_token_index` (`token`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`,`user_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proxy`
--
ALTER TABLE `proxy`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_user_role_id_foreign` (`role_id`);

--
-- Indexes for table `route_permission`
--
ALTER TABLE `route_permission`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `route_permission_route_unique` (`route`);

--
-- Indexes for table `search_indexs`
--
ALTER TABLE `search_indexs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shopifyproducts`
--
ALTER TABLE `shopifyproducts`
  ADD PRIMARY KEY (`id`,`user_id`);

--
-- Indexes for table `sync_failure_request`
--
ALTER TABLE `sync_failure_request`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `variant_id` (`variant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_agents`
--
ALTER TABLE `user_agents`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amz_keys`
--
ALTER TABLE `amz_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bulk_imports`
--
ALTER TABLE `bulk_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chargerequest`
--
ALTER TABLE `chargerequest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crawlinglogs`
--
ALTER TABLE `crawlinglogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crons`
--
ALTER TABLE `crons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_productimports`
--
ALTER TABLE `failed_productimports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_review_pages`
--
ALTER TABLE `failed_review_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fetchReviews`
--
ALTER TABLE `fetchReviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `importToShopify`
--
ALTER TABLE `importToShopify`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proxy`
--
ALTER TABLE `proxy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `route_permission`
--
ALTER TABLE `route_permission`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_indexs`
--
ALTER TABLE `search_indexs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting`
--
ALTER TABLE `setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shopifyproducts`
--
ALTER TABLE `shopifyproducts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sync_failure_request`
--
ALTER TABLE `sync_failure_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_agents`
--
ALTER TABLE `user_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
