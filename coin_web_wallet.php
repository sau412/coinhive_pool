<?php
// Coin-client related functions
//require_once("settings.php");
//var_dump(coin_rpc_get_balance());

// Send query to gridcoin client
function coin_web_send_query($query) {
        global $coin_api_url;
        global $coin_api_key;

        $ch=curl_init($coin_api_url);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
//curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
//curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS,"api_key=$coin_api_key&".$query);
        $result=curl_exec($ch);

//var_dump("curl error",curl_error($ch));
        curl_close($ch);

        return $result;
}

// Get balance
function coin_web_get_balance() {
        $query="method=get_balance";
        $result=coin_web_send_query($query);
        $data=json_decode($result);
//var_dump($result,$data);
        if(property_exists($data,"error")) return $data->error;
        return $data->balance;
}

// Send coins
function coin_web_send($coin_address,$amount) {
        $query="method=send&address=$coin_address&amount=$amount";
//var_dump($query);
        $result=coin_web_send_query($query);
//var_dump($result);
        $data=json_decode($result);
//var_dump($data);
        if(property_exists($data,"error")) return $data->error;
        return $data->uid;
}

// Get sending status
function coin_web_get_tx_status($tx_uid) {
        $query="method=get_transaction_by_uid&transaction_uid=$tx_uid";
//var_dump($query);
        $result=coin_web_send_query($query);
//var_dump($result);
        $data=json_decode($result);
        if(property_exists($data,"error")) return $data->error;

        return $data;
}

// Get new address
function coin_web_get_new_receiving_address() {
        $query="method=new_receiving_address";
        $result=coin_web_send_query($query);
        $data=json_decode($result);
//var_dump($result,$data);
        if(property_exists($data,"error")) return $data->error;
        return $data;
}

// Get receiving address
function coin_web_get_receiving_address($address_uid) {
        $query="method=get_receiving_address_by_uid&address_uid=$address_uid";
        $result=coin_web_send_query($query);
        $data=json_decode($result);
//var_dump($result,$data);
        if(property_exists($data,"error")) return $data->error;
        return $data;
}

?>
