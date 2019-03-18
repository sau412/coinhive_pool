<?php
// Only for command line
if(!isset($argc)) die();

require_once("settings_remote.php");
require_once("db.php");
require_once("core.php");
require_once("coin_web_wallet.php");

$coin_api_url=$btc_api_url;
$coin_api_key=$btc_api_key;
$coin_code="BTC";

db_connect();

// Get addresses for new users
$new_array=db_query_to_array("SELECT `uid` FROM `deposits` WHERE `wallet_uid` IS NULL AND `currency`='$coin_code'");
foreach($new_array as $user_info) {
        $uid=$user_info['uid'];
        $result=coin_web_get_new_receiving_address();
        $wallet_uid=$result->uid;
        $uid_escaped=db_escape($uid);
        $wallet_uid_escaped=db_escape($wallet_uid);
        db_query("UPDATE `deposits` SET `wallet_uid`='$wallet_uid_escaped' WHERE `uid`='$uid_escaped'");
}


// Update addresses data for all users
$pending_array=db_query_to_array("SELECT `uid`,`user_uid`,`wallet_uid`,`amount` FROM `deposits` WHERE `wallet_uid` IS NOT NULL AND `currency`='$coin_code'");
foreach($pending_array as $deposit_info) {
        $uid=$deposit_info['uid'];
        $user_uid=$deposit_info['user_uid'];
        $address_uid=$deposit_info['wallet_uid'];
        $prev_received=$deposit_info['amount'];
        $result=coin_web_get_receiving_address($address_uid);
        $address=$result->address;
        $received=$result->received;

        if($address!='') {
                $uid_escaped=db_escape($uid);
                $address_escaped=db_escape($address);
                $received_escaped=db_escape($received);
                db_query("UPDATE `deposits` SET `address`='$address_escaped',`amount`='$received_escaped' WHERE `uid`='$uid_escaped'");
        }

        if($prev_received!=$received) {
                $result_diff=$received-$prev_received;
                if($result_diff>0) {
                        add_user_asset($user_uid,$coin_code,$result_diff);
                }
        }
}

?>
