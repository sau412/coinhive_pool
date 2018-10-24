<?php
require_once("settings.php");
require_once("db.php");
require_once("email.php");

db_connect();

email_send_all();
?>
