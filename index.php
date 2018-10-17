<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");
require_once("coinhive.php");

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
        $user_uid_escaped=db_escape($user_uid);
        $coinhive_id=db_query_to_variable("SELECT `coinhive_id` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $miner_form=<<<_END
<script src="https://authedmine.com/lib/simple-ui.min.js" async></script>
<div class="coinhive-miner"
        data-key="$coinhive_public_key"
        data-user="$coinhive_id">
        <em>Loading...</em>
</div>
_END;
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
                $payment_id=$_POST['payment_id'];
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
        $login_form=<<<_END
Welcome, $username (<a href='?action=logout&token=$token'>logout</a>)

_END;

        $coinhive_id=db_query_to_variable("SELECT `coinhive_id` FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($coinhive_id=='') {
                $coinhive_id=$token=bin2hex(random_bytes(16));
                $coinhive_id_escaped=db_escape($coinhive_id);
                db_query("UPDATE `users` SET `coinhive_id`='$coinhive_id_escaped' WHERE `uid`='$user_uid_escaped'");
        }

        $miner_form=<<<_END
<div class="coinhive-miner"
        data-key="$coinhive_public_key"
        data-user="$coinhive_id">
        <em>Loading...</em>
</div>

_END;

        $logged_in=TRUE;
} else {
        if(isset($_GET['ref'])) $ref_id=stripslashes($_GET['ref']);
        else $ref_id=0;
        $ref_id_html=html_escape($ref_id);
        $login_form=<<<_END
<form name=register method=POST>
<input type=hidden name=action value=register>
<input type=hidden name=token value='$token'>
<input type=hidden name=ref_id value='$ref_id_html'>
Login: <input type=text name=login>
Password <input type=password name=password>
<input type=submit value='Login/register'>
</form>
_END;
        $miner_form='';

        $logged_in=FALSE;
}

if(isset($_COOKIE['message'])) {
        $message="<div style='background:yellow;'>".html_escape($_COOKIE['message'])."</div>";
        setcookie("message","");
} else {
        $message='';
}

if(isset($_GET['json'])) {
        if($logged_in==FALSE) {
                echo "<p>Disclaimer: this page embeds third-party script (coinhive), use it on your own risk.</p>";
                //echo "<p>Only registered user can mine and withdraw</p>";
                echo "<p>Mine any supported coin online in the browser:</p>";
                echo "<p>Mine hashes, then convert mined hashes into any supported coin.</p>";
                echo "<p>Actual exchange rate is used. Fee depends on the coin. You can withdraw any amount above payout fee. Withdraw takes up to 24 hours.</p>";
                echo "<p>If you want to add your favourite coin here, please contact <a href='mailto:sau412@gmail.com'>sau412@gmail.com</a></p>";
                $currency_data_array=db_query_to_array("SELECT `currency_code`,`currency_name`,`payout_fee`,`project_fee`,`rate_per_mhash`,`img_url`,`payment_id_field` FROM `currency` ORDER BY `currency_code` ASC");

                echo "<h2>Supported coins</h2>\n";
                echo "<p>Fee depends on the coin</p>\n";
                echo "<center>\n";
                echo "<table class=currency_grid>\n";
                $n=0;
                foreach($currency_data_array as $currency_data) {
                        if(($n%6)==0) echo "</tr>\n<tr>\n";
                        $n++;
                        $currency_code=$currency_data['currency_code'];
                        $currency_name=$currency_data['currency_name'];
                        $img_url=$currency_data['img_url'];
                        $rate_per_mhash=$currency_data['rate_per_mhash'];
                        $payout_fee=$currency_data['payout_fee'];
                        $project_fee=$currency_data['project_fee'];
                        $payment_id_field=$currency_data['payment_id_field'];

                        $result=$user_hashes*$rate_per_mhash/1000000;
                        $total=$result-$payout_fee-$project_fee;

                        $total_fee=sprintf("%0.8f",$payout_fee+$project_fee);

                        $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);

                        $result=sprintf("%0.8f",$result);
                        if($total>0) $total=sprintf("%0.8f<br><a href='#'>withdraw</a>",$total);
                        else $total=sprintf("<span style='color:red;'>%0.8f</span><br>mining for fee",$total);

                        echo "<td><img src='$img_url'><br><strong>$currency_name</strong><br><small>$rate_per_mhash<br>per Mhash</small></td>\n";
                        //echo "<td><a href='?coin=$currency_code'><img src='$img_url'></a><br><strong>$currency_name</strong></td>\n";
                        //echo "<td onClick=\"set_coin('$currency_code')\"><img src='$img_url'><br><strong>$currency_name</strong><br>$total</td>\n";
                }

                echo "</table>\n";
                echo "</center>\n";
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
        //$hashes_balance_escaped=db_escape($hashes_balance);

        db_query("UPDATE `users` SET `mined`='$hashes_total_escaped' WHERE uid='$user_uid_escaped' AND `mined`<='$hashes_total_escaped'");

        $cooldown_time=db_query_to_variable("SELECT UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(COALESCE(`cooldown`,0)) FROM `users` WHERE `uid`='$user_uid_escaped'");

        $user_hashes=$hashes_total-$hashes_withdrawn+$hashes_bonus+$hashes_ref;

        echo "<h2>Balance $user_hashes hashes</h2>\n";
        echo "<p>Mined $hashes_total hashes";
        if($hashes_ref>0) echo ", referred $hashes_ref hashes";
        if($hashes_bonus>0) echo ", bonus $hashes_bonus hashes";
        if($hashes_withdrawn>0) echo ",  withdrawn $hashes_withdrawn hashes";
        echo "</p>\n";

        if($coin=='') {
                $currency_data_array=db_query_to_array("SELECT `currency_code`,`currency_name`,`payout_fee`,`project_fee`,`rate_per_mhash`,`img_url`,`payment_id_field` FROM `currency` ORDER BY `currency_code` ASC");

                echo "<h2>Select your coin:</h2>\n";
                echo "<center>\n";
                echo "<table class=currency_grid>\n";
                $n=0;
                foreach($currency_data_array as $currency_data) {
                        if(($n%6)==0) echo "</tr>\n<tr>\n";
                        $n++;
                        $currency_code=$currency_data['currency_code'];
                        $currency_name=$currency_data['currency_name'];
                        $img_url=$currency_data['img_url'];
                        $rate_per_mhash=$currency_data['rate_per_mhash'];
                        $payout_fee=$currency_data['payout_fee'];
                        $project_fee=$currency_data['project_fee'];
                        $payment_id_field=$currency_data['payment_id_field'];

                        $result=$user_hashes*$rate_per_mhash/1000000;
                        $total=$result-$payout_fee-$project_fee;

                        $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);

                        $result=sprintf("%0.8f",$result);
                        if($total>0) $total=sprintf("%0.8f",$total);
                        else $total=sprintf("below fee",$total);

                        if($total>0) echo "<td class='currency_grid_withdrawable' onClick=\"set_coin('$currency_code')\"><img src='$img_url'><br><strong>$currency_name</strong><br><small>$total</small></td>\n";
                        else echo "<td onClick=\"set_coin('$currency_code')\"><img src='$img_url'><br><strong>$currency_name</strong><br><small>$total</small></td>\n";
                        //echo "<td><a href='?coin=$currency_code'><img src='$img_url'></a><br><strong>$currency_name</strong></td>\n";
                        //echo "<td onClick=\"set_coin('$currency_code')\"><img src='$img_url'><br><strong>$currency_name</strong><br>$total</td>\n";
                }

                echo "</table>\n";
                echo "</center>\n";
        } else {
                $coin_escaped=db_escape($coin);
                $currency_data_array=db_query_to_array("SELECT `currency_code`,`currency_name`,`enabled`,`payout_fee`,`project_fee`,`rate_per_mhash`,`img_url`,`payment_id_field`,`user_withdraw_note`
FROM `currency` WHERE `currency_code`='$coin_escaped' ORDER BY `currency_code` ASC");

                $currency_data=array_pop($currency_data_array);

                $currency_code=$currency_data['currency_code'];
                $currency_name=$currency_data['currency_name'];
                $enabled=$currency_data['enabled'];
                $img_url=$currency_data['img_url'];
                $rate_per_mhash=$currency_data['rate_per_mhash'];
                $payout_fee=$currency_data['payout_fee'];
                $project_fee=$currency_data['project_fee'];
                $payment_id_field=$currency_data['payment_id_field'];
                $user_withdraw_note=$currency_data['user_withdraw_note'];

                $result=$user_hashes*$rate_per_mhash/1000000;
                $total=$result-$payout_fee-$project_fee;
                $total_fee=sprintf("%0.8F",$payout_fee+$project_fee);
                $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);
                $fee_hashes=ceil(1000000*$total_fee/$rate_per_mhash);
                $fee_hashes_unit="hashes";

                if($fee_hashes>=1000000) {
                        $fee_hashes=sprintf("%0.2F",$total_fee/$rate_per_mhash);
                        $fee_hashes_unit="Mhashes";
                }

                $result=sprintf("%0.8f",$result);
                if($total>0) {
                        $total=sprintf("%0.8f $currency_code",$total);
                        $withdraw_button="<input type=submit value='send withdraw request'>";
                } else {
                        $total="Nothing";
                        $withdraw_form='Nothing to withdraw';
                }
                echo "<center>\n";
                echo "<p><input type=button value='Change coin' onClick=\"set_coin('');\"></p>\n";
                echo <<<_END
<form name=withdraw method=POST>
<input type=hidden name=action value=withdraw>
<input type=hidden name=token value=$token>
<input type=hidden name=currency_code value='$currency_code'>

_END;
                echo "<h2>Your results in <img src='$img_url'> $currency_name:</h2>\n";
                if($user_withdraw_note!='')
                        echo "<p>$user_withdraw_note</p>\n";
                echo "<table class='data_table'>\n";
                echo "<tr><th align=right>Hashes mined</th><td>$user_hashes</td></tr>\n";
                echo "<tr><th align=right>Exchange rate</th><td>$rate_per_mhash $currency_code per Mhash</td></tr>\n";
                echo "<tr><th align=right>Current balance</th><td>$result $currency_code</td></tr>\n";
                echo "<tr><th align=right>Withdraw fee</th><td>$total_fee $currency_code ($fee_hashes $fee_hashes_unit)</td></tr>\n";
                echo "<tr><th align=right>You can withdraw</th><td>$total</td></tr>\n";
                if($cooldown_time<$cooldown_limit) {
                        echo "<tr><th></th><td>One withdraw in 15 minutes</td></tr>";
                } else if($total>0) {
                        echo "<tr><th align=right>Your address</th><td><input type=text name=payout_address size=40 placeholder='required' required></td></tr>\n";
                        if($payment_id_field) {
                                echo "<tr><th align=right>Payment ID</th><td><input type=text name=payment_id size=40 placeholder='optional'></td></tr>\n";
                        }
                        echo "<tr><th align=right></th><td>$withdraw_button<br>Withdraw takes up to 24h</td></tr>\n";
                } else {
                        echo "<tr><th></th><td>Nothing to withdraw</td></tr>";
                }
                echo "</table>\n";
                echo "</form>";
                echo "</center>\n";
        }

        $payout_data_array=db_query_to_array("SELECT `currency_code`,`address`,`payment_id`,`hashes`,`rate_per_mhash`,`amount`,`payout_fee`,`project_fee`,`total`,`tx_id`,`timestamp` FROM `payouts` WHERE `user_uid`='$user_uid_escaped' OR `session`='$session_escaped' ORDER BY `timestamp` DESC LIMIT 10");

        $badges_array=get_user_badges($user_uid);
        if(count($badges_array)>0) {
                echo "<h2>Your achievements</h2>";
                echo implode("&nbsp;",$badges_array);
        }

        echo "<h2>Your links</h2>\n";
        echo "<p>Earn 1 % of hashes, mined by each your referral. Mine without logging in with miner link.</p>\n";
        echo "<center>\n";
        echo "<table class=data_table>\n";
        $ref_link="https://hivepool.arikado.ru/?ref=$user_uid";
        $miner_link="https://hivepool.arikado.ru/?miner=$user_uid";
        echo "<tr><th align=right>Your referral link:</th><td><input type=text size=50 value='$ref_link'></td>\n";
        echo "<tr><th align=right>Your miner link:</th><td><input type=text size=50 value='$miner_link'></td>\n";
        echo "</table>\n";

        echo "<h2>Your payouts</h2>\n";
        echo "<center>\n";
        echo "<table class=data_table>\n";
        echo "<tr><th>Address</th><th>Hashes</th><th>Total</th><th>Transaction</th><th>Timestamp</th></tr>\n";

        foreach($payout_data_array as $payout_data) {
                $currency_code=$payout_data['currency_code'];
                $address=$payout_data['address'];
                $payment_id=$payout_data['payment_id'];
                $hashes=$payout_data['hashes'];
                $rate_per_mhash=$payout_data['rate_per_mhash'];
                $amount=$payout_data['amount'];
                $payout_fee=$payout_data['payout_fee'];
                $project_fee=$payout_data['project_fee'];
                $total=$payout_data['total'];
                $tx_id=$payout_data['tx_id'];
                $timestamp=$payout_data['timestamp'];

                $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);
                $total=sprintf("%0.8f",$total);
                $fee=$payout_fee+$project_fee;

                if($payment_id!='') $address.="<br>PID $payment_id";

                if($tx_id=='') $tx_id="not send yet";

                echo "<tr><td>$address</td><td>$hashes</td><td>$total $currency_code</td><td>$tx_id</td><td>$timestamp</td></tr>\n";
        }
        echo "</table>\n";
        echo "</center>\n";

        die();
}


echo <<<_END
<!DOCTYPE html>
<html>
<head>
<title>Coinhive pool</title>
<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" type="text/css" href="common.css">
<script src='jquery-3.3.1.min.js'></script>
<script src="https://authedmine.com/lib/authedmine.min.js"></script>
<script src="https://authedmine.com/lib/simple-ui.min.js" async></script>
<link rel="icon" href="favicon.png" type="image/png">
<style>
body {
        font-family: sans-serif;
        text-align:center;
}
.data_table {
        border: 1px solid gray;
        border-collapse: collapse;
}
.data_table tr, .data_table td, .data_table th {
        border: 1px solid gray;
        padding: 0.5px 1em;
}

.currency_grid td {
        border: 0;
        width: 100px;
        padding: 1em;
        text-align:center;
}
.currency_grid .currency_grid_withdrawable {
        background-color:#DFD;
}
.currency_grid td:hover {
        background-color:lightblue;
}
</style>
</head>
<body>
<h1>Coinhive pool</h1>
$message
<p>
$login_form
</p>

$miner_form
<div id=balance>Loading data, please wait...</div>
<input type=hidden id=coin value='$coin_html'>

_END;

echo <<<_END
<script>
$( document ).ready(refresh_data());

function refresh_data() {
        var coin=document.getElementById('coin').value;
        $('#balance').load('?json=1&coin='+coin);
        setTimeout('refresh_data()',60000);
}

function set_coin(coin) {
        document.getElementById('coin').value=coin;
        $('#balance').load('?json=1&coin='+coin);
}
</script>

<hr width=10%>
<p>&copy; 2018 MineAnyCoin Ltd. Support email: <a href='mailto:sau412@gmail.com'>sau412@gmail.com</a></p>
</body>
</html>

_END;
?>
