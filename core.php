<?php
// Core functions

// Escape text to show in html page as text
function html_escape($data) {
        $data=htmlspecialchars($data);
        $data=str_replace("'","&apos;",$data);
        return $data;
}

// Add message to log
function write_log($message) {
        $message_escaped=db_escape($message);
        db_query("INSERT INTO `log` (`message`) VALUES ('$message_escaped')");
}

// Checks is string contains only ASCII symbols
function validate_ascii($string) {
        if(strlen($string)>100) return FALSE;
        if(is_string($string)==FALSE) return FALSE;
        for($i=0;$i!=strlen($string);$i++) {
                if(ord($string[$i])<32 || ord($string[$i])>127) return FALSE;
        }
        return TRUE;
}

// Get variable
function get_variable($name) {
        $name_escaped=db_escape($name);
        return db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='$name_escaped'");
}

// Set variable
function set_variable($name,$value) {
        $name_escaped=db_escape($name);
        $value_escaped=db_escape($value);
        db_query("INSERT INTO `variables` (`name`,`value`) VALUES ('$name_escaped','$value_escaped') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
}

// Get user balance
function get_user_balance($user_uid) {
        global $hashes_ref_rate;
        global $hashes_ref_rate_level_2;

        $user_uid_escaped=db_escape($user_uid);
        $mined=db_query_to_variable("SELECT `mined` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $bonus=db_query_to_variable("SELECT `bonus` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $withdrawn=db_query_to_variable("SELECT `withdrawn` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $ref1=db_query_to_variable("SELECT SUM(`mined`) FROM `users` WHERE `ref_id`='$user_uid_escaped'");
        $ref2=db_query_to_variable("SELECT SUM(`mined`) FROM `users` WHERE `ref_id` IN (SELECT `uid` FROM `users` WHERE `ref_id`='$user_uid_escaped')");
        $ref1=floor($ref1*$hashes_ref_rate);
        $ref2=floor($ref2*$hashes_ref_rate_level_2);

        $hashes=$mined+$ref1+$ref2+$bonus-$withdrawn;
        return $hashes;
}

// Get badges
function get_user_badges($user_uid) {
        global $hashes_ref_rate;
        global $hashes_ref_rate_level_2;

        $user_uid_escaped=db_escape($user_uid);
        $mined=db_query_to_variable("SELECT `mined` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $refs=db_query_to_variable("SELECT count(*) FROM `users` WHERE `ref_id`='$user_uid_escaped'");
        $withdraws=db_query_to_variable("SELECT count(*) FROM `payouts` WHERE `user_uid`='$user_uid_escaped'");

        $badges_array=array();

        if($mined>=1E6) $badges_array[]="<span style='background-color:#F88;' title='Mined 1 Mhash'>&nbsp;Beginner&nbsp;</sup></span>";
        if($mined>=1E7) $badges_array[]="<span style='background-color:#F44;' title='Mined 10 Mhash'>&nbsp;Average miner&nbsp;</sup></span>";
        if($mined>=1E8) $badges_array[]="<span style='background-color:#F22;' title='Mined 100 Mhash'>&nbsp;Advanced miner&nbsp;</sup></span>";
        if($mined>=1E9) $badges_array[]="<span style='background-color:#F00;' title='Mined 1000 Mhash'>&nbsp;Farm&nbsp;</sup></span>";

        if($refs>=1E0) $badges_array[]="<span style='background-color:#8F8;' title='Referred 1 user'>&nbsp;One more user&nbsp;</span>";
        if($refs>=1E1) $badges_array[]="<span style='background-color:#4F4;' title='Referred 10 users'>&nbsp;Foreman&nbsp;</span>";
        if($refs>=1E2) $badges_array[]="<span style='background-color:#2F2;' title='Referred 100 users'>&nbsp;Centurion&nbsp;</span>";
        if($refs>=1E3) $badges_array[]="<span style='background-color:#0F0;' title='Referred 1000 users'>&nbsp;Creator&nbsp;</span>";

        if($withdraws>=1E0) $badges_array[]="<span style='background-color:#88F;' title='Withdraw 1 time'>&nbsp;Just try&nbsp;</span>";
        if($withdraws>=1E1) $badges_array[]="<span style='background-color:#44F;' title='Withdraw 10 times'>&nbsp;&nbsp;</span>";
        if($withdraws>=1E2) $badges_array[]="<span style='background-color:#22F;' title='Withdraw 100 times'>&nbsp;Tester&nbsp;</span>";
        if($withdraws>=1E3) $badges_array[]="<span style='background-color:#00F;' title='Withdraw 1000 times'>&nbsp;Withdraw fabric&nbsp;</span>";

        return $badges_array;
}

// For php 5 only variant for random_bytes is openssl_random_pseudo_bytes from openssl lib
if(!function_exists("random_bytes")) {
        function random_bytes($n) {
                return openssl_random_pseudo_bytes($n);
        }
}
?>
