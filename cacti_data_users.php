<?php
require_once("settings_remote.php");
require_once("db.php");

db_connect();

// Get active users count
$active_users=db_query_to_variable("SELECT count(*) FROM `users` WHERE DATE_SUB(NOW(),INTERVAL 1 HOUR)<`timestamp`");

// Show in cacti-readable format
echo "active_users:$active_users\n";
?>
