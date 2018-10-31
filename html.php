<?php

// Standard page begin
function html_page_begin($title) {
        return <<<_END
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="icon" href="favicon.png" type="image/png">
<script src='jquery-3.3.1.min.js'></script>
<script src="https://authedmine.com/lib/simple-ui.min.js" async></script>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

_END;
}

// Show coinhive miner with given user id
function html_coinhive_frame($coinhive_user_id) {
        global $coinhive_public_key;

        return <<<_END
<script src="https://authedmine.com/lib/simple-ui.min.js" async></script>
<div class="coinhive-miner"
        data-key="$coinhive_public_key"
        data-user="$coinhive_user_id">
        <em>Loading...</em>
</div>

_END;
}

// Page end, scripts and footer
function html_page_end() {
        return <<<_END
<script>
$( document ).ready(refresh_data());

function refresh_data() {
        var coin=document.getElementById('coin').value;
        $('#mining_info').load('?json=1&coin='+coin);
        setTimeout('refresh_data()',60000);
}

function refresh_balance(balance) {
        prev_balance=document.getElementById('balance_info').innerHTML;
        if (eval(prev_balance) < eval(balance)) {
                document.getElementById('balance_info').innerHTML=balance;
        }
}

function set_coin(coin) {
        document.getElementById('coin').value=coin;
        $('#mining_info').load('?json=1&coin='+coin);
}
</script>

<hr width=10%>
<p>Opensource coinhive pool (<a href='https://github.com/sau412/coinhive_pool'>github link</a>) by Vladimir Tsarev, my nickname is sau412 on telegram, twitter, facebook, gmail, github, vk.</p>
</body>
</html>

_END;
}

// Links section
function html_links_section($user_uid) {
        global $pool_domain;

        $user_uid_urlencoded=urlencode($user_uid);
        $result="";
        $result.="<h2>Your links</h2>\n";
        $result.="<p>Earn 1 % of hashes, mined by each your referral. Mine without logging in with miner link.</p>\n";
        $result.="<center>\n";
        $result.="<table class=data_table>\n";
        $ref_link="https://$pool_domain/?ref=$user_uid_urlencoded";
        $miner_link="https://$pool_domain/?miner=$user_uid_urlencoded";
        $result.="<tr><th align=right>Your referral link:</th><td><input type=text size=50 value='$ref_link'></td>\n";
        $result.="<tr><th align=right>Your miner link:</th><td><input type=text size=50 value='$miner_link'></td>\n";
        $result.="</table>\n";
        return $result;
}

// Payouts section
function html_payouts_section($user_uid) {
        $result="";
        $user_uid_escaped=db_escape($user_uid);
        $payout_data_array=db_query_to_array("SELECT `currency_code`,`address`,`payment_id`,`hashes`,`rate_per_mhash`,`amount`,`payout_fee`,`project_fee`,`total`,`tx_id`,`timestamp` FROM `payouts` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT 10");

        $result.="<h2>Your payouts</h2>\n";
        $result.="<center>\n";

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

                if($tx_id=='') $tx_id="not sent yet";

                $result.="<p>\n";
                $result.="<table class=data_table>\n";
                $result.="<tr><th>Address</th><td>$address</td></tr>\n";
                $result.="<tr><th>Hashes</th><td>$hashes</td></tr>\n";
                $result.="<tr><th>Total</th><td>$total $currency_code</td></tr>\n";
                $result.="<tr><th>Transaction</th><td>$tx_id</td></tr>\n";
                $result.="<tr><th>Timestamp</th><td>$timestamp</td></tr>\n";
                $result.="</table>\n";
                $result.="</p>\n";
        }
        $result.="</center>\n";
        return $result;
}

// Achievements
function html_achievements_section($user_uid) {
        $result="";
        $badges_array=get_user_badges($user_uid);
        if(count($badges_array)>0) {
                $result.="<h2>Your achievements</h2>";
                $result.=implode("&nbsp;",$badges_array);
        }
        return $result;
}

// Balance
function html_balance($user_uid) {
        $result="";

}

// Not logged in page
function html_register_login_info() {
        $result="";
        $result.="<p>Disclaimer: this page embeds third-party script (coinhive), use it on your own risk.</p>";
        $result.="<p>Mine any supported coin online in the browser:</p>";
        $result.="<p>Mine hashes, then convert mined hashes into any supported coin.</p>";
        $result.="<p>Actual exchange rate is used. Fee depends on the coin. You can withdraw any amount above payout fee. Withdraw takes up to 24 hours.</p>";
        $result.="<p>If you want to add your favourite coin here, please contact <a href='mailto:sau412@gmail.com'>sau412@gmail.com</a></p>";
        $currency_data_array=db_query_to_array("SELECT `currency_code`,`currency_name`,`payout_fee`,`project_fee`,`rate_per_mhash`,`img_url`,`payment_id_field` FROM `currency` ORDER BY `currency_code` ASC");

        $result.="<h2>Supported coins</h2>\n";
        $result.="<p>Fee depends on the coin</p>\n";
        $result.="<center>\n";
        $result.="<table class=currency_grid>\n";
        $n=0;
        foreach($currency_data_array as $currency_data) {
                if(($n%6)==0) $result.="</tr>\n<tr>\n";
                $n++;
                $currency_code=$currency_data['currency_code'];
                $currency_name=$currency_data['currency_name'];
                $img_url=$currency_data['img_url'];
                $rate_per_mhash=$currency_data['rate_per_mhash'];
                $payout_fee=$currency_data['payout_fee'];
                $project_fee=$currency_data['project_fee'];
                $payment_id_field=$currency_data['payment_id_field'];

                $total_fee=sprintf("%0.8f",$payout_fee+$project_fee);

                $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);

                $result.="<td><img src='$img_url'><br><strong>$currency_name</strong><br><small>$rate_per_mhash<br>per Mhash</small></td>\n";
                }

        $result.="</table>\n";
        $result.="</center>\n";

        return $result;
}

function html_select_your_coin($user_hashes) {
        $result="";
        $currency_data_array=db_query_to_array("SELECT `currency_code`,`currency_name`,`payout_fee`,`project_fee`,`rate_per_mhash`,`img_url`,`payment_id_field` FROM `currency` ORDER BY `currency_code` ASC");

        $result.="<h2>Select your coin:</h2>\n";
        $result.="<center>\n";
        $result.="<table class=currency_grid>\n";
        $n=0;
        foreach($currency_data_array as $currency_data) {
                if(($n%6)==0) $result.="</tr>\n<tr>\n";
                $n++;
                $currency_code=$currency_data['currency_code'];
                $currency_name=$currency_data['currency_name'];
                $img_url=$currency_data['img_url'];
                $rate_per_mhash=$currency_data['rate_per_mhash'];
                $payout_fee=$currency_data['payout_fee'];
                $project_fee=$currency_data['project_fee'];
                $payment_id_field=$currency_data['payment_id_field'];

                $result_in_currency=$user_hashes*$rate_per_mhash/1000000;
                $total=$result_in_currency-$payout_fee-$project_fee;

                $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);

                $result_in_currency=sprintf("%0.8f",$result_in_currency);
                if($total>0) $total=sprintf("%0.8f",$total);
                else $total=sprintf("below fee",$total);

                if($total>0) {
                        $result.="<td class='currency_grid_withdrawable' onClick=\"set_coin('$currency_code')\"><img src='$img_url'><br><strong>$currency_name</strong><br><small>$total</small></td>\n";
                } else {
                        $result.="<td onClick=\"set_coin('$currency_code')\"><img src='$img_url'><br><strong>$currency_name</strong><br><small>$total</small></td>\n";
                }
        }

        $result.="</table>\n";
        $result.="</center>\n";
        return $result;
}

function html_results_in_coin($user_uid,$user_hashes,$coin) {
        global $token;

        $result="";
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

        $result_in_currency=$user_hashes*$rate_per_mhash/1000000;
        $total=$result_in_currency-$payout_fee-$project_fee;
        $total_fee=sprintf("%0.8F",$payout_fee+$project_fee);
        $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);
        if($rate_per_mhash>0) $fee_hashes=ceil(1000000*$total_fee/$rate_per_mhash);
        else $fee_hashes=0;
        $fee_hashes_unit="hashes";

        if($fee_hashes>=1000000) {
                if($rate_per_mhash>0) $fee_hashes=sprintf("%0.2F",$total_fee/$rate_per_mhash);
                else $fee_hashes=0;
                $fee_hashes_unit="Mhashes";
        }

        $result_in_currency=sprintf("%0.8f",$result_in_currency);

        if($total>0) {
                $total=sprintf("%0.8f $currency_code",$total);
                $withdraw_button="<input type=submit value='send withdraw request'>";
        } else {
                $total="Nothing";
                $withdraw_form='Nothing to withdraw';
        }

        $result.="<center>\n";
        $result.="<p><input type=button value='Change coin' onClick=\"set_coin('');\"></p>\n";
        $result.=<<<_END
<form name=withdraw method=POST>
<input type=hidden name=action value=withdraw>
<input type=hidden name=token value=$token>
<input type=hidden name=currency_code value='$currency_code'>

_END;
        $result.="<h2>Your results in <img src='$img_url'> $currency_name:</h2>\n";
        $result.="<table class='data_table'>\n";
        $result.="<tr><th align=right>Hashes mined</th><td>$user_hashes</td></tr>\n";
        $result.="<tr><th align=right>Exchange rate</th><td>$rate_per_mhash $currency_code per Mhash</td></tr>\n";
        $result.="<tr><th align=right>Current balance</th><td>$result_in_currency $currency_code</td></tr>\n";
        $result.="<tr><th align=right>Withdraw fee</th><td>$total_fee $currency_code ($fee_hashes $fee_hashes_unit)</td></tr>\n";
        $result.="<tr><th align=right>You can withdraw</th><td>$total</td></tr>\n";
        if(is_cooltime_active($user_uid)) {
                $result.="<tr><th></th><td>One withdraw in 15 minutes</td></tr>";
        } else if($total>0) {
                $result.="<tr><th align=right>Your address</th><td><input type=text name=payout_address size=40 placeholder='required' required></td></tr>\n";
                if($payment_id_field) {
                        $result.="<tr><th align=right>Payment ID</th><td><input type=text name=payment_id size=40 placeholder='optional'></td></tr>\n";
                }
                $result.="<tr><th align=right></th><td>$withdraw_button<br>$user_withdraw_note</td></tr>\n";
        } else {
                $result.="<tr><th></th><td>Nothing to withdraw<br>$user_withdraw_note</td></tr>";
        }
        $result.="</table>\n";
        $result.="</form>";
        $result.="</center>\n";

        return $result;
}

function html_show_balance_big() {
}

function html_show_balance_detail() {
}

/*

if($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $username=db_query_to_variable("SELECT `username` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $username_html=html_escape($username);
        $login_form=<<<_END
Welcome, $username_html (<a href='?action=logout&token=$token'>logout</a>)

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
                die();
        }

        // Hashes before update
//      $prev_user_hashes=get_user_balance($user_uid);

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

        die();
}


<h1>Coinhive pool</h1>
$message
<p>
$login_form
</p>

$miner_form
$balance_form
<div id=mining_info>Loading data, please wait...</div>
<input type=hidden id=coin value='$coin_html'>

_END;
*/
?>
