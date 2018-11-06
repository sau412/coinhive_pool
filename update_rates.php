<?php
// Get other cryptos rate from coingecko

if(!isset($argc)) die();

require_once("settings.php");
require_once("db.php");
require_once("core.php");

db_connect();

$coinhive_xmr_per_Mhash=get_variable("payoutPer1MHashes");

// Setup cURL
$ch=curl_init();
curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
curl_setopt($ch,CURLOPT_POST,FALSE);

// Get XMR price
curl_setopt($ch,CURLOPT_URL,"https://api.coingecko.com/api/v3/coins/monero");
$result=curl_exec($ch);
if($result=="") {
        echo "No XMR price data\n";
        log_write("No XMR price data");
        die();
}
$parsed_data=json_decode($result);
$btc_per_xmr_price=(string)$parsed_data->market_data->current_price->btc;

// Query and calculate data for every other coin
$currency_data_array=db_query_to_array("SELECT `uid`,`currency_code`,`currency_name`,`api_url` FROM `currency`");

foreach($currency_data_array as $currency_data) {
        $uid=$currency_data['uid'];
        $currency_code=$currency_data['currency_code'];
        $currency_name=$currency_data['currency_name'];
        $api_url=$currency_data['api_url'];
        curl_setopt($ch,CURLOPT_URL,$api_url);
        $result=curl_exec($ch);
        if($result=="") {
                echo "No data for $currency_name ($currency_code)\n";
                log_write("No data for $currency_name ($currency_code)");
                continue;
        }
        $parsed_data=json_decode($result);
        if(property_exists($result,'error')) {
                echo "Error for $currency_name ($currency_code)\n";
                log_write("Error data for $currency_name ($currency_code)");
                continue;
        }

        // Getting data from coingecko
        $image_url=(string)$parsed_data->image->small;
        $btc_per_coin=(float)$parsed_data->market_data->current_price->btc;
        $price=$coinhive_xmr_per_Mhash*$btc_per_xmr_price/$btc_per_coin;

        // Escaping and updating
        $uid_escaped=db_escape($uid);
        $image_url_escaped=db_escape($image_url);
        $price_escaped=db_escape($price);

        db_query("UPDATE `currency` SET `rate_per_mhash`='$price_escaped',`img_url`='$image_url_escaped' WHERE `uid`='$uid_escaped'");
        echo "$currency_name ($currency_code) updated, price: $price\n";
}

?>
