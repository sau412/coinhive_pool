<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");
require_once("coinhive.php");

db_connect();

$payout_info=coinhive_get_payout_info();

$payoutPer1MHashes=$payout_info->payoutPer1MHashes;
if($payoutPer1MHashes!=0) {
        set_variable('payoutPer1MHashes',$payoutPer1MHashes);
        echo "Payout per 1 Mhash is $payoutPer1MHashes\n";
}
$user_data_array=db_query_to_array("SELECT `uid`,`coinhive_id` FROM `users`");

foreach($user_data_array as $user_data) {
        $user_uid=$user_data['uid'];
        $coinhive_id=$user_data['coinhive_id'];

        //$bonus=db_query_to_variable("SELECT hashes FROM sessions WHERE user_uid='$user_uid'");
        //if($bonus!=0) db_query("UPDATE `users` SET bonus='$bonus' WHERE uid='$user_uid'");
        if($coinhive_id=='') continue;

        $balance_info=coinhive_get_user_balance($coinhive_id);
        if(property_exists($balance_info,'total')) {
                $hashes_total=$balance_info->total;
                $user_uid_escaped=db_escape($user_uid);
                $hashes_total_escaped=db_escape($hashes_total);
                db_query("UPDATE `users` SET `mined`='$hashes_total_escaped' WHERE uid='$user_uid_escaped' AND `mined`<='$hashes_total_escaped'");
                echo "Updated data for coinhive user $coinhive_id\n";
        } else {
                echo "No data for coinhive user $coinhive_id\n";
        }
}
?>
