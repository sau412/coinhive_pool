<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");
require_once("coinhive.php");
require_once("coinimp.php");
require_once("email.php");
require_once("html.php");
require_once("captcha.php");

db_connect();

$users_array=db_query_to_array("SELECT * FROM `users` WHERE `mined`>0");

foreach($users_array as $user) {
        $user_uid=$user['uid'];
        $coinhive_id=get_coinhive_id_by_user_uid($user_uid);
        $old_hashes=get_user_hashes($user_uid);

        $coinhive_balance=coinhive_get_user_balance($coinhive_id);
        $coinimp_xmr_balance=coinimp_get_user_balance("xmr",$coinhive_id);
        $coinimp_web_balance=coinimp_get_user_balance("web",$coinhive_id);

        update_user_results($user_uid,"Coinhive",$coinhive_balance);
        update_user_results($user_uid,"Coinimp-XMR",$coinimp_xmr_balance);
        update_user_results($user_uid,"Coinimp-WEB",$coinimp_web_balance);

        $jse=$user['jsecoin'];
        update_user_results($user_uid,"JSECoin",$jse);

        $balance=get_user_balance($user_uid);
        $balance+=$coinimp_xmr_balance;
        $xmr_balance=$balance*0.00005/1000000;
        echo "<p>$user_uid balance $balance XMR $xmr_balance</p>\n";
        set_user_asset($user_uid,"XMR",$xmr_balance);
}
