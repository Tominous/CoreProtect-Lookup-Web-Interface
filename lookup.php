<?php
/**
 * lookup script
 * 
 * CoreProtect Lookup Web Interface
 * @author      Simon Chuu <simonorj@outlook.com>
 * @copyright   Simon Chuu, 2015-2017
 * @license     MIT
 */

/** Timing the execution time */
$timer = microtime(true);

register_shutdown_function(function() {
    global $timer, $out;
    header('Content-type:application/json;charset=utf-8');
    $out['meta']['duration'] = microtime(true) - $timer;
    echo json_encode($out);
});

require_once "class/PDOWrapper.php";
require_once "class/CacheCtrl.php";
require_once "class/BukkitToMinecraft.php";



