<?php
// Only for command line
if(!isset($argc)) die();

require_once("settings_remote.php");
require_once("db.php");
require_once("core.php");

// Coin code
$coin_code="BURST";

// Gridcoin RPC variables
$wallet_rpc_url="https://wallet.burst.cryptoguru.org:8125/burst";
$wallet_rpc_account=""; // Like BURST-BADB-H3VU-MXB3-AFKCJ
$wallet_rpc_passphrase=""; // Your 12 words
$tx_fee_NQT=1000000;

// Send query to gridcoin client
function wallet_rpc_send_query($query,$post=FALSE) {
        global $wallet_rpc_url;

        // Setup cURL
        $ch=curl_init($wallet_rpc_url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        if($post==TRUE) {
                curl_setopt($ch,CURLOPT_POST,TRUE);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$query);
        } else {
                curl_setopt($ch,CURLOPT_POST,FALSE);
                curl_setopt($ch,CURLOPT_URL,"$wallet_rpc_url?$query");
        }
        // Send query
        $result=curl_exec($ch);

        curl_close($ch);

        return $result;
}

// Get balance
function wallet_rpc_get_balance() {
        global $wallet_rpc_account;

        $query="requestType=getBalance&account=$wallet_rpc_account";
        $post=FALSE;
        $result=wallet_rpc_send_query($query,$post);

        $data=json_decode($result);

        if(property_exists($data,"balanceNQT")) return ($data->balanceNQT)/100000000;
        else return 0;
}

// Validate address
function wallet_rpc_validate_address($wallet_address) {
        $wallet_address_html=html_escape($wallet_address);
        $query="requestType=getBalance&account=$wallet_address_html";
        $post=FALSE;
        $result=wallet_rpc_send_query($query,$post);

        $data=json_decode($result);

        if(property_exists($data,"balanceNQT")) return TRUE;
        else return FALSE;
}

// Send coins
function wallet_rpc_send($wallet_address,$amount) {
        global $tx_fee_NQT;
        global $wallet_rpc_passphrase;
        $wallet_address_html=html_escape($wallet_address);
        $amountNQT=$amount*100000000;
        $query="requestType=sendMoney&secretPhrase=$wallet_rpc_passphrase&recipient=$wallet_address&amountNQT=$amountNQT&feeNQT=$tx_fee_NQT&deadline=24";
        $post=TRUE;
        $result=wallet_rpc_send_query($query,$post);

        $data=json_decode($result);

        if(property_exists($data,"transaction")) return $data->transaction;
        else return FALSE;
}

db_connect();

// Get payout information for this coin
$payout_data_array=db_query_to_array("SELECT `uid`,`address`,`total` FROM `payouts` WHERE `currency_code`='$coin_code' AND `tx_id` IS NULL");

if(count($payout_data_array)==0) {
        echo "No unsend payouts\n";
        die();
}

// Get balance
$current_balance=wallet_rpc_get_balance();
echo "Current balance: $current_balance $coin_code\n";
if($current_balance==0) die("Balance is zero\n");

// Send payouts
foreach($payout_data_array as $payout_data) {
        $uid=$payout_data['uid'];
        $address=$payout_data['address'];
        $amount=$payout_data['total'];
        $amount=sprintf("%0.8F",$amount);

        // If we have funds for this
        if($amount<$current_balance) {
                echo "Sending $amount $coin_code to $address\n";
                // Check_address
                if(wallet_rpc_validate_address($address)==TRUE) {
                        echo "Address $address is valid\n";
                        // Send coins, get txid
                        $txid=wallet_rpc_send($address,$amount);
                        if($txid != FALSE) {
                                // Write to log
                                echo "Sent $coin_code reward to '$address' reward '$amount'\n";
                                write_log("Sent $coin_code reward to '$address' reward '$amount'");
                                $uid_escaped=db_escape($uid);
                                $txid_escaped=db_escape($txid);
                                db_query("UPDATE `payouts` SET `tx_id`='$txid_escaped' WHERE `uid`='$uid_escaped'");
                                $current_balance-=$amount;
                        } else {
                                // Sending error
                                echo "Sending coinhive reward error address '$address' reward '$amount'\n";
                                write_log("Sending error '$address' reward '$amount'");
                        }
                } else {
                        // Address error
                        echo "Invalid address: $address\n";
                        write_log("Coinhive invalid address '$address' reward '$amount'");
                        $uid_escaped=db_escape($uid);
                        db_query("UPDATE `payouts` SET `tx_id`='invalid address' WHERE `uid`='$uid_escaped'");
                }
                echo "----\n";
        } else {
                // No funds
                echo "Insufficient funds for sending rewards";
                write_log("Insufficient funds for sending rewards");
                break;
        }
}

?>
