<?php
// Get other cryptos rate from poloniex
// https://poloniex.com/public?command=returnTicker

if(!isset($argc)) die();

require_once("settings.php");
require_once("db.php");
require_once("core.php");

$poloniex_url="https://poloniex.com/public?command=returnTicker";

db_connect();

$coinhive_xmr_per_Mhash=get_variable("payoutPer1MHashes");

// Setup cURL
$ch=curl_init();
curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
curl_setopt($ch,CURLOPT_POST,FALSE);
curl_setopt($ch,CURLOPT_URL,$poloniex_url);
$result = curl_exec ($ch);

if($result=="") die("No data");

$data=json_decode($result);

// Store data
$btc_xmr_rate=$data->BTC_XMR->highestBid;
$rate=1;
$code="BTC";
$mhash_rate=($btc_xmr_rate)*$coinhive_xmr_per_Mhash;
db_query("UPDATE `currency` SET `rate_per_mhash`='$mhash_rate' WHERE `currency_code`='$code'");
echo "$code $rate $mhash_rate\n";

foreach($data as $pair => $pair_data) {
        $rate=$pair_data->highestBid;
        switch($pair) {
                case "BTC_ARDR": $code="ARDR"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_BCH": $code="BCH"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_BCN": $code="BCN"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_BCY": $code="BCY"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_BLK": $code="BLK"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_BTS": $code="BTS"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_BURST": $code="BURST"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_CLAM": $code="CLAM"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_CVC": $code="CVC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_DASH": $code="DASH"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_DCR": $code="DCR"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_DGB": $code="DGB"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_DOGE": $code="DOGE"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_EMC2": $code="EMC2"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_ETC": $code="ETC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_ETH": $code="ETH"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_EXP": $code="EXP"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_FLDC": $code="FLDC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_FLO": $code="FLO"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_GAME": $code="GAME"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_GAS": $code="GAS"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_GNO": $code="GNO"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_GNT": $code="GNT"; $rate=$btc_xmr_rate/$rate; break;
                //case "BTC_GRC": $code="GRC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_HUC": $code="HUC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_LBC": $code="LBC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_LSK": $code="LSK"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_LTC": $code="LTC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_MAID": $code="MAID"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_NMC": $code="NMC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_NXC": $code="NXC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_NXT": $code="NXT"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_OMG": $code="OMG"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_OMNI": $code="OMNI"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_PASC": $code="PASC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_PINK": $code="PINK"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_POT": $code="POT"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_PPC": $code="PPC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_RADS": $code="RADS"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_REP": $code="REP"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_SC": $code="SC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_STORJ": $code="STORJ"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_STR": $code="STR"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_STRAT": $code="STRAT"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_SYS": $code="SYS"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_VTC": $code="VTC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_VIA": $code="VIA"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_VRC": $code="VRC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_XBC": $code="XBC"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_XCP": $code="XCP"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_XEM": $code="XEM"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_XMR": $code="XMR"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_XRP": $code="XRP"; $rate=$btc_xmr_rate/$rate; break;
                case "BTC_ZRX": $code="ZRX"; $rate=$btc_xmr_rate/$rate; break;
                case "USDT_BTC": $code="USDT"; $rate=$btc_xmr_rate*$rate; break;
                default: unset($code); $rate=0; break;
        }

        if(!isset($code)) continue;

        $mhash_rate=$rate*$coinhive_xmr_per_Mhash;
        echo "$code $rate $mhash_rate\n";
        db_query("UPDATE `currency` SET `rate_per_mhash`='$mhash_rate' WHERE `currency_code`='$code'");
}

$bittrex_urls=array(
        "BTC_GRC"=>"https://bittrex.com/api/v1.1/public/getticker?market=BTC-GRC",
        "BTC_TRX"=>"https://bittrex.com/api/v1.1/public/getticker?market=BTC-TRX",
        "BTC_GBYTE"=>"https://bittrex.com/api/v1.1/public/getticker?market=BTC-GBYTE",
        "BTC_XLM"=>"https://bittrex.com/api/v1.1/public/getticker?market=BTC-XLM",
);

foreach($bittrex_urls as $pair => $url) {
        curl_setopt($ch,CURLOPT_URL,$url);
        $result = curl_exec ($ch);

        if($result=="") continue;

        $data=json_decode($result);
//var_dump($data);
        $rate=$data->result->Bid;

        switch($pair) {
                case 'BTC_GBYTE': $code="GBYTE"; $rate=$btc_xmr_rate/$rate; break;
                case 'BTC_GRC': $code="GRC"; $rate=$btc_xmr_rate/$rate; break;
                case 'BTC_TRX': $code="TRX"; $rate=$btc_xmr_rate/$rate; break;
                case 'BTC_XLM': $code="XLM"; $rate=$btc_xmr_rate/$rate; break;
                default: unset($code); $rate=0; break;
        }

        if(!isset($code)) continue;
        $mhash_rate=$rate*$coinhive_xmr_per_Mhash;
        echo "$code $rate $mhash_rate\n";
        db_query("UPDATE `currency` SET `rate_per_mhash`='$mhash_rate' WHERE `currency_code`='$code'");
}
?>
