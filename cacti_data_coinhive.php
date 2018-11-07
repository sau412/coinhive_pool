<?php
require_once("settings_remote.php");
require_once("coinhive.php");

// Get stats from coinhive
$coinhive_data=coinhive_get_stats_site_info();

// On success show data in cacti-readable format
if(property_exists($coinhive_data,"success")) {
        $hashesPerSecond=$coinhive_data->hashesPerSecond;
        $hashesTotal=$coinhive_data->hashesTotal;
        $xmrPending=$coinhive_data->xmrPending;
        $xmrPaid=$coinhive_data->xmrPaid;
        $xmrPending=sprintf("%0.8f",$xmrPending);
        echo "hashesPerSecond:$hashesPerSecond hashesTotal:$hashesTotal xmrPending:$xmrPending xmrPaid:$xmrPaid\n";
}
?>
