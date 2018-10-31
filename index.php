<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");
require_once("coinhive.php");
require_once("email.php");
require_once("html.php");

// Only ASCII parameters allowed
foreach($_GET as $key => $value) {
        if(validate_ascii($key)==FALSE) die("Non-ASCII parameters disabled");
        if(validate_ascii($value)==FALSE) die("Non-ASCII parameters disabled");
}
foreach($_POST as $key => $value) {
        if(validate_ascii($key)==FALSE) die("Non-ASCII parameters disabled");
        if(validate_ascii($value)==FALSE) die("Non-ASCII parameters disabled");
}


db_connect();

$coinhive_xmr_per_Mhash=get_variable("payoutPer1MHashes");

// Miner link - show only miner for that user
if(isset($_GET['miner'])) {
        $user_uid=stripslashes($_GET['miner']);
        $coinhive_id=get_coinhive_id_by_user_uid($user_uid);
        $miner_form=html_coinhive_frame($coinhive_id);
        echo $miner_form;
        die();
}

$user_uid="";

if(isset($_GET['coin'])) $coin=stripslashes($_GET['coin']);
else $coin='';

$coin_html=html_escape($coin);

if(isset($_COOKIE['session_id']) && validate_ascii($_COOKIE['session_id'])) {
        $session=$_COOKIE['session_id'];
        $session_escaped=db_escape($session);
        $session_exists=db_query_to_variable("SELECT 1 FROM `sessions` WHERE `session`='$session_escaped'");
        if(!$session_exists) unset($session);
        else {
                $user_uid=db_query_to_variable("SELECT `user_uid` FROM `sessions` WHERE `session`='$session_escaped'");
                $user_uid_escaped=db_escape($user_uid);
                $login=db_query_to_variable("SELECT `username` FROM `users` WHERE `uid`='$user_uid_escaped'");
                $token=db_query_to_variable("SELECT `token` FROM `sessions` WHERE `session`='$session_escaped'");
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


if(isset($_POST['action']) || isset($_GET['action'])) {
        $message='';
        if(isset($_GET['action'])) $action=$_GET['action'];
        else if(isset($_POST['action'])) $action=$_POST['action'];

        if($action=="register") {
                $login=$_POST['login'];
                $password=$_POST['password'];
                $received_token=$_POST['token'];
                $ref_id=$_POST['ref_id'];
                if($ref_id=='') $ref_id=0;
                $password_hash=hash("sha256",$password.strtolower($login).$salt);

                if($token==$received_token) {
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
                                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid' WHERE `session`='$session_escaped'");
                                } else {
                                        write_log("Invalid password for '$login'");
                                        $message="Invalid password";
                                }
                        } else {
                                write_log("Invalid login for '$login'");
                                $message="Invalid login";
                        }
                } else {
                        $message="Invalid token";
                }
        } else if($action=="logout") {
                write_log("Logged out user '$login'");
                $received_token=$_GET['token'];
                if($token==$received_token) {
                        db_query("UPDATE `sessions` SET `user_uid`=NULL WHERE `session`='$session_escaped'");
                } else {
                        $message="Invalid token";
                }
        } else if($action=="withdraw") {
                $received_token=$_POST['token'];
                $currency_code=$_POST['currency_code'];
                $payout_address=$_POST['payout_address'];
                if(isset($_POST['payment_id'])) $payment_id=$_POST['payment_id'];
                else $payment_id='';
                if($payout_address=="") die("Address is not set");

                if($token==$received_token) {
                        $user_uid_escaped=db_escape($user_uid);

                        $user_hashes=get_user_balance($user_uid);
                        $hashes_mined=db_query_to_variable("SELECT `mined` FROM `users` WHERE `uid`='$user_uid_escaped'");
                        $hashes_withdrawn=db_query_to_variable("SELECT `withdrawn` FROM `users` WHERE `uid`='$user_uid_escaped'");
                        $hashes_bonus=db_query_to_variable("SELECT `bonus` FROM `users` WHERE `uid`='$user_uid_escaped'");
                        $hashes_ref=db_query_to_variable("SELECT SUM(`mined`) FROM `users` WHERE `ref_id`='$user_uid_escaped'");
                        $hashes_ref=floor($hashes_ref*$hashes_ref_rate);

                        //$user_hashes=$hashes_mined-$hashes_withdrawn+$hashes_bonus+$hashes_ref;

                        $cooldown_time=db_query_to_variable("SELECT MIN(UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(COALESCE(`cooldown`,0))) FROM `users` WHERE `uid`='$user_uid_escaped'");

                        if($cooldown_time<$cooldown_limit) {
                                $message="One withdraw in 15 minutes";
                        } else if($user_hashes<=0) {
                                $message="Nothing to withdraw";
                        } else {
                                $currency_code_escaped=db_escape($currency_code);
                                $rate_per_mhash=db_query_to_variable("SELECT `rate_per_mhash` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");
                                $payout_fee=db_query_to_variable("SELECT `payout_fee` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");
                                $project_fee=db_query_to_variable("SELECT `project_fee` FROM `currency` WHERE `currency_code`='$currency_code_escaped'");

                                $amount=$user_hashes*$rate_per_mhash/1000000;
                                $total=$amount-$payout_fee-$project_fee;
                                $amount=sprintf("%0.8f",$amount);
                                $amount_escaped=db_escape($amount);
                                $total_escaped=db_escape($total);
                                $total=sprintf("%0.8f",$total);
                                if($total>0) {
                                        $hashes_withdrawn_new=$hashes_withdrawn+$user_hashes;
                                        db_query("UPDATE `users` SET `withdrawn`='$hashes_withdrawn_new',`cooldown`=NOW() WHERE `uid`='$user_uid_escaped'");

                                        write_log("Withdraw user '$login' amount '$user_hashes' (from mined '$hashes_mined' ref '$hashes_ref' bonus '$hashes_bonus' withdrawn '$hashes_withdrawn') coin '$currency_code'");

                                        email_add($email_notify,"Withdraw '$login' '$total' '$currency_code'",
                                                "Withdraw user '$login' amount '$user_hashes' (from mined '$hashes_mined' ref '$hashes_ref' bonus '$hashes_bonus' withdrawn '$hashes_withdrawn') coin '$currency_code' address $payout_address");

                                        $address_escaped=db_escape($payout_address);
                                        $payment_id_escaped=db_escape($payment_id);
                                        $rate_per_mhash_escaped=db_escape($rate_per_mhash);
                                        $payout_fee_escaped=db_escape($payout_fee);
                                        $project_fee_escaped=db_escape($project_fee);
                                        $hashes_escaped=db_escape($user_hashes);

                                        db_query("INSERT INTO `payouts` (`session`,`user_uid`,`currency_code`,`address`,`payment_id`,`hashes`,`rate_per_mhash`,`amount`,`payout_fee`,`project_fee`,`total`)
VALUES ('$session_escaped','$user_uid_escaped','$currency_code_escaped','$address_escaped','$payment_id_escaped','$hashes_escaped','$rate_per_mhash_escaped','$amount_escaped','$payout_fee_escaped','$project_fee_escaped','$total_escaped')");

                                        $message="Request sent";
                                } else {
                                        $message="Payout limit not reached";
                                }
                        }
                }
        } else {
                $message="Invalid token";
        }
        setcookie("message",$message);
        header("Location: ./");
        die();
}

if($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $username=db_query_to_variable("SELECT `username` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $username_html=html_escape($username);
        $login_form=<<<_END
Welcome, $username_html (<a href='?action=logout&token=$token'>logout</a>)

_END;

        $coinhive_id=get_coinhive_id_by_user_uid($user_uid);

        $miner_form=<<<_END
<div class="coinhive-miner"
        data-key="$coinhive_public_key"
        data-user="$coinhive_id">
        <em>Loading...</em>
</div>

_END;

        $logged_in=TRUE;

        $load_user_hashes=get_user_balance($user_uid);

        $balance_form=<<<_END
<input type=hidden id=balance_shown value='$load_user_hashes'>
<h2>Balance <span id=balance_info>$load_user_hashes</span> hashes</h2>

_END;
} else {
        if(isset($_GET['ref'])) $ref_id=stripslashes($_GET['ref']);
        else $ref_id=0;
        $ref_id_html=html_escape($ref_id);
        $login_form=<<<_END
<form name=register method=POST>
<input type=hidden name=action value=register>
<input type=hidden name=token value='$token'>
<input type=hidden name=ref_id value='$ref_id_html'>
Login: <input type=text name=login required>
Password <input type=password name=password required>
<input type=submit value='Login/register'>
</form>
_END;
        $miner_form='';

        $logged_in=FALSE;
        $load_user_hashes=0;

        $balance_form='';
}

if(isset($_COOKIE['message'])) {
        $message="<div style='background:yellow;'>".html_escape($_COOKIE['message'])."</div>";
        setcookie("message","");
} else {
        $message='';
}

if(isset($_GET['json'])) {
        if($logged_in==FALSE) {
                echo html_register_login_info();
                die();
        }

        $balance_data=coinhive_get_user_balance($coinhive_id);
        if($balance_data->success) {
                $hashes_total=$balance_data->total;
        } else {
                $hashes_total=0;
        }

        $hashes_withdrawn=db_query_to_variable("SELECT `withdrawn` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $hashes_bonus=db_query_to_variable("SELECT `bonus` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $hashes_ref=db_query_to_variable("SELECT SUM(`mined`) FROM `users` WHERE `ref_id`='$user_uid_escaped'");
        $hashes_ref=floor($hashes_ref*$hashes_ref_rate);

        $hashes_total_escaped=db_escape($hashes_total);
        $hashes_withdrawn_escaped=db_escape($hashes_withdrawn);

        db_query("UPDATE `users` SET `mined`='$hashes_total_escaped' WHERE uid='$user_uid_escaped' AND `mined`<='$hashes_total_escaped'");

        $cooldown_time=db_query_to_variable("SELECT UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(COALESCE(`cooldown`,0)) FROM `users` WHERE `uid`='$user_uid_escaped'");

        // Hashes after update
        $user_hashes=get_user_balance($user_uid);

        // Show balance and other data
        echo <<<_END
<script>
if (document.getElementById('balance_shown') !== null) {
        var intervals=600;
        var balance_begin=eval(document.getElementById('balance_shown').value);
        var balance_end=eval('$user_hashes');
        var balance_diff=balance_end-balance_begin;
        for(var i=1;i<=intervals;i++) {
                var balance=Math.floor(balance_begin+i*balance_diff/intervals);
                if (typeof refresh_balance === "function") {
                        setTimeout(refresh_balance,i*60000/intervals,balance);
                }
        }
        document.getElementById('balance_shown').value=eval('$user_hashes');
}
</script>
_END;
        echo "<p>Mined $hashes_total hashes";
        if($hashes_ref>0) echo ", referred $hashes_ref hashes";
        if($hashes_bonus>0) echo ", bonus $hashes_bonus hashes";
        if($hashes_withdrawn>0) echo ",  withdrawn $hashes_withdrawn hashes";
        echo "</p>\n";

        if($coin=='') {
                echo html_select_your_coin($user_hashes);
        } else {
                echo html_results_in_coin($user_hashes,$coin);
        }

        // Achievements
        echo html_achievements_section($user_uid);

        // User links
        echo html_links_section($user_uid);

        // User payouts
        echo html_payouts_section($user_uid);

        // This is end of dynamically loaded block, so script should stop here
        die();
}


echo html_page_begin($pool_name);

echo <<<_END
<h1>$pool_name</h1>
$message
<p>
$login_form
</p>

$miner_form
$balance_form
<div id=mining_info>Loading data, please wait...</div>
<input type=hidden id=coin value='$coin_html'>

_END;

// End of page, script and footer
echo html_page_end();

?>
