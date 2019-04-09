<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");
require_once("coinhive.php");
require_once("coinimp.php");
require_once("email.php");
require_once("html.php");
require_once("captcha.php");

// Only ASCII parameters allowed

db_connect();

$coinhive_xmr_per_Mhash=get_variable("payoutPer1MHashes");

// Miner link - show only miner for that user
if(isset($_GET['miner'])) {
        $user_uid=stripslashes($_GET['miner']);
        $coinhive_id=get_coinhive_id_by_user_uid($user_uid);
        $miner_form=html_coinhive_frame($coinhive_id);
        echo $miner_form;
        die();
} else if(isset($_GET['miner_coinimp_web'])) {
        $user_uid=stripslashes($_GET['miner_coinimp_web']);
        $coinhive_id=get_coinhive_id_by_user_uid($user_uid);
        $miner_form=html_coinimp_frame("web",$coinhive_id);
        echo "<link rel='stylesheet' type='text/css' href='style.css'>\n";
        echo $miner_form;
        if(isset($_GET['autostart'])) {
                $autostart='web_client.start();';
        } else {
                $autostart='';
        }
        echo <<<_END
<script>
update_stats_repeat();
function update_stats_repeat() {
        web_update_stats();
        setTimeout('update_stats_repeat()',1000);
}
$autostart
</script>
_END;
        die();

} else if(isset($_GET['miner_coinimp_xmr'])) {
        $user_uid=stripslashes($_GET['miner_coinimp_xmr']);
        $coinhive_id=get_coinhive_id_by_user_uid($user_uid);
        $miner_form=html_coinimp_frame("xmr",$coinhive_id);
        echo '<link rel="stylesheet" type="text/css" href="style.css">';
        echo $miner_form;
        echo <<<_END
<script>
update_stats_repeat();
function update_stats_repeat() {
        xmr_update_stats();
        setTimeout('update_stats_repeat()',1000);
}
</script>
_END;
        die();
}

$user_uid="";
$logged_in=FALSE;

if(isset($_GET['part'])) $part=stripslashes($_GET['part']);
else $part='';

$part_html=html_escape($part);

// Get session (create new if not exists), user_uid, token
$session=get_session();
$user_uid=get_user_uid_by_session($session);
$token=get_user_token_by_session($session);
//var_dump($session,$user_uid,$token);
if(isset($user_uid) && $user_uid!=0) $logged_in=TRUE;
else $logged_in=FALSE;

// Captcha
if(isset($_GET['captcha'])) {
        captcha_show($session);
        die();
}

// Actions handle
if(isset($_POST['action']) || isset($_GET['action'])) {
        $message='';

        if(isset($_GET['token'])) $received_token=$_GET['token'];
        else $received_token=$_POST['token'];

        if($token!=$received_token) {
                die("Invalid token");
        }

        if(isset($_GET['action'])) $action=$_GET['action'];
        else if(isset($_POST['action'])) $action=$_POST['action'];

        if($action=="register") {
                $captcha_code=$_POST['captcha_code'];
                if(captcha_check($session,$captcha_code)) {
                        captcha_regenerate($session);
                        $login=stripslashes($_POST['login']);
                        $password=stripslashes($_POST['password']);
                        $ref_id=stripslashes($_POST['ref_id']);
                        if($ref_id=='') $ref_id=0;

                        $message=user_register_or_login($session,$login,$password,$ref_id);
                } else {
                        captcha_regenerate($session);
                        $message="Incorrect captcha";
                }
        } else if($action=="logout") {
                user_logout($session);
        } else if($logged_in==TRUE && $action=="request_coin") {
                $coin=$_POST['request_coin'];
                $message=request_coin($user_uid,$coin);
        } else if($logged_in==TRUE && $action=="chat_message") {
                $user_message=stripslashes($_POST['message']);
                $message=chat_add_message($user_uid,$user_message);
                $return_to="?part=user_chat";
        } else if($logged_in==TRUE && $action=="cancel_payout") {
                $payout_uid=stripslashes($_POST['payout_uid']);
                payout_cancel($user_uid,$payout_uid);
                $message="";
        } else if($logged_in==TRUE && $action=="request_deposit_address") {
                $currency=stripslashes($_POST['currency']);
                request_deposit_address($user_uid,$currency);
                $message="Deposit address for $currency requested, it takes some time";
        } else if($logged_in==TRUE && $action=="freebitcoin_address") {
                $user_id=stripslashes($_POST['address']);
                update_user_result_id($user_uid,"Freebitcoin",$user_id);
                $message="Address for freebitco.in ref reward is set";
        } else if($logged_in==TRUE && $action=="freedogecoin_address") {
                $user_id=stripslashes($_POST['address']);
                update_user_result_id($user_uid,"Freedogecoin",$user_id);
                $message="Address for freedoge.co.in ref reward is set";
        } else if($logged_in==TRUE && $action=="withdraw") {
                $currency_code=stripslashes($_POST['currency_code']);
                $payout_address=stripslashes($_POST['payout_address']);

                if(isset($_POST['payment_id'])) $payment_id=$_POST['payment_id'];
                else $payment_id='';

                $message=user_withdraw($session,$user_uid,$currency_code,$payout_address,$payment_id);
                $return_to="?part=deposit";
        } else if(is_admin($user_uid) && $action=='set_tx_id') {
                $tx_id=stripslashes($_POST['tx_id']);
                $payout_uid=stripslashes($_POST['payout_uid']);
                $status=stripslashes($_POST['status']);
                $message=admin_set_tx_id($payout_uid,$tx_id,$status);
                $return_to="?part=admin_payouts";
        }
        setcookie("message",$message);
        if(isset($return_to)) {
                header("Location: ./$return_to");
        } else {
                header("Location: ./");
        }
        die();
}

// If message exists, show it to user once
if(isset($_COOKIE['message'])) {
        $message=html_message($_COOKIE['message']);
        setcookie("message","");
} else {
        $message='';
}

if(isset($_GET['json'])) {
        if($logged_in==FALSE) {
                echo html_register_login_info();
        } else {
                //echo html_message("Updating in progress, balances will updated after. It is safe to continue mining.");
                // Balance information
                $coinhive_id=get_coinhive_id_by_user_uid($user_uid);
                $old_hashes=get_user_hashes($user_uid);

                //coinimp_get_reward_info();
                //$coinhive_balance=coinhive_get_user_balance($coinhive_id);
                //$coinimp_xmr_balance=coinimp_get_user_balance("xmr",$coinhive_id);
                $coinimp_web_balance=coinimp_get_user_balance("web",$coinhive_id);

                //update_user_mined_balance($user_uid,$coinhive_balance);

                //update_user_results($user_uid,"Coinhive",$coinhive_balance);
                //update_user_results($user_uid,"Coinimp-XMR",$coinimp_xmr_balance);
                update_user_results($user_uid,"Coinimp-WEB",$coinimp_web_balance);

                $new_hashes=get_user_hashes($user_uid);

                echo html_balance_detail($user_uid,$old_hashes,$new_hashes);

                //echo html_balance_detail_coinimp($user_uid,$coinimp_xmr_balance,$coinimp_web_balance);

                echo html_user_assets($user_uid);
                //echo html_results_and_assets($user_uid);

                // Common information
                if($part=='') {
                        echo html_select_your_coin($user_uid);
                } else if($part=="user_chat") {
                        echo html_chat();
                } else if($part=="user_stats") {
                        // User results
                        echo html_user_results($user_uid);

                        // Achievements
                        echo html_achievements_section($user_uid);

                        // Global stats
                        echo html_stats();
                } else if($part=="payouts") {
                        echo html_payouts_section($user_uid);
                } else if($part=="deposit") {
                        echo html_deposit($user_uid);
                } else if($part=="settings") {
                        // User links
                        echo html_settings($user_uid);
                        echo html_links_section($user_uid);
                } else if(is_admin($user_uid) && $part=="admin_users") {
                        echo html_registered_users_admin();
                } else if(is_admin($user_uid) && $part=="admin_payouts") {
                        echo html_payouts_section_admin();
                } else if(is_admin($user_uid) && $part=="admin_log") {
                        echo html_log_section_admin();
                } else {
                        echo html_results_in_coin($user_uid,$part);
                }
        }

        die();
}


echo html_page_begin($pool_name);

if(isset($message) && $message!='') echo $message;

// If logged in, show balance info
if($logged_in) {
        //echo html_message("Updating in progress, balances will updated after. It is safe to continue mining.");
        // Welcome message and logout link
        echo html_welcome_logout_form($user_uid);

        //if($user_uid==99) echo html_message("Hi, lglazanl, accidentally I sent you 22477 RDD, can you send 90 % of them back, please? My address is RcWWAoto8z1CWxpgp69t2xL3Cg9Bj2Lx4c");

        //echo html_select_miner_form($user_uid);

        // Coinhive miner
        $coinhive_id=get_coinhive_id_by_user_uid($user_uid);

        echo html_jsecoin_miner($coinhive_id);

        if(isset($_GET['coinhive'])) {
                echo html_coinhive_frame($coinhive_id);
        } else if(isset($_GET['coinimp_xmr'])) {
                echo html_coinimp_frame("xmr",$coinhive_id);
        } else {
                echo html_coinimp_frame("web",$coinhive_id);
        }

        // Balance information
        echo html_balance_big($user_uid);
        if(is_admin($user_uid)) {
                echo html_button_admin_block();
        } else {
                echo html_button_user_block();
        }
        echo html_mininig_info_block();

// If not logged in, show common information
} else {
        echo html_register_login_info();
}

// End of page, script and footer
echo html_page_end();

?>
