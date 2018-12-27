<?php
// Only for command line
if(!isset($argc)) die();

// Gridcoinresearch send rewards
require_once("settings_remote.php");
require_once("db.php");
require_once("core.php");
require_once("gridcoin_web_wallet.php");

// Gridcoin online wallet API key
$grc_api_url="https://wallet.arikado.ru/api.php";
$grc_api_key="";

// Check if unsent rewards exists
db_connect();

// Get balance
$current_balance=grc_web_get_balance();
echo "Current balance: $current_balance\n";

$unsent_count=db_query_to_variable("SELECT count(*) FROM `payouts` WHERE `currency_code` IN ('GRC') AND (`tx_id` IS NULL OR `tx_id`='') AND `status` IN ('requested','processing')");

if($unsent_count==0) {
        echo "No unsent rewards for now\n";
        die();
}

// Get payout information for GRC
$payout_data_array=db_query_to_array("SELECT `uid`,`address`,`total`,`wallet_send_uid` FROM `payouts` WHERE `currency_code` IN ('GRC') AND (`tx_id` IS NULL OR `tx_id`='') AND `status` IN ('requested','processing')");

foreach($payout_data_array as $payout_data) {
        $uid=$payout_data['uid'];
        $grc_address=$payout_data['address'];
        $amount=$payout_data['total'];
        $wallet_send_uid=$payout_data['wallet_send_uid'];
//var_dump($payout_data);
        $uid_escaped=db_escape($uid);

        // If we have funds for this
        if($wallet_send_uid) {
                $tx_data=grc_web_get_tx_status($wallet_send_uid);
//var_dump($tx_data);
                if($tx_data) {
                        switch($tx_data->status) {
                                case 'address error':
                                        echo "Address error wallet uid '$wallet_send_uid' for address '$grc_address' amount '$amount' GRC\n";
                                        write_log("Address error wallet uid '$wallet_send_uid' for address '$grc_address' amount '$amount' GRC");
                                        db_query("UPDATE `payouts` SET `tx_id`='address error' WHERE `uid`='$uid_escaped'");
                                        break;
                                case 'sending error':
                                        echo "Sending error wallet uid '$wallet_send_uid' for address '$grc_address' amount '$amount' GRC\n";
                                        write_log("Sending error wallet uid '$wallet_send_uid' for address '$grc_address' amount '$amount' GRC");
                                        db_query("UPDATE `payouts` SET `tx_id`='sending error',`status`='error' WHERE `uid`='$uid_escaped'");
                                        break;
                                case 'received':
                                case 'pending':
                                case 'sent':
                                        $tx_id=$tx_data->tx_id;
                                        $tx_id_escaped=db_escape($tx_id);
                                        write_log("Sent wallet uid '$wallet_send_uid' for address '$grc_address' amount '$amount' GRC");
                                        echo "Sent wallet uid '$wallet_send_uid' for address '$grc_address' amount '$amount' GRC\n";
                                        db_query("UPDATE `payouts` SET `tx_id`='$tx_id_escaped',`status`='sent' WHERE `uid`='$uid_escaped'");
                                        break;
                        }
                }
        } else if($amount<$current_balance) {
                echo "Sending $amount to $grc_address\n";

                // Send coins, get txid
                $wallet_send_uid=grc_web_send($grc_address,$amount);
                $wallet_send_uid_escaped=db_escape($wallet_send_uid);
                if($wallet_send_uid && is_numeric($wallet_send_uid)) {
                        db_query("UPDATE `payouts` SET `status`='processing',`wallet_send_uid`='$wallet_send_uid_escaped' WHERE `uid`='$uid_escaped'");
                } else {
                        write_log("Sending error, no wallet uid for address '$grc_address' amount '$amount' GRC");
                }
                echo "----\n";
        } else {
                // No funds
                echo "Insufficient funds for sending rewards\n";
                write_log("Insufficient funds for sending rewards");
                break;
        }
}
?>
