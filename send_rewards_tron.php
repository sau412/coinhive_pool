<?php
// Only for command line
if(!isset($argc)) die();

require_once("settings_remote.php");
require_once("db.php");
require_once("core.php");

// Coin code
$coin_code="TRX";

// TRON RPC variables
$api_url="https://api.trongrid.io";
$url_get_balance="$api_url/wallet/getaccount";
$url_validate_address="$api_url/wallet/validateaddress";
$url_create_transaction="$api_url/wallet/createtransaction";
$url_sign_transaction="$api_url/wallet/gettransactionsign";
$url_broadcast_transaction="$api_url/wallet/broadcasttransaction";

$wallet_address="";
$wallet_private_key="";

$alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

// Based on https://github.com/tuaris/CryptoCurrencyPHP/blob/master/Base58.class.php
function base_58_decode($encoded_str) {
        global $alphabet;

        $res = gmp_init(0, 10);
        $length = strlen($encoded_str);
        for($i=0;$i!=$length;$i++) {
                $char=$encoded_str[$i];
                $pos=strpos($alphabet,$char);
//              echo "char '$char' pos '$pos'\n";
                $res = gmp_add(gmp_mul($res,gmp_init(58, 10)),$pos);
        }
        $res = gmp_strval($res, 16);
        $i = 0;
        while($encoded_str[$i]=='1') {
                $res = '00' . $res;
                $i++;
        }
        if(strlen($res)%2 != 0) {
                $res = '0' . $res;
        }
        return substr($res,0,42);
}

// Based on https://github.com/iexbase/tron-api-python/blob/master/tronapi/tron.py

// Send query
function wallet_rpc_send_query($url,$query) {
        // Setup cURL
//echo "wallet_rpc_send_query($url,$query)\n";
        $ch=curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$query);

        // Send query
        $result=curl_exec($ch);

        curl_close($ch);

        return $result;
}

// Validate address
function wallet_rpc_validate_address($address) {
        global $url_validate_address;
        $address_hex=base_58_decode($address);
        $result=wallet_rpc_send_query($url_validate_address,'{"address":"'.$address_hex.'"}');

        $data=json_decode($result);

        if(property_exists($data,"result") && $data->result=="true") {
                return TRUE;
        } else {
                return FALSE;
        }
}

// Get Balance
function wallet_rpc_get_balance() {
        global $wallet_address;
        global $url_get_balance;
        $address_hex=base_58_decode($wallet_address);
        $result=wallet_rpc_send_query($url_get_balance,'{"address":"'.$address_hex.'"}');

        $data=json_decode($result);

        if(property_exists($data,"balance")) {
                return ($data->balance)/1000000;
        } else {
                return 0;
        }
}

// Send coins
function wallet_rpc_send($address,$amount) {
        global $url_create_transaction;
        global $url_sign_transaction;
        global $url_broadcast_transaction;
        global $wallet_address;
        global $wallet_private_key;

        $address_to_hex=base_58_decode($address);
        $address_from_hex=base_58_decode($wallet_address);
        $amount=ceil($amount*1000000);

        $result=wallet_rpc_send_query($url_create_transaction,'{"to_address":"'.$address_to_hex.'","owner_address":"'.$address_from_hex.'","amount":'.$amount.'}');

        $data=json_decode($result);
        if(!property_exists($data,"txID")) return FALSE;
        $tx_id=$data->txID;
        $tx_data=$result;

        $result=wallet_rpc_send_query($url_sign_transaction,'{"transaction":'.$tx_data.',"privateKey":"'.$wallet_private_key.'"}');

        $data=json_decode($result);
        if(!property_exists($data,"signature")) return FALSE;
        $transaction=$result;

        $result=wallet_rpc_send_query($url_broadcast_transaction,$transaction);
        if($result!='') return $tx_id;
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
