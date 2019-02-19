SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `assets` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `user_uid` int(11) NOT NULL,
  `currency` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `balance` double NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `user_uid` (`user_uid`,`currency`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `currency` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
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
  `btc_per_coin` double NOT NULL DEFAULT '0',
  `user_withdraw_note` text COLLATE utf8_unicode_ci NOT NULL,
  `admin_withdraw_note` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `currency_code` (`currency_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `email` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `to` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `subject` text COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `is_sent` int(11) NOT NULL DEFAULT '0',
  `is_success` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `log` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
  `uid` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_uid` bigint(20) NOT NULL,
  `message` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `payouts` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `session` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `user_uid` int(11) NOT NULL,
  `currency_code` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `payment_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `hashes` int(11) NOT NULL,
  `rate_per_mhash` double NOT NULL,
  `amount` double NOT NULL,
  `payout_fee` double NOT NULL,
  `project_fee` double NOT NULL,
  `total` double NOT NULL,
  `wallet_send_uid` bigint(20) DEFAULT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `tx_id` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE IF NOT EXISTS `payout_stats` (
`currency_code` varchar(100)
,`sum_total` double
,`sum_hashes` decimal(32,0)
,`acount` bigint(21)
,`distinct_users` bigint(21)
);
CREATE TABLE IF NOT EXISTS `platforms` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `currency` varchar(100) NOT NULL,
  `rate` double NOT NULL,
  `caption` varchar(100) NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `results` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `user_uid` int(11) NOT NULL,
  `platform` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` double NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `user_uid` (`user_uid`,`platform`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `session` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `captcha` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `user_uid` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `session` (`session`),
  UNIQUE KEY `token` (`token`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `coinhive_id` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mined` bigint(20) NOT NULL DEFAULT '0',
  `dualmined` bigint(20) NOT NULL DEFAULT '0',
  `withdrawn` bigint(20) NOT NULL DEFAULT '0',
  `bonus` bigint(20) NOT NULL DEFAULT '0',
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `jsecoin` int(11) NOT NULL DEFAULT '0',
  `cooldown` datetime DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `is_admin` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `variables` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
DROP TABLE IF EXISTS `payout_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u0403486_hive`@`localhost` SQL SECURITY DEFINER VIEW `payout_stats` AS select `payouts`.`currency_code` AS `currency_code`,sum(`payouts`.`total`) AS `sum_total`,sum(`payouts`.`hashes`) AS `sum_hashes`,count(0) AS `acount`,count(distinct `payouts`.`user_uid`) AS `distinct_users` from `payouts` where (`payouts`.`tx_id` <> '') group by `payouts`.`currency_code`;
