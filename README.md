# Coinhive pool
Online mining pool based on coinhive miner.

## Requirements
* Windows/Linux
* Apache
* MySQL
* php5 or php7
* jQuery 3.1.1

## Installation
* Copy files to web folder
* Import DB scheme from base.sql
* Write your settings in settings.php

## How it works
Users mine for monero with coinhive script. Site aggregates statistics (hashes) and allow payouts in several coins.

## User attraction instruments
Pool has two-level referral system and badges.

## How payouts work
* Payouts possible with RPC interface, see wallet_send_rewards.php
Other payouts in manual mode.
