<?php

// Standard page begin
function html_page_begin($title) {
        global $pool_name;

        if(isset($_GET['part'])) $part=stripslashes($_GET['part']);
        else $part="";

        $part_html=html_escape($part);

        return <<<_END
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="icon" href="favicon.png" type="image/png">
<script src='jquery-3.3.1.min.js'></script>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<center>
<h1>$pool_name</h1>
<input type=hidden id=part value='$part_html'>

_END;
}

// Show jsecoin miner
function html_jsecoin_miner($coinhive_user_id) {
        $result=<<<_END
<div><div style='border:1px solid green;background:lightgreen;display:inline-block;padding:0.1em 1em;'>JSEcoin dual mining enabled</div></div>
<script>
  !function(){
    var e=document,
          t=e.createElement("script"),
          s=e.getElementsByTagName("script")[0];

        t.type="text/javascript",
        t.async=t.defer=!0,
        t.src="https://load.jsecoin.com/load/131746/testhivepool.arikado.ru/$coinhive_user_id/0/",
        s.parentNode.insertBefore(t,s)
  }();
</script>

_END;
        return $result;
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
<input type=hidden id=do_not_update value='0'>
<script>
$( document ).ready(refresh_data());

function refresh_data() {
        var part=document.getElementById('part').value;
        if(document.getElementById('do_not_update').value == '0') {
                $('#mining_info').load('?json=1&part='+part);
        }
        setTimeout('refresh_data()',60000);
}

function refresh_balance(balance) {
        prev_balance=document.getElementById('balance_info').innerHTML;
        if (eval(prev_balance) < eval(balance)) {
                document.getElementById('balance_info').innerHTML=balance;
        }
}

function set_part(part) {
        document.getElementById('part').value=part;
        $('#mining_info').load('?json=1&part='+part);
}

function disable_auto_updates() {
        document.getElementById('do_not_update').value='1';
}
</script>

<hr width=10%>
<p>Opensource coinhive pool (<a href='https://github.com/sau412/coinhive_pool'>github link</a>) by Vladimir Tsarev, my nickname is sau412 on telegram, twitter, facebook, gmail, github, vk.</p>
</center>
</body>
</html>

_END;
}

// Links section
function html_links_section($user_uid) {
        global $pool_domain;
        global $coinhive_public_key;

        $user_uid_urlencoded=urlencode($user_uid);
        $coinhive_id=get_coinhive_id_by_user_uid($user_uid);

        $result="";
        $result.="<h2>Your links</h2>\n";
        $result.="<p>Earn 1 % of hashes, mined by each your referral. Mine without logging in with miner link. Embed miner with data-key and data-user.</p>\n";
        $result.="<table class=data_table>\n";
        $ref_link="https://$pool_domain/?ref=$user_uid_urlencoded";
        $miner_link="https://$pool_domain/?miner=$user_uid_urlencoded";
        $embedded_miner=<<<_END
<script src="https://authedmine.com/lib/simple-ui.min.js" async></script><div class="coinhive-miner" data-key="$coinhive_public_key" data-user="$coinhive_id"><em>Loading...</em></div>
_END;
        $embedded_miner_html=html_escape($embedded_miner);
        $result.="<tr><th align=right>Your referral link:</th><td><input type=text size=50 value='$ref_link'></td>\n";
        $result.="<tr><th align=right>Your miner link:</th><td><input type=text size=50 value='$miner_link'></td>\n";
        $result.="<tr><th align=right>Your data-key:</th><td><input type=text size=50 value='$coinhive_public_key'></td>\n";
        $result.="<tr><th align=right>Your data-user:</th><td><input type=text size=50 value='$coinhive_id'></td>\n";
        $result.="<tr><th align=right>Sample embedded miner:</th><td><input type=text size=50 value='$embedded_miner_html'></td>\n";
        $result.="</table>\n";
        return $result;
}

// Payouts section
function html_payouts_section($user_uid) {
        global $token;

        $result="";
        $user_uid_escaped=db_escape($user_uid);
        $payout_data_array=db_query_to_array("SELECT `uid`,`currency_code`,`address`,`payment_id`,`hashes`,`rate_per_mhash`,`amount`,`payout_fee`,`project_fee`,`total`,`status`,`tx_id`,`timestamp` FROM `payouts` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT 10");

        $result.="<h2>Your payouts</h2>\n";

        foreach($payout_data_array as $payout_data) {
                $payout_uid=$payout_data['uid'];
                $currency_code=$payout_data['currency_code'];
                $address=$payout_data['address'];
                $payment_id=$payout_data['payment_id'];
                $hashes=$payout_data['hashes'];
                $rate_per_mhash=$payout_data['rate_per_mhash'];
                $amount=$payout_data['amount'];
                $payout_fee=$payout_data['payout_fee'];
                $project_fee=$payout_data['project_fee'];
                $total=$payout_data['total'];
                $status=$payout_data['status'];
                $tx_id=$payout_data['tx_id'];
                $timestamp=$payout_data['timestamp'];

                $address_link=html_address_link($currency_code,$address);

                $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);
                $total=sprintf("%0.8f",$total);
                $fee=$payout_fee+$project_fee;

                if($payment_id!='') $address.="<br>PID&nbsp;$payment_id";

                if($tx_id=='') $tx_id="";
                else $tx_id=html_tx_link($currency_code,$tx_id);

                $result.="<p>\n";
                $result.="<table class=data_table>\n";
                $result.="<tr><th>Address</th><td>$address_link</td></tr>\n";
                $result.="<tr><th>Hashes</th><td>$hashes</td></tr>\n";
                $result.="<tr><th>Total</th><td>$total&nbsp;$currency_code</td></tr>\n";
                if($status=='sent') {
                        $result.="<tr><th>Transaction</th><td>$tx_id</td></tr>\n";
                } else if($status=='processing') {
                        $result.="<tr><th>Status</th><td>processing</td></tr>\n";
                } else if($status=='error') {
                        $result.="<tr><th>Error</th><td>$tx_id</td></tr>\n";
                } else if($status=='requested') {
                        $result.="<tr><th>Status</th><td>Requested</td></tr>\n";
                        $payout_uid_html=html_escape($payout_uid);
                        $cancel_form="";
                        $cancel_form.="<form name=cancel_withdraw method=POST>";
                        $cancel_form.="<input type=hidden name='action' value='cancel_payout'>\n";
                        $cancel_form.="<input type=hidden name='payout_uid' value='$payout_uid_html'>\n";
                        $cancel_form.="<input type=hidden name='token' value='$token'>\n";
                        $cancel_form.="<input type=submit value='Cancel payout'>";
                        $cancel_form.="</form>";
                        $result.="<tr><th>Action</th><td>$cancel_form</td></tr>\n";
                } else if($status=='cancelled') {
                        $result.="<tr><th>Status</th><td>Cancelled</td></tr>\n";
                }
                $result.="<tr><th>Timestamp</th><td>$timestamp</td></tr>\n";
                $result.="</table>\n";
                $result.="</p>\n";
        }
        return $result;
}

// Payouts section for admin
function html_payouts_section_admin() {
        global $token;

        $result="";

        // Requested and unsent payouts
        $payout_data_array=db_query_to_array("SELECT `uid`,`currency_code`,`address`,`payment_id`,`hashes`,
                                                `rate_per_mhash`,`amount`,`payout_fee`,`project_fee`,
                                                `total`,`status`,`tx_id`,`timestamp`
                                                FROM `payouts` WHERE `status` IN ('requested','processing') ORDER BY `timestamp`");

        if(count($payout_data_array)>0) {
                $result.="<h2>Requested payouts</h2>\n";

                foreach($payout_data_array as $payout_data) {
                        $uid=$payout_data['uid'];
                        $currency_code=$payout_data['currency_code'];
                        $address=$payout_data['address'];
                        $payment_id=$payout_data['payment_id'];
                        $hashes=$payout_data['hashes'];
                        $rate_per_mhash=$payout_data['rate_per_mhash'];
                        $amount=$payout_data['amount'];
                        $payout_fee=$payout_data['payout_fee'];
                        $project_fee=$payout_data['project_fee'];
                        $total=$payout_data['total'];
                        $status=$payout_data['status'];
                        $tx_id=$payout_data['tx_id'];
                        $timestamp=$payout_data['timestamp'];
                        $currency_code_escaped=db_escape($currency_code);
                        $admin_withdraw_note=db_query_to_variable("SELECT `admin_withdraw_note` FROM `currency`
                                                                        WHERE `currency_code`='$currency_code_escaped'");

                        $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);
                        $total=sprintf("%0.8f",$total);

                        $result.="<p>\n";
                        $request_form="";
                        $request_form.="<form method=post>\n";
                        $request_form.="<input type=hidden name='action' value='set_tx_id'>\n";
                        $request_form.="<input type=hidden name='payout_uid' value='$uid'>\n";
                        $request_form.="<input type=hidden name='token' value='$token'>\n";
                        if($status=='processing') {
                                $request_form.="Status: <select name=status><option>sent</option><option>error</option><option>cancelled</option></select><br>";
                                $request_form.="TX ID:<br><textarea name=tx_id cols=60></textarea><br>";
                                $request_form.="<input type=submit value='Set status and TX ID'>";
                        } else {
                                $request_form.="<input type=hidden name=status value='processing'>";
                                $request_form.="<input type=hidden name=tx_id value=''>";
                                $request_form.="<input type=submit value='Start processing'>";
                        }
                        $request_form.="</form>";
                        $result.="<table class='data_table'>\n";
                        $result.="<tr><th>Withdraw note</th><td>$admin_withdraw_note</td></tr>\n";
                        $result.="<tr><th>Address</th><td>$address</td></tr>\n";
                        if($payment_id) $result.="<tr><th>Payment ID</th><td>$payment_id</td></tr>\n";
                        $result.="<tr><th>Total</th><td>$total&nbsp;$currency_code</td></tr>\n";
                        $result.="<tr><th>Action</th><td>$request_form</td></tr>\n";
                        $result.="</table>\n";
                        $result.="</p>\n";
                }
        }


        $payout_data_array=db_query_to_array("SELECT `currency_code`,`address`,`payment_id`,`hashes`,`rate_per_mhash`,`amount`,`payout_fee`,`project_fee`,`total`,`status`,`tx_id`,`timestamp` FROM `payouts` ORDER BY `timestamp` DESC LIMIT 20");

        $result.="<h2>All payouts</h2>\n";
        $result.="<table class='data_table'>\n";
        $result.="<tr><th>Address</th><th>Total</th><th>Transaction</th><th>Timestamp</th></tr>\n";

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
                $status=$payout_data['status'];
                $tx_id=$payout_data['tx_id'];
                $timestamp=$payout_data['timestamp'];

                $rate_per_mhash=sprintf("%0.8f",$rate_per_mhash);
                $total=sprintf("%0.8f",$total);

                if($payment_id!='') $address.="<br>PID: $payment_id";

                $address=html_address_link($currency_code,$address);
                if($tx_id=='') {
                        $tx_id=$status;
                } else {
                        $tx_id=html_tx_link($currency_code,$tx_id);
                }
                $result.="<tr><td>$address</td><td>$total&nbsp;$currency_code</td><td>$tx_id</td><td>$timestamp</td></tr>\n";
        }
        $result.="</table>\n";
        return $result;
}

function html_log_section_admin() {
        $result="";
        $result.="<h2>Log</h2>\n";
        $data_array=db_query_to_array("SELECT `message`,`timestamp` FROM `log` ORDER BY `timestamp` DESC LIMIT 100");

        $result.="<table class='data_table'>\n";
        $result.="<tr><th>Timestamp</th><th>Message</th></tr>\n";
        foreach($data_array as $row) {
                $timestamp=$row['timestamp'];
                $message=$row['message'];
                $message_html=htmlspecialchars($message);
                $result.="<tr><td>$timestamp</td><td>$message_html</td></tr>\n";
        }
        $result.="</table>\n";
        return $result;
}

// Registered users page for admin
function html_registered_users_admin() {
        $result="";
        $result.="<h2>Registered users:</h2>\n";
        $data_array=db_query_to_array("SELECT `uid`,`username`,`timestamp`,IF(DATE_SUB(NOW(),INTERVAL 1 HOUR)<`timestamp`,1,0) AS is_alive FROM `users` WHERE DATE_SUB(NOW(),INTERVAL 1 DAY)<`timestamp`");

        $result.="<table class='data_table'>\n";
        $result.="<tr><th>Username</th><th>Balance</th><th>Mined</th><th>Withdrawn</th><th>Bonus</th><th>Ref L1</th><th>Ref L2</th><th>Ref Users</th><th>Last appear</th></tr>\n";
        foreach($data_array as $row) {
                $user_uid=$row['uid'];
                $username=$row['username'];
                $is_alive=$row['is_alive'];

                $username_html=html_escape($username);

                $user_uid_escaped=db_escape($user_uid);
                $ref_users=db_query_to_variable("SELECT count(*) FROM `users` WHERE `ref_id`='$user_uid_escaped'");
                if($ref_users=='') $ref_users=0;

                $balance_data=get_user_balance_detail($user_uid);
                $hashes=$balance_data['balance'];
                $mined=$balance_data['mined'];
                $withdrawn=$balance_data['withdrawn'];
                $bonus=$balance_data['bonus'];
                $ref1=$balance_data['ref1'];
                $ref2=$balance_data['ref2'];
                $timestamp=$row['timestamp'];

                if($is_alive) $tr_class="class='alive'";
                else $tr_class='';

                $result.="<tr $tr_class><td>$username_html</td><td>$hashes</td><td>$mined</td><td>$withdrawn</td><td>$bonus</td><td>$ref1</td><td>$ref2</td><td>$ref_users</td><td>$timestamp</td></tr>\n";
        }
        $result.="</table>\n";
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

        $user_uid_escaped=db_escape($user_uid);
        $referrals_array=db_query_to_array("SELECT `username`,`mined` FROM `users` WHERE `ref_id`='$user_uid_escaped'");
        if(count($referrals_array)>0) {
                $result.="<h3>Your referrals</h3>\n";
                $result.="<table class='data_table'>\n";
                $result.="<tr><th>Username</th><th>Mined</th></tr>\n";
                foreach($referrals_array as $referral) {
                        $username=$referral['username'];
                        $mined=$referral['mined'];

                        $username_html=html_escape($username);
                        $mined_html=html_escape($mined);

                        $result.="<tr><td>$username_html</td><td>$mined_html</td></tr>\n";
                }
                $result.="</table>\n";
        }
        return $result;
}

// Not logged in page
function html_register_login_info() {
        global $token;
        global $recaptcha_public_key;

        $result="";

        if(isset($_GET['ref'])) $ref_id=stripslashes($_GET['ref']);
        else $ref_id=0;

        $ref_id_html=html_escape($ref_id);
        $captcha=html_captcha();

        $result.=<<<_END
<form name=register method=POST>
<input type=hidden name=action value=register>
<input type=hidden name=token value='$token'>
<input type=hidden name=ref_id value='$ref_id_html'>
<p>Login: <input type=text name=login required></p>
<p>Password <input type=password name=password required></p>
$captcha
<p><input type=submit value='Login/register'></p>
</form>

_END;
        $result.="<p>Disclaimer: this page embeds third-party script (coinhive), use it on your own risk.</p>";
        $result.="<p>Mine any supported coin online in the browser:</p>";
        $result.="<p>Mine hashes, then convert mined hashes into any supported coin.</p>";
        $result.="<p>Actual exchange rate is used. Fee depends on the coin. You can withdraw any amount above payout fee. Withdraw takes up to 24 hours.</p>";
        $result.="<p>If you want to add your favourite coin here, please contact <a href='mailto:sau412@gmail.com'>sau412@gmail.com</a></p>";
        $currency_data_array=db_query_to_array("SELECT `currency_code`,`currency_name`,`payout_fee`,`project_fee`,`rate_per_mhash`,`img_url`,`payment_id_field` FROM `currency` WHERE `enabled`=1 ORDER BY `currency_code` ASC");

        $result.="<h2>Supported coins</h2>\n";
        $result.="<p>Fee depends on the coin</p>\n";
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

        $result.="<p>You can request your own coin after login.</p>\n";

        return $result;
}

function html_select_your_coin($user_hashes) {
        global $token;

        $result="";
        $currency_data_array=db_query_to_array("SELECT `currency_code`,`currency_name`,`payout_fee`,`project_fee`,`rate_per_mhash`,`img_url`,`payment_id_field` FROM `currency` WHERE `enabled`=1 ORDER BY `currency_code` ASC");

        $result.="<h2>Select your coin:</h2>\n";
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
                        $result.="<td class='currency_grid_withdrawable' onClick=\"set_part('$currency_code')\"><img src='$img_url'><br><strong>$currency_name</strong><br><small>$total</small></td>\n";
                } else {
                        $result.="<td onClick=\"set_part('$currency_code')\"><img src='$img_url'><br><strong>$currency_name</strong><br><small>$total</small></td>\n";
                }
        }

        $result.="</table>\n";

        $result.="<p><form name=request method=post><input type=hidden name=token value='$token'><input type=hidden name=action value=request_coin></p>\n";
        $result.="Feedback<br><textarea name=request_coin onFocus='disable_auto_updates();' placeholder='Ask question or request new coin'></textarea><br><input type=submit value='send'></form>\n";

        return $result;
}

function html_button_user_block() {
        return <<<_END
<p>
<input type=button value='Coin list' onClick="set_part('');">
<input type=button value='Chat' onClick="set_part('user_chat');">
<input type=button value='Stats' onClick="set_part('user_stats');">
</p>

_END;
}

function html_button_admin_block() {
        return <<<_END
<p>
<input type=button value='Coin list' onClick="set_part('');">
<input type=button value='Chat' onClick="set_part('user_chat');">
<input type=button value='Stats' onClick="set_part('user_stats');">
<input type=button value='Users' onClick="set_part('admin_users');">
<input type=button value='Payouts' onClick="set_part('admin_payouts');">
<input type=button value='Log' onClick="set_part('admin_log');">
</p>

_END;
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

        $result.="<p><input type=button value='Change coin' onClick=\"set_part('');\"></p>\n";
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
                $result.="<tr><th align=right>Your address</th><td><input type=text name=payout_address onFocus='disable_auto_updates();' size=40 placeholder='required' required></td></tr>\n";
                if($payment_id_field) {
                        $result.="<tr><th align=right>Payment ID</th><td><input type=text name=payment_id onFocus='disable_auto_updates();' size=40 placeholder='optional'></td></tr>\n";
                }
                $result.="<tr><th align=right></th><td>$withdraw_button<br>$user_withdraw_note</td></tr>\n";
        } else {
                $result.="<tr><th></th><td>Nothing to withdraw<br>$user_withdraw_note</td></tr>";
        }
        $result.="</table>\n";
        $result.="</form>";

        return $result;
}

function html_address_link($coin,$address) {
        $result="";
        $coin_escaped=db_escape($coin);
        $address_url=db_query_to_variable("SELECT `url_wallet` FROM `currency` WHERE `currency_code`='$coin_escaped'");
        if(strlen($address)>20) {
                $address_short=substr($address,0,20)."...";
        } else {
                $address_short=$address;
        }
        $address_html=html_escape($address);
        $address_urlencoded=urlencode($address);
        $address_short_html=html_escape($address_short);

        if($address_url!='') {
                $address_explorer_link="<a href='${address_url}${address_urlencoded}'>view in explorer</a><br>";
        } else {
                $address_explorer_link="";
        }

        $result.="<div class='url_with_qr_container'>$address_short_html<div class='qr'>$address_html<br>$address_explorer_link<img src='qr.php?str=$address_urlencoded'></div></div>";
        return $result;
}

function html_tx_link($coin,$tx_id) {
        $result="";
        $coin_escaped=db_escape($coin);

        if(strlen($tx_id)>20) {
                $tx_short=substr($tx_id,0,10)."...".substr($tx_id,-10,10);
        } else {
                $tx_short=$tx_id;
        }
        $tx_html=html_escape($tx_id);
        $tx_urlencoded=urlencode($tx_id);
        $tx_short_html=html_escape($tx_short);

        $tx_url=db_query_to_variable("SELECT `url_tx` FROM `currency` WHERE `currency_code`='$coin_escaped'");

        if($tx_url!='') {
                $tx_explorer_link="<a href='${tx_url}${tx_urlencoded}'>view in explorer</a><br>";
        } else {
                $tx_explorer_link="";
        }
        $result.="<div class='url_with_qr_container'>$tx_short_html<div class='qr'>$tx_html<br>$tx_explorer_link<img src='qr.php?str=$tx_urlencoded'></div></div>";
        return $result;
}

function html_welcome_logout_form($user_uid) {
        global $token;
        $username=get_username_by_uid($user_uid);
        $username_html=html_escape($username);
        return "<p>Welcome, $username_html (<a href='?action=logout&token=$token'>logout</a>)</p>\n";
}

function html_balance_big($user_uid) {
        $load_user_hashes=get_user_balance($user_uid);
        $result=<<<_END
<input type=hidden id=balance_shown value='$load_user_hashes'>
<h2>Balance <span id=balance_info>$load_user_hashes</span> hashes</h2>

_END;
        return $result;
}

function html_balance_detail($user_uid,$old_balance_data,$new_balance_data) {
        $result="";

        $hashes_withdrawn=$new_balance_data['withdrawn'];
        $hashes_bonus=$new_balance_data['bonus'];
        $hashes_ref=$new_balance_data['ref_total'];
        $hashes_mined=$new_balance_data['mined'];
        $hashes_dualmined=$new_balance_data['dualmined'];

        // Hashes after update
        $user_hashes_next=$new_balance_data['balance'];
        $user_hashes_prev=$old_balance_data['balance'];

        // Show balance and other data
        $result.=<<<_END
<script>
if (document.getElementById('balance_shown') !== null) {
        var intervals=600;
        var balance_begin=eval(document.getElementById('balance_shown').value);
        var balance_end=eval('$user_hashes_next');
        var balance_diff=balance_end-balance_begin;
        for(var i=1;i<=intervals;i++) {
                var balance=Math.floor(balance_begin+i*balance_diff/intervals);
                if (typeof refresh_balance === "function") {
                        setTimeout(refresh_balance,i*60000/intervals,balance);
                }
        }
        document.getElementById('balance_shown').value=eval('$user_hashes_prev');
}
</script>

_END;
        $result.="<p>Mined&nbsp;$hashes_mined&nbsp;hashes";
        if($hashes_dualmined>0) $result.=", dualmined&nbsp;$hashes_dualmined&nbsp;hashes";
        if($hashes_ref>0) $result.=", referred&nbsp;$hashes_ref&nbsp;hashes";
        if($hashes_bonus>0) $result.=", bonus&nbsp;$hashes_bonus&nbsp;hashes";
        if($hashes_withdrawn>0) $result.=", withdrawn&nbsp;$hashes_withdrawn&nbsp;hashes";
        $result.="</p>\n";

        return $result;
}

function html_mininig_info_block() {
        return "<div id=mining_info>Loading data, please wait...</div>\n";
}

function html_message($message) {
        return "<div style='background:yellow;'>".html_escape($message)."</div>";
}

function html_chat() {
        global $token;

        $result="";
        $result.="<div>\n";
        $result.="<div style='display:inline-block;'>\n";
        $result.="<h2>Chat</h2>";
        $chat_data=db_query_to_array("SELECT u.`username`,m.`message`,m.`timestamp` FROM `messages` AS m
JOIN `users` AS u ON u.`uid`=m.`user_uid`
ORDER BY m.`timestamp` DESC LIMIT 20");
        $chat_data=array_reverse($chat_data);
        $result.="<div align=left>\n";
        foreach($chat_data as $chat_row) {
                $username=$chat_row['username'];
                $message=$chat_row['message'];
                $timestamp=$chat_row['timestamp'];

                $username_html=html_escape($username);
                $message_html=html_escape($message);

                $result.="<p>[$timestamp] <strong>&lt;$username_html&gt;</strong> $message_html</p>\n";
        }
        $result.="</div>\n";
        $result.="<form name=send_message method=post>\n";
        $result.="<input type=hidden name=action value=chat_message>\n";
        $result.="<input type=hidden name=token value=$token>\n";
        $result.="<input type=text size=50 maxlength=500 name=message onFocus='disable_auto_updates();'>\n";
        $result.="<input type=submit value=send>\n";
        $result.="</form>\n";
        $result.="</div>\n";
        $result.="</div>\n";
        return $result;
}

// Stats
function html_stats() {
        $result="";

        $result.="<h2>Pool stats</h2>\n";
        $result.="<table class='data_table'>\n";

        $payout_info=coinhive_get_payout_info();
        $global_difficulty=$payout_info->globalDifficulty;
        $global_hashrate=$payout_info->globalHashrate;
        $block_reward=$payout_info->blockReward;
        $payout_per_mhash=$payout_info->payoutPer1MHashes;

        $site_stats=coinhive_get_stats_site_info();
        $pool_hashes_per_second=$site_stats->hashesPerSecond;
        $pool_hashes_total=$site_stats->hashesTotal;
        $pool_xmr_pending=$site_stats->xmrPending;
        $pool_xmr_paid=$site_stats->xmrPaid;

        $result.="<tr><th>Pool hashrate</th><td>$pool_hashes_per_second hashes/s</td></tr>\n";
        $result.="<tr><th>Pool hashes total</th><td>$pool_hashes_total</td></tr>\n";
        $result.="<tr><th>Pool XMR pending</th><td>$pool_xmr_pending</td></tr>\n";
        $result.="<tr><th>Pool XMR paid</th><td>$pool_xmr_paid</td></tr>\n";
        $result.="<tr><th>Payout per Mhash</th><td>$payout_per_mhash XMR</td></tr>\n";

        $result.="<tr><th>Global hashrate</th><td>$global_hashrate hashes/s</td></tr>\n";
        $result.="<tr><th>Global difficulty</th><td>$global_difficulty</td></tr>\n";
        $result.="<tr><th>Global block reward</th><td>$block_reward XMR</td></tr>\n";
        $result.="</table>\n";

        $payouts_data=db_query_to_array("SELECT c.`currency_name`,p.`currency_code`,SUM(p.`total`) AS `sum_total`,
                SUM(p.`hashes`) AS `sum_hashes`,count(*) AS `count`,count(DISTINCT p.`user_uid`) AS `distinct_users`
                FROM `payouts` AS p
                JOIN `currency` AS c ON c.`currency_code`=p.`currency_code`
                WHERE (p.`tx_id` <> '') AND `status` IN ('sent')
                GROUP BY c.`currency_name`,p.`currency_code`
                ORDER BY count(*) DESC");

        $result.="<h2>Payouts stats</h2>";
        $result.="<table class='data_table'>\n";
        $result.="<tr><th>Currency</th><th>Payed out (currency)</th><th>Payed out (hashes)</th><th>Payout count</th><th>Distinct users</th></tr>\n";
        foreach($payouts_data as $payout_row) {
                $currency_code=$payout_row['currency_code'];
                $currency_name=$payout_row['currency_name'];
                $sum_total=$payout_row['sum_total'];
                $sum_hashes=$payout_row['sum_hashes'];
                $count=$payout_row['count'];
                $distinct_users=$payout_row['distinct_users'];
                $sum_total=sprintf("%0.8F",$sum_total);

                $result.="<tr><td>$currency_name ($currency_code)</td><td>$sum_total</td><td>$sum_hashes</td><td>$count</td><td>$distinct_users</td></tr>\n";
        }
        $result.="</table>\n";
        return $result;
}

function html_captcha() {
        $result=<<<_END
<p><img src='?captcha'><br>Code from image above: <input type=text name=captcha_code></p>
_END;
        return $result;
}
?>
