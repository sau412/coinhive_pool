<?php
// Coinhive API URLs
$coinhive_get_balance_url="https://api.coinhive.com/user/balance";
$coinhive_withdraw_balance_url="https://api.coinhive.com/user/withdraw";
$coinhive_reset_balance_url="https://api.coinhive.com/user/reset";
$coinhive_get_payout_info_url="https://api.coinhive.com/stats/payout";
$coinhive_get_stats_site_info_url="https://api.coinhive.com/stats/site";

// Get balance of specific user
// Returns class with total, withdrawn and balance
function coinhive_get_user_balance_detail($address) {
        global $coinhive_private_key;
        global $coinhive_get_balance_url;

        $address_html=html_escape($address);
        // Setup cURL
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($ch,CURLOPT_POST,FALSE);
        curl_setopt($ch,CURLOPT_URL,$coinhive_get_balance_url."?secret=$coinhive_private_key&name=$address_html");
        $result = curl_exec ($ch);
        if($result=="") return 0;
        $data=json_decode($result);
        return $data;
        //if(isset($data->balance) && $data->balance) return $data->balance;
        //else return 0;
}

// Returns only balance
function coinhive_get_user_balance($address) {
        $data=coinhive_get_user_balance_detail($address);
        if(property_exists($data,"total") && $data->total!=0) return $data->total;
        else return 0;
}

// Reset user balance
function coinhive_reset_user_balance($address) {
        global $coinhive_private_key;
        global $coinhive_reset_balance_url;

        $address_html=html_escape($address);
        // Setup cURL
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch,CURLOPT_POSTFIELDS,"secret=$coinhive_private_key&name=$address_html");
        curl_setopt($ch,CURLOPT_URL,$coinhive_reset_balance_url."?secret=$coinhive_private_key&name=$address_html");
        $result = curl_exec ($ch);

        if($result=="") return FALSE;
        $data=json_decode($result);

        if($data->success=="true") return TRUE;
        else return FALSE;
}


// Get payout info
// Main information here is rate per 1 Mhash
function coinhive_get_payout_info() {
        global $coinhive_private_key;
        global $coinhive_get_payout_info_url;

        // Setup cURL
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($ch,CURLOPT_POST,FALSE);
        curl_setopt($ch,CURLOPT_URL,$coinhive_get_payout_info_url."?secret=$coinhive_private_key");
        $result = curl_exec ($ch);
        if($result=="") return 0;
        $data=json_decode($result);
        return $data;
}

// Get site info
function coinhive_get_stats_site_info() {
        global $coinhive_private_key;
        global $coinhive_get_stats_site_info_url;

        // Setup cURL
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($ch,CURLOPT_POST,FALSE);
        curl_setopt($ch,CURLOPT_URL,$coinhive_get_stats_site_info_url."?secret=$coinhive_private_key");
        $result = curl_exec ($ch);
        if($result=="") return 0;
        $data=json_decode($result);
        return $data;
}

?>
