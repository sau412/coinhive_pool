SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `assets` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `currency` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `balance` double NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `currency` (
  `uid` int(11) NOT NULL,
  `currency_code` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `currency_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` int(11) NOT NULL DEFAULT '1',
  `payout_fee` double NOT NULL,
  `project_fee` double NOT NULL DEFAULT '0',
  `api_url` text COLLATE utf8_unicode_ci NOT NULL,
  `url_wallet` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `url_tx` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `img_url` text COLLATE utf8_unicode_ci NOT NULL,
  `payment_id_field` int(11) NOT NULL DEFAULT '0',
  `rate_per_mhash` double NOT NULL DEFAULT '0',
  `btc_per_coin` double NOT NULL,
  `user_withdraw_note` text COLLATE utf8_unicode_ci NOT NULL,
  `admin_withdraw_note` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `deposits` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `currency` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `wallet_uid` int(11) DEFAULT NULL,
  `address` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `email` (
  `uid` int(11) NOT NULL,
  `to` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `subject` text COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `is_sent` int(11) NOT NULL DEFAULT '0',
  `is_success` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `log` (
  `uid` int(11) NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `messages` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `payouts` (
  `uid` int(11) NOT NULL,
  `session` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `user_uid` int(11) NOT NULL,
  `currency_code` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `payment_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `hashes` int(11) NOT NULL DEFAULT '0',
  `rate_per_mhash` double NOT NULL DEFAULT '0',
  `amount` double NOT NULL,
  `payout_fee` double NOT NULL,
  `project_fee` double NOT NULL,
  `total` double NOT NULL,
  `wallet_uid` bigint(20) DEFAULT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `tx_id` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `platforms` (
  `uid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `currency` varchar(100) NOT NULL,
  `rate` double NOT NULL,
  `caption` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `results` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `platform` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` double NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `sessions` (
  `uid` int(11) NOT NULL,
  `session` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `captcha` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_uid` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `users` (
  `uid` int(11) NOT NULL,
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `coinhive_id` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mined` bigint(20) NOT NULL DEFAULT '0',
  `dualmined` bigint(20) NOT NULL DEFAULT '0',
  `withdrawn` bigint(20) NOT NULL DEFAULT '0',
  `bonus` bigint(20) NOT NULL DEFAULT '0',
  `jsecoin` double NOT NULL DEFAULT '0',
  `comment` text COLLATE utf8_unicode_ci,
  `cooldown` datetime DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `is_admin` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `variables` (
  `uid` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `variables` (`uid`, `name`, `value`, `timestamp`) VALUES
(1, 'payoutPer1MHashes', '1.77601003745E-5', '2019-03-08 17:06:03'),
(2, 'web_payoutPer1MHashes', '2.5', '2019-01-12 11:54:07'),
(3, 'btc_per_web', '0.00000031', '2019-03-15 11:52:41');


ALTER TABLE `assets`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid` (`user_uid`,`currency`);

ALTER TABLE `currency`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `currency_code` (`currency_code`);

ALTER TABLE `deposits`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid` (`user_uid`,`currency`);

ALTER TABLE `email`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `log`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `payouts`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `status` (`status`);

ALTER TABLE `platforms`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `results`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid` (`user_uid`,`platform`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `session` (`session`),
  ADD UNIQUE KEY `token` (`token`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `variables`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `name` (`name`);


ALTER TABLE `assets`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `currency`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `deposits`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `email`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `log`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `messages`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `payouts`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `platforms`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `results`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sessions`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `variables`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
