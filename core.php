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
        if(strlen($string)>500) return FALSE;
        if(is_string($string)==FALSE) return FALSE;
        for($i=0;$i!=strlen($string);$i++) {
                if((ord($string[$i])<32 && !in_array(ord($string[$i]),array(10,13))) || ord($string[$i])>127) return FALSE;
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

// Set asset
function set_user_asset($user_uid,$currency,$balance) {
        $user_uid_escaped=db_escape($user_uid);
        $currency_escaped=db_escape($currency);
        $balance_escaped=db_escape($balance);
        db_query("INSERT INTO `assets` (`user_uid`,`currency`,`balance`) VALUES ('$user_uid_escaped','$currency_escaped','$balance_escaped')
ON DUPLICATE KEY UPDATE `balance`=VALUES(`balance`)");
}

// Get user assets
function get_user_assets($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $assets_array=db_query_to_array("SELECT c.`currency_name`,a.`currency`,a.`balance`,(a.`balance`*c.`btc_per_coin`) AS btc_est FROM `assets` AS a
LEFT JOIN `currency` AS c ON c.`currency_code`=a.`currency`
WHERE a.`user_uid`='$user_uid_escaped'");
        return $assets_array;
}

// Get user assets in BTC
function get_user_assets_btc($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $amount_btc=db_query_to_variable("SELECT SUM(a.`balance`*c.`btc_per_coin`) FROM `assets` AS a
JOIN `currency` AS c ON c.`currency_code`=a.`currency`
WHERE a.`user_uid`='$user_uid_escaped'");
        return $amount_btc;
}

// Add user asset
function add_user_asset($user_uid,$currency,$amount) {
        $user_uid_escaped=db_escape($user_uid);
        $currency_escaped=db_escape($currency);
        $amount_escaped=db_escape($amount);
        db_query("INSERT INTO `assets` (`user_uid`,`currency`,`balance`) VALUES ('$user_uid_escaped','$currency_escaped','$amount_escaped')
ON DUPLICATE KEY UPDATE `balance`=`balance`+VALUES(`balance`)");
}

// Add ref level 1 user asset
function add_user_asset_ref1($user_uid,$currency,$amount) {
        global $hashes_ref_rate;
        $user_uid_escaped=db_escape($user_uid);
        $ref_amount=$amount*$hashes_ref_rate;
        $ref_uid=db_query_to_variable("SELECT `ref_id` FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($ref_uid!='') {
                $ref_uid_escaped=db_escape($ref_uid);
                $currency_escaped=db_escape($currency);
                $ref_amount_escaped=db_escape($ref_amount);
                db_query("INSERT INTO `assets` (`user_uid`,`currency`,`balance`) VALUES ('$ref_uid_escaped','$currency_escaped','$ref_amount_escaped')
ON DUPLICATE KEY UPDATE `balance`=`balance`+VALUES(`balance`)");
                add_user_asset_ref2($ref_uid,$currency,$amount);
        }
}

// Add ref level 2 user asset
function add_user_asset_ref2($user_uid,$currency,$amount) {
        global $hashes_ref_rate_level_2;

        $user_uid_escaped=db_escape($user_uid);
        $ref_amount=$amount*$hashes_ref_rate_level_2;
        $ref_uid=db_query_to_variable("SELECT `ref_id` FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($ref_uid!='') {
                $ref_uid_escaped=db_escape($ref_uid);
                $currency_escaped=db_escape($currency);
                $ref_amount_escaped=db_escape($ref_amount);
                db_query("INSERT INTO `assets` (`user_uid`,`currency`,`balance`) VALUES ('$ref_uid_escaped','$currency_escaped','$ref_amount_escaped')
ON DUPLICATE KEY UPDATE `balance`=`balance`+VALUES(`balance`)");
        }
}



// Get user results
function get_user_results($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $results_array=db_query_to_array("SELECT `platform`,`value` FROM `results` WHERE `user_uid`='$user_uid_escaped'");
        return $results_array;
}

// Set user results
function set_user_results($user_uid,$platform,$new_result) {
        $user_uid_escaped=db_escape($user_uid);
        $platform_escaped=db_escape($platform);
        $new_result_escaped=db_escape($new_result);
        $old_result=db_query_to_variable("SELECT `value` FROM `results` WHERE `user_uid`='$user_uid_escaped' AND `platform`='$platform_escaped'");
        $result_diff=$new_result-$old_result;
        if($result_diff==0 || $new_result==0 || $new_result=='') return FALSE;
        db_query("INSERT INTO `results` (`user_uid`,`platform`,`value`) VALUES ('$user_uid_escaped','$platform_escaped','$new_result_escaped')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
}

// Update user results
function update_user_results($user_uid,$platform,$new_result) {
        $user_uid_escaped=db_escape($user_uid);
        $platform_escaped=db_escape($platform);
        $new_result_escaped=db_escape($new_result);
        $old_result=db_query_to_variable("SELECT `value` FROM `results` WHERE `user_uid`='$user_uid_escaped' AND `platform`='$platform_escaped'");
        if($old_result=='') $old_result=0;
        $result_diff=$new_result-$old_result;
        if($result_diff<=0 || $new_result==0 || $new_result=='') {
                if($result_diff<0) {
                        $coinhive_xmr_per_Mhash=get_variable("payoutPer1MHashes");
                        $xmr_per_Mhash=0.99*get_variable("payoutPer1MHashes")/0.7;
                        $xmr=$result_diff*$xmr_per_Mhash/1000000;
                        write_log("Negative diff for user $user_uid platform $platform old result $old_result new result $new_result diff $result_diff xmr $xmr");
                }
                return FALSE;
        }
        db_query("UPDATE `users` SET `timestamp`=NOW() WHERE `uid`='$user_uid_escaped'");
        db_query("INSERT INTO `results` (`user_uid`,`platform`,`value`) VALUES ('$user_uid_escaped','$platform_escaped','$new_result_escaped')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
        switch($platform) {
                case 'Coinhive':
                        $xmr_per_Mhash=get_variable("payoutPer1MHashes");
                        $xmr=$result_diff*$xmr_per_Mhash/1000000;
                        add_user_asset($user_uid,"XMR",$xmr);
                        add_user_asset_ref1($user_uid,"XMR",$xmr);
                        break;
                case 'Coinimp-XMR':
                        $coinhive_xmr_per_Mhash=get_variable("payoutPer1MHashes");
                        $xmr_per_Mhash=0.99*get_variable("payoutPer1MHashes")/0.7;
                        $xmr=$result_diff*$xmr_per_Mhash/1000000;
                        add_user_asset($user_uid,"XMR",$xmr);
                        add_user_asset_ref1($user_uid,"XMR",$xmr);
                        break;
                case 'Coinimp-WEB':
                        $web_per_Mhash=get_variable("web_payoutPer1MHashes");
                        $web=$result_diff*$web_per_Mhash/1000000;
                        add_user_asset($user_uid,"WEB",$web);
                        add_user_asset_ref1($user_uid,"WEB",$web);
                        break;
                case 'JSECoin':
                        add_user_asset($user_uid,"JSE",$result_diff);
                        break;
                case 'Freebitcoin':
                        add_user_asset($user_uid,"BTC",$result_diff);
                        break;
                case 'Freedogecoin':
                        add_user_asset($user_uid,"DOGE",$result_diff);
                        break;
        }
}

// Update user result id
function update_user_result_id($user_uid,$platform,$new_id) {
        $user_uid_escaped=db_escape($user_uid);
        $platform_escaped=db_escape($platform);
        $new_id_escaped=db_escape($new_id);

        $exists=db_query_to_variable("SELECT 1 FROM `results` WHERE `platform`='$platform_escaped' AND `id`='$new_id_escaped'");

        if(!$exists) {
                db_query("INSERT INTO `results` (`user_uid`,`platform`,`id`,`value`) VALUES ('$user_uid_escaped','$platform_escaped','$new_id_escaped',0)
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`)");
        }
}

// Get detailed balance
function get_user_balance_detail($user_uid) {
        global $hashes_ref_rate;
        global $hashes_ref_rate_level_2;

        $user_uid_escaped=db_escape($user_uid);
        $mined=db_query_to_variable("SELECT `mined` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $dualmined=db_query_to_variable("SELECT `dualmined` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $bonus=db_query_to_variable("SELECT `bonus` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $withdrawn=db_query_to_variable("SELECT `withdrawn` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $ref1=db_query_to_variable("SELECT SUM(`mined`) FROM `users` WHERE `ref_id`='$user_uid_escaped'");
        $ref2=db_query_to_variable("SELECT SUM(`mined`) FROM `users` WHERE `ref_id` IN (SELECT `uid` FROM `users` WHERE `ref_id`='$user_uid_escaped')");

        $ref1=floor($ref1*$hashes_ref_rate);
        $ref2=floor($ref2*$hashes_ref_rate_level_2);

        $ref_total=$ref1+$ref2;

        $balance=$mined+$dualmined+$ref1+$ref2+$bonus-$withdrawn;

        return array(
                "mined"=>$mined,
                "dualmined"=>$dualmined,
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

// Get user hashes
function get_user_hashes($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $hashes=db_query_to_variable("SELECT SUM(`value`) FROM `results` WHERE `user_uid`='$user_uid_escaped'");
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
        //db_query("UPDATE `users` SET `mined`='$new_balance_escaped' WHERE uid='$user_uid_escaped' AND `mined`<='$new_balance_escaped'");
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

        $user_btc=get_user_assets_btc($user_uid);

        if(is_cooltime_active($user_uid)) {
                $message="One withdraw in 15 minutes";
        } else if($user_btc<=0) {
                $message="Nothing to withdraw";
        } else {
                $currency_code_escaped=db_escape($currency_code);
                $btc_per_coin=db_query_to_variable("SELECT `btc_per_coin` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");
                $payout_fee=db_query_to_variable("SELECT `payout_fee` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");
                $project_fee=db_query_to_variable("SELECT `project_fee` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");

                if($btc_per_coin>0) $amount=$user_btc/$btc_per_coin;
                else $amount=0;

                $total=$amount-$payout_fee-$project_fee;
                $amount=sprintf("%0.8f",$amount);
                $amount_escaped=db_escape($amount);
                $total_escaped=db_escape($total);
                $total=sprintf("%0.8f",$total);

                if($total>0) {
                        // Delete user assets
                        db_query("DELETE FROM `assets` WHERE `user_uid`='$user_uid_escaped'");

                        write_log("Withdraw user '$username' (assets amount '$user_btc') coin '$currency_code' total '$total' address '$payout_address'");

                        email_add($email_notify,"Withdraw '$username' '$total' '$currency_code'",
                                "Withdraw user '$username' (assets amount '$user_btc') coin '$currency_code' total '$total' address '$payout_address'");

                        $address_escaped=db_escape($payout_address);
                        $payment_id_escaped=db_escape($payment_id);
                        // $rate_per_mhash_escaped=db_escape($rate_per_mhash);
                        $payout_fee_escaped=db_escape($payout_fee);
                        $project_fee_escaped=db_escape($project_fee);
                        // $hashes_escaped=db_escape($hashes_balance);

                        db_query("INSERT INTO `payouts` (`session`,`user_uid`,`currency_code`,`address`,`payment_id`,`amount`,`payout_fee`,`project_fee`,`total`,`status`)
VALUES ('$session_escaped','$user_uid_escaped','$currency_code_escaped','$address_escaped','$payment_id_escaped','$amount_escaped','$payout_fee_escaped','$project_fee_escaped','$total_escaped','requested')");

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

function admin_set_tx_id($payout_uid,$tx_id,$status) {
        $message="TX ID set";
        $payout_uid_escaped=db_escape($payout_uid);
        $tx_id_escaped=db_escape($tx_id);
        db_query("UPDATE `payouts` SET `tx_id`='$tx_id_escaped' WHERE `uid`='$payout_uid'");
        payout_set_status($payout_uid,$status);
}

// Set payout status
function payout_cancel($user_uid,$payout_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $payout_uid_escaped=db_escape($payout_uid);
        $status="cancelled";
        $status_escaped=db_escape($status);

        $exists=db_query_to_variable("SELECT 1 FROM `payouts` WHERE `uid`='$payout_uid' AND `user_uid`='$user_uid_escaped' AND `status` IN ('requested')");

        if($exists) {
                // Only requested can be cancelled
                db_query("UPDATE `payouts` SET `status`='$status_escaped' WHERE `uid`='$payout_uid' AND `user_uid`='$user_uid_escaped' AND `status` IN ('requested')");

                $amount=db_query_to_variable("SELECT `amount` FROM `payouts` WHERE `uid`='$payout_uid' AND `user_uid`='$user_uid_escaped' AND `status` IN ('cancelled')");
                $currency=db_query_to_variable("SELECT `currency_code` FROM `payouts` WHERE `uid`='$payout_uid' AND `user_uid`='$user_uid_escaped' AND `status` IN ('cancelled')");

                $username=get_username_by_uid($user_uid);
                write_log("Withdraw cancelled, user '$username'");

                add_user_asset($user_uid,$currency,$amount);
        }
}

// Set payout status
function payout_set_status($payout_uid,$status) {
        $payout_uid_escaped=db_escape($payout_uid);
        $status_escaped=db_escape($status);

        switch($status) {
                case "cancelled":
                        // Only requested can be cancelled
                        db_query("UPDATE `payouts` SET `status`='$status_escaped' WHERE `uid`='$payout_uid' AND `status` IN ('requested','processing')");
                        break;
                case "processing":
                        // Only requested can be processing
                        db_query("UPDATE `payouts` SET `status`='$status_escaped' WHERE `uid`='$payout_uid' AND `status` IN ('requested')");
                        break;
                case "sent":
                        // Sent only after processing
                        db_query("UPDATE `payouts` SET `status`='$status_escaped' WHERE `uid`='$payout_uid' AND `status` IN ('processing')");
                        break;
                case "error":
                        // Error for all except cancelled
                        db_query("UPDATE `payouts` SET `status`='$status_escaped' WHERE `uid`='$payout_uid' AND `status` IN ('processing','requested')");
                        break;
        }

        // Update user balance
        $user_uid=db_query_to_variable("SELECT `user_uid` FROM `payouts` WHERE `uid`='$payout_uid_escaped'");
        //update_withdrawn($user_uid);
}

// Not needed anymore
function update_withdrawn($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $withdrawn=db_query_to_variable("SELECT SUM(`hashes`) FROM `payouts` WHERE `user_uid`='$user_uid_escaped' AND `status` IN ('requested','processing','sent')");
        $withdrawn_escaped=db_escape($withdrawn);
        db_query("UPDATE `users` SET `withdrawn`='$withdrawn_escaped' WHERE `uid`='$user_uid_escaped'");
}

function request_coin($user_uid,$message) {
        $username=get_username_by_uid($user_uid);
        write_log("Feedback: '$message' username '$username'");
        return "Request sent";
}

function chat_add_message($user_uid,$user_message) {
        $username=get_username_by_uid($user_uid);
        $user_uid_escaped=db_escape($user_uid);
        $user_message_escaped=db_escape($user_message);
        db_query("INSERT INTO `messages` (`user_uid`,`message`) VALUES ('$user_uid_escaped','$user_message_escaped')");
        write_log("Chat: username '$username' message '$user_message'");
        return "";
}

function recaptcha_check($response) {
        global $recaptcha_private_key;
        $recaptcha_url="https://www.google.com/recaptcha/api/siteverify";
        $query="secret=$recaptcha_private_key&response=$response&remoteip=".$_SERVER['REMOTE_ADDR'];
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$query);
        curl_setopt($ch,CURLOPT_URL,$recaptcha_url);
        $result = curl_exec ($ch);
        $data = json_decode($result);
        if($data->success) return TRUE;
        else return FALSE;
}

function request_deposit_address($user_uid,$currency) {
        $user_uid_escaped=db_escape($user_uid);
        $currency_escaped=db_escape($currency);

//      db_query("LOCK TABLES `deposits` WRITE");

        $exists_uid=db_query_to_variable("SELECT `uid` FROM `deposits` WHERE `user_uid`='$user_uid_escaped' AND `currency`='$currency_escaped'");

        if(!$exists_uid) {
                db_query("INSERT INTO `deposits` (`user_uid`,`currency`) VALUES ('$user_uid_escaped','$currency_escaped')");
                $exists_uid=mysql_insert_id();
        }

//      db_query("UNLOCK TABLES");

        return $exists_uid;
}

function get_deposit_info($user_uid,$currency) {
        $user_uid_escaped=db_escape($user_uid);
        $currency_escaped=db_escape($currency);

        $data=db_query_to_array("SELECT `uid`,`user_uid`,`currency`,`wallet_uid`,`address`,`amount`,`timestamp` FROM `deposits` WHERE `user_uid`='$user_uid_escaped' AND `currency`='$currency_escaped'");
        $row=array_pop($data);
        return $row;
}

// For php 5 only variant for random_bytes is openssl_random_pseudo_bytes from openssl lib
if(!function_exists("random_bytes")) {
        function random_bytes($n) {
                return openssl_random_pseudo_bytes($n);
        }
}

?>
