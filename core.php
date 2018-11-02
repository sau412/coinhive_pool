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

// Get detailed balance
function get_user_balance_detail($user_uid) {
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

        $ref_total=$ref1+$ref2;

        $balance=$mined+$ref1+$ref2+$bonus-$withdrawn;

        return array(
                "mined"=>$mined,
                "bonus"=>$bonus,
                "withdrawn"=>$withdrawn,
                "ref_total"=>$ref_total,
                "balance"=>$balance,
                "ref1"=>$ref1,
                "ref2"=>$ref2,
        );
}

// Get user balance
function get_user_balance($user_uid) {
        $balance_detail=get_user_balance_detail($user_uid);

        return $balance_detail['balance'];
}

// Get badges
function get_user_badges($user_uid) {
        global $hashes_ref_rate;
        global $hashes_ref_rate_level_2;

        $user_uid_escaped=db_escape($user_uid);
        $mined=db_query_to_variable("SELECT `mined` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $refs=db_query_to_variable("SELECT count(*) FROM `users` WHERE `ref_id`='$user_uid_escaped'");
        $withdraws=db_query_to_variable("SELECT count(*) FROM `payouts` WHERE `user_uid`='$user_uid_escaped'");

        $coins=db_query_to_variable("SELECT count(DISTINCT `currency_code`) FROM `payouts` WHERE `user_uid`='$user_uid_escaped'");

        $badges_array=array();

        if($mined>=1E6) $badges_array[]="<span style='background-color:#F88;' title='Mined 1 Mhash'>&nbsp;Beginner&nbsp;</sup></span>";
        if($mined>=1E7) $badges_array[]="<span style='background-color:#F44;' title='Mined 10 Mhash'>&nbsp;Average miner&nbsp;</sup></span>";
        if($mined>=1E8) $badges_array[]="<span style='background-color:#F22;' title='Mined 100 Mhash'>&nbsp;Advanced miner&nbsp;</sup></span>";
        if($mined>=1E9) $badges_array[]="<span style='background-color:#F00;' title='Mined 1000 Mhash'>&nbsp;Farm&nbsp;</sup></span>";

        if($coins>=5) $badges_array[]="<span style='background-color:#FF0;' title='Withdraw in 5 different coins'>&nbsp;Multicoiner&nbsp;</sup></span>";

        if($refs>=1E0) $badges_array[]="<span style='background-color:#8F8;' title='Referred 1 user'>&nbsp;One more user&nbsp;</span>";
        if($refs>=1E1) $badges_array[]="<span style='background-color:#4F4;' title='Referred 10 users'>&nbsp;Foreman&nbsp;</span>";
        if($refs>=1E2) $badges_array[]="<span style='background-color:#2F2;' title='Referred 100 users'>&nbsp;Centurion&nbsp;</span>";
        if($refs>=1E3) $badges_array[]="<span style='background-color:#0F0;' title='Referred 1000 users'>&nbsp;Creator&nbsp;</span>";

        if($withdraws>=1E0) $badges_array[]="<span style='background-color:#88F;' title='Withdraw 1 time'>&nbsp;First try&nbsp;</span>";
        if($withdraws>=1E1) $badges_array[]="<span style='background-color:#44F;' title='Withdraw 10 times'>&nbsp;Ten withdraws&nbsp;</span>";
        if($withdraws>=1E2) $badges_array[]="<span style='background-color:#22F;' title='Withdraw 100 times'>&nbsp;Tester&nbsp;</span>";
        if($withdraws>=1E3) $badges_array[]="<span style='background-color:#00F;' title='Withdraw 1000 times'>&nbsp;Withdraw fabric&nbsp;</span>";

        return $badges_array;
}

// Get coinhive_id by user_uid
function get_coinhive_id_by_user_uid($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $coinhive_id=db_query_to_variable("SELECT `coinhive_id` FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($coinhive_id=='') {
                $coinhive_id=bin2hex(random_bytes(16));
                $coinhive_id_escaped=db_escape($coinhive_id);
                db_query("UPDATE `users` SET `coinhive_id`='$coinhive_id_escaped' WHERE `uid`='$user_uid_escaped'");
        }
        return $coinhive_id;
}

// Get cooldown time status
function is_cooltime_active($user_uid) {
        global $cooldown_limit;
        $user_uid_escaped=db_escape($user_uid);
        $cooldown_time=db_query_to_variable("SELECT MIN(UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(COALESCE(`cooldown`,0))) FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($cooldown_time<$cooldown_limit) return TRUE;
        else return FALSE;
}

// Get username by user_uid
function get_username_by_uid($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $username=db_query_to_variable("SELECT `username` FROM `users` WHERE `uid`='$user_uid_escaped'");
        return $username;
}

// Update user balance
function update_user_mined_balance($user_uid,$new_balance) {
        $user_uid_escaped=db_escape($user_uid);
        $new_balance_escaped=db_escape($new_balance);
        db_query("UPDATE `users` SET `mined`='$new_balance_escaped' WHERE uid='$user_uid_escaped' AND `mined`<='$new_balance_escaped'");
}

function get_session() {
        if(isset($_COOKIE['session_id']) && validate_ascii($_COOKIE['session_id'])) {
                $session=$_COOKIE['session_id'];
                $session_escaped=db_escape($session);
                $session_exists=db_query_to_variable("SELECT 1 FROM `sessions` WHERE `session`='$session_escaped'");
                if(!$session_exists) {
                        unset($session);
                }
        }

        if(!isset($session)) {
                $session=bin2hex(random_bytes(32));
                $token=bin2hex(random_bytes(32));
                setcookie('session_id',$session,time()+86400*30);
                $session_escaped=db_escape($session);
                $token_escaped=db_escape($token);
                db_query("INSERT INTO `sessions` (`session`,`token`) VALUES ('$session_escaped','$token_escaped')");
        }
        return $session;
}

function get_user_uid_by_session($session) {
        $session_escaped=db_escape($session);
        $user_uid=db_query_to_variable("SELECT `user_uid` FROM `sessions` WHERE `session`='$session_escaped'");
        return $user_uid;
}

function get_user_token_by_session($session) {
        $session_escaped=db_escape($session);
        $token=db_query_to_variable("SELECT `token` FROM `sessions` WHERE `session`='$session_escaped'");
        return $token;
}

function user_register_or_login($session,$login,$password,$ref_id) {
        global $salt;

        $session_escaped=db_escape($session);
        $password_hash=hash("sha256",$password.strtolower($login).$salt);

        $message="";

        if(validate_ascii($login)) {
                $login_escaped=db_escape($login);
                $ref_id_escaped=db_escape($ref_id);
                $exists_hash=db_query_to_variable("SELECT `password_hash` FROM `users` WHERE `username`='$login_escaped'");
                if($exists_hash=="") {
                        write_log("New user '$login' ref '$ref_id'");
                        $login_escaped=db_escape($login);
                        db_query("INSERT INTO `users` (`username`,`password_hash`,`ref_id`) VALUES ('$login_escaped','$password_hash','$ref_id_escaped')");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `username`='$login_escaped'");
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid' WHERE `session`='$session_escaped'");
                } else if($password_hash==$exists_hash) {
                        write_log("Logged in user '$login'");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `username`='$login_escaped'");
                        $user_uid_escaped=db_escape($user_uid);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid' WHERE `session`='$session_escaped'");
                } else {
                        write_log("Invalid password for '$login'");
                        $message="Invalid password";
                }
        } else {
                write_log("Invalid login for '$login'");
                $message="Invalid login";
        }
        return $message;
}

function user_logout($session) {
        $user_uid=get_user_uid_by_session($session);
        $username=get_username_by_uid($user_uid);
        write_log("Logged out user '$username'");

        $session_escaped=db_escape($session);
        db_query("UPDATE `sessions` SET `user_uid`=NULL WHERE `session`='$session_escaped'");
}

function user_withdraw($session,$user_uid,$currency_code,$payout_address,$payment_id) {
        global $email_notify;

        $message="";

        $session_escaped=db_escape($session);
        $user_uid_escaped=db_escape($user_uid);
        $username=get_username_by_uid($user_uid);

        $balance_info=get_user_balance_detail($user_uid);

        $hashes_mined=$balance_info['mined'];
        $hashes_withdrawn=$balance_info['withdrawn'];
        $hashes_bonus=$balance_info['bonus'];
        $hashes_ref=$balance_info['ref_total'];
        $hashes_balance=$balance_info['balance'];

        if(is_cooltime_active($user_uid)) {
                $message="One withdraw in 15 minutes";
        } else if($hashes_balance<=0) {
                $message="Nothing to withdraw";
        } else {
                $currency_code_escaped=db_escape($currency_code);
                $rate_per_mhash=db_query_to_variable("SELECT `rate_per_mhash` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");
                $payout_fee=db_query_to_variable("SELECT `payout_fee` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");
                $project_fee=db_query_to_variable("SELECT `project_fee` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");

                $amount=$hashes_balance*$rate_per_mhash/1000000;
                $total=$amount-$payout_fee-$project_fee;
                $amount=sprintf("%0.8f",$amount);
                $amount_escaped=db_escape($amount);
                $total_escaped=db_escape($total);
                $total=sprintf("%0.8f",$total);

                if($total>0) {
                        $hashes_withdrawn_new=$hashes_withdrawn+$hashes_balance;
                        db_query("UPDATE `users` SET `withdrawn`='$hashes_withdrawn_new',`cooldown`=NOW() WHERE `uid`='$user_uid_escaped'");

                        write_log("Withdraw user '$username' amount '$hashes_balance' (from mined '$hashes_mined' ref '$hashes_ref' bonus '$hashes_bonus' withdrawn '$hashes_withdrawn') coin '$currency_code' total '$total' address '$payout_address'");

                        email_add($email_notify,"Withdraw '$username' '$total' '$currency_code'",
                                "Withdraw user '$username' amount '$hashes_balance' (from mined '$hashes_mined' ref '$hashes_ref' bonus '$hashes_bonus' withdrawn '$hashes_withdrawn')
coin '$currency_code' total '$total' address '$payout_address'");

                        $address_escaped=db_escape($payout_address);
                        $payment_id_escaped=db_escape($payment_id);
                        $rate_per_mhash_escaped=db_escape($rate_per_mhash);
                        $payout_fee_escaped=db_escape($payout_fee);
                        $project_fee_escaped=db_escape($project_fee);
                        $hashes_escaped=db_escape($hashes_balance);

                        db_query("INSERT INTO `payouts` (`session`,`user_uid`,`currency_code`,`address`,`payment_id`,`hashes`,`rate_per_mhash`,`amount`,`payout_fee`,`project_fee`,`total`)
VALUES ('$session_escaped','$user_uid_escaped','$currency_code_escaped','$address_escaped','$payment_id_escaped','$hashes_escaped','$rate_per_mhash_escaped','$amount_escaped','$payout_fee_escaped','$project_fee_escaped','$total_escaped')");

                        $message="Request sent";
                } else {
                        $message="Payout limit not reached";
                }
        }

        return $message;
}

function is_admin($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $is_admin=db_query_to_variable("SELECT `is_admin` FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($is_admin) return TRUE;
        else return FALSE;
}

function admin_set_tx_id($payout_uid,$tx_id) {
        $message="TX ID set";
        $payout_uid_escaped=db_escape($payout_uid);
        $tx_id_escaped=db_escape($tx_id);
        db_query("UPDATE `payouts` SET `tx_id`='$tx_id_escaped' WHERE `uid`='$payout_uid'");
}

// For php 5 only variant for random_bytes is openssl_random_pseudo_bytes from openssl lib
if(!function_exists("random_bytes")) {
        function random_bytes($n) {
                return openssl_random_pseudo_bytes($n);
        }
}
?>
