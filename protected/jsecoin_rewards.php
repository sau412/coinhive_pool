<?php
require_once("settings_remote.php");
require_once("db.php");
require_once("core.php");

db_connect();

if(isset($_POST['csv'])) {
        echo "<pre>";
        $csv=stripslashes($_POST['csv']);
        $csv_strings=explode("\n",$csv);
        foreach($csv_strings as $str) {
                $data=explode("\t",$str);
                $id=$data[0];
                $jsecoin=str_replace(' JSE','',$data[1]);
                if($id=='') continue;
                echo "id $id jsecoin $jsecoin\n";
                $id_escaped=db_escape($id);
                $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `coinhive_id`='$id_escaped'");
                if(!$user_uid) {
                        echo "User not found\n";
                        continue;
                }
                $user_uid_escaped=db_escape($user_uid);
                $jsecoins_balance=db_query_to_variable("SELECT `value` FROM `results` WHERE `user_uid`='$user_uid_escaped' AND `platform`='JSECoin'");
                $balance_diff=$jsecoin-$jsecoins_balance;
                if($balance_diff>0) {
                        //$hashes_to_add=floor(1000000*$balance_diff/$rate);
                        //db_query("UPDATE `users` SET `dualmined`=`dualmined`+$hashes_to_add,`jsecoin`='$jsecoin' WHERE `coinhive_id`='$id_escaped'");
                        echo "New $balance_diff JSEcoin, add to $user_uid\n";
                        update_user_results($user_uid,"JSECoin",$jsecoin);
                } else {
                        echo "No balance change";
                }
        }
} else {
        $csv='';
}
?>
<form name=jsecoin_csv method=post>
<p><textarea name=csv>
<?php echo $csv; ?>
</textarea></p>
<p><input type=submit value='distribute'</p>
</form>
