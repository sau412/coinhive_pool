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
                $id=trim($data[0]);
                $dogecoin=trim($data[1]);
                if($id=='') continue;
                echo "id $id dogecoin $dogecoin\n";
                $id_escaped=db_escape($id);
                $user_uid=db_query_to_variable("SELECT `user_uid` FROM `results` WHERE `id`='$id_escaped'");
                if(!$user_uid) {
                        echo "User not found\n";
                        continue;
                }
                $user_uid_escaped=db_escape($user_uid);
                $dogecoins_balance=db_query_to_variable("SELECT `value` FROM `results` WHERE `id`='$id_escaped'");
                $balance_diff=$dogecoin-$dogecoins_balance;
                if($balance_diff>0) {
                        echo "New $balance_diff DOGE, add to $user_uid\n";
                        update_user_results($user_uid,"Freedogecoin",$dogecoin);
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
