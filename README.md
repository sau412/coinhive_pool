# Coinhive pool
Online mining pool based on coinhive miner.

## Requirements
* Windows/Linux
* Apache
* MySQL
* php5 or php7
* jQuery 3.1.1 (tested with that version), not included in this github

## Installation
* Copy files to web folder
* Import DB scheme from db_scheme.sql
* Download jquery to pool folder
* Write your settings in settings.php
* Folder 'protected' should be protected with password in .htaccess or server config

## How it works
Users mine for monero with coinhive script. Site aggregates statistics (hashes) and allow payouts in several coins.

## User attraction instruments
Pool has two-level referral system and badges.

## How payouts work
* Payouts possible with Bitcoin-like RPC interface, see send_rewards_core_wallet.php
* Payouts possible with NXT-like RPC interface, see send_rewards_burst.php
* Payouts possible with TRON cryptocurrency, see send_rewards_tron.php
* Payouts possible with Gridcoin RPC online wallet, see send_rewards_gridcoin.php
* Other payouts in manual mode.
* On user's withdraw request admin receives email notify
