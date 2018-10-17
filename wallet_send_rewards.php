<?php
// Only for command line
if(!isset($argc)) die();

require_once("settings.php");
require_once("db.php");
require_once("core.php");

// Coin code (e.g. BTC, GRC, DOGE)
$coin_code="";

// Wallet RPC variables
$wallet_rpc_host="";
$wallet_rpc_port="";
$wallet_rpc_login="";
$wallet_rpc_password="";
$wallet_rpc_wallet_passphrase="";

// Send query to gridcoin client
function wallet_rpc_send_query($query) {
        global $wallet_rpc_host,$wallet_rpc_port,$wallet_rpc_login,$wallet_rpc_password;

        // Setup cURL
        $ch=curl_init("http://$wallet_rpc_host:$wallet_rpc_port");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch,CURLOPT_USERPWD,"$wallet_rpc_login:$wallet_rpc_password");
        curl_setopt($ch,CURLOPT_POSTFIELDS,$query);

        // Send query
        $result=curl_exec($ch);

        curl_close($ch);

        return $result;
}

// Get balance
function wallet_rpc_get_balance() {
        $query='{"id":1,"method":"getbalance","params":[]}';
        $result=wallet_rpc_send_query($query);
        $data=json_decode($result);
        return $data->result;
}

// Unlock wallet
function wallet_rpc_unlock_wallet() {
        global $wallet_rpc_wallet_passphrase;
        $query='{"id":1,"method":"walletpassphrase","params":["'.$wallet_rpc_wallet_passphrase.'",60]}';
        $result=wallet_rpc_send_query($query);
        $data=json_decode($result);
        if($data->error == NULL) return TRUE;
        else return FALSE;
}

// Lock wallet
function wallet_rpc_lock_wallet() {
        $query='{"id":1,"method":"walletlock","params":[]}';
        $result=wallet_rpc_send_query($query);
        $data=json_decode($result);
        if($data->error == NULL) return TRUE;
        else return FALSE;
}

// Validate address
function wallet_rpc_validate_address($wallet_address) {
        if(!preg_match('/^[0-9A-Za-z]+$/',$wallet_address)) return FALSE;
        $query='{"id":1,"method":"validateaddress","params":["'.$wallet_address.'"]}';
        $result=wallet_rpc_send_query($query);
        $data=json_decode($result);

        if($data->error == NULL) {
                if($data->result->isvalid == TRUE) return TRUE;
                else return FALSE;
        } else return FALSE;
}

// Send coins
function wallet_rpc_send($wallet_address,$amount) {
        $query='{"id":1,"method":"sendtoaddress","params":["'.$wallet_address.'",'.$amount.']}';

        $result=wallet_rpc_send_query($query);

        $data=json_decode($result);
        if($data->error == NULL) return $data->result;
        else return FALSE;
}

db_connect();

// Get balance
$current_balance=wallet_rpc_get_balance();
echo "Current balance: $current_balance $coin_code\n";

// Get payout information for this coin
$payout_data_array=db_query_to_array("SELECT `uid`,`address`,`total` FROM `payouts` WHERE `currency_code`='$coin_code' AND `tx_id` IS NULL");

if(count($payout_data_array)==0) {
        echo "No unsend payouts\n";
        die();
}

// Unlock wallet
if(wallet_rpc_unlock_wallet() == FALSE) {
        echo "Unlock wallet error\n";
        auth_log("Unlock wallet error");
        die();
}

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
                                db_query("UPDATE `payouts` SET `txid`='$txid_escaped' WHERE `uid`='$uid_escaped'");
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
                        db_query("UPDATE `payouts` SET `txid`='invalid address' WHERE `uid`='$uid_escaped'");
                }
                echo "----\n";
        } else {
                // No funds
                echo "Insufficient funds for sending rewards";
                write_log("Insufficient funds for sending rewards");
                break;
        }
}

// Lock wallet
if(wallet_rpc_lock_wallet() == FALSE) {
        echo "Lock wallet error\n";
        auth_log("Lock wallet error");
        die();
}

?>
