<?php
/**
 * ParseInput class TODO: Complete migration
 * 
 * CoreProtect Lookup Web Interface
 * @author      Simon Chuu <simonorj@outlook.com>
 * @copyright   Simon Chuu, 2015-2017
 * @license     MIT
 */

class ParseInput {
    // string[]
    private $action, $block, $user, $keyword;
    // int[]
    private $cornerLo, $cornerHi;
    // int
    private $date, $limit, $offset;
    // boolean
    private $exclusiveBlock, $exclusiveUser, $ascendingDate;
    // other
    private $rollback;
    
    // actions
    const A_BLOCK = "block";
    const A_CHAT = "chat";
    const A_CLICK = "click";
    const A_CONTAINER = "container";
    const A_COMMAND = "command";
    const A_KILL = "kill";
    const A_SESSION = "session";
    const A_USERNAME = "username_log";
    
    // form origin
    const ORIGIN_HTML_FORM = 0;
    const ORIGIN_COREPROTECT_LOOKUP_COMMAND = 1;
    
    public function __construct($request, $origin, $c) {
        if ($origin === ORIGIN_HTML_FORM)
            parseHTMLForm($request, $c);
        elseif ($origin === ORIGIN_COREPROTECT_LOOKUP_COMMAND)
            // TODO: this stuff
            ;
    }
    
    /**
     * HTML GET/POST Form parser
     * 
     * @param string[] $input   The input as fetched directly from $_REQUEST
     * @param mixed[]  $c       Configuration input for defaults
     * 
     * @return boolean true on success, false on failure.  Warnings will be
     *         considered as a pass (success).
     */
    public function parseHTMLForm($input, $c) {
        function checkArray($var, $input, $ifNone = null) {
            // empty() does not support functions for PHP under v5.5.0.
            $emptyValTest = array_filter($val,function($ke){
                return $ke !== "";
            });
            
            if (!empty($emptyValTest) && array_key_exists($var,$in)
                    && is_array($in[$var]))
                return $in[$var];

            return $ifNone;
        }
        $action = checkArray('a',$input,array('block'));
        $block  = checkArray('b',$input);
        $user   = checkArray('u',$input);
        
        // code for $corner1 and $corner2
        $corner1 = array();
        $corner2 = array();
        $l1 = checkArray('xyz',$input);
        if ($l1 === null) {
            $cornerLo = $cornerHi = null;
        } else {
            $l1 = array_map('intval', $l1);

            $l2 = checkArray('xyz2',$input);
            if ($l2 === null) {
                // one-block search
                $cornerLo = $cornerHi = $l1;
            } else {
                // coordinate 2: figure out if radius or second corner.
                if ($l2[0] !== ""
                        && $l2[1] === ""
                        && $l2[2] === "") {
                    // radius
                    $l2 = intval($l2[0]);
                    $cornerLo = array(
                        $l1[0] - $l2,
                        $l1[1] - $l2,
                        $l1[2] - $l2
                    );
                    $cornerHi = array(
                        $l1[0] + $l2,
                        $l1[1] + $l2,
                        $l1[2] + $l2,
                    );
                }
                else {
                    $l2 = array_map('intval', $l2);
                    // Second corner
                    $cornerLo = array(
                        min($l1[0], $l2[0]),
                        min($l1[1], $l2[0]),
                        min($l1[2], $l2[0])
                    );
                    $cornerHi = array(
                        max($l1[0], $l2[0]),
                        max($l1[1], $l2[1]),
                        max($l1[2], $l2[2])
                    );
                }
            }
        }
        
        //$date
        //$limit
        //$offset
        //$exclusiveBlock
        //$exclusiveUser
        //$ascendingDate
        //$rollback
        //$keyword
        
        /* Old way, defunct.
        $VARS = array(
            'int[]'    => array("xyz", "xyz2"),
            'string'   => array("t","wid"),
            'int'      => array("r","rollback","lim","offset"),
            'boolean'  => array("unixtime","asendt")
        );
        
        foreach ($input as $k => $v) {
            
            // Emptiness check
            if (is_string($v) && $v === "") continue; // String is empty.
            if (is_array($v)) {
            }
            

            // Matching
            switch ($key) {
            case "a":
            case "b":
            case "e":
            case "u":
                if (is_array($v)) $ret[$k] = $val;
                break;
            case "xyz":
            case "xyz2":
                if (is_array($v)) {
                    // coordinate 2: figure out if radius or second corner.
                    if ($k === "xyz2" && $v[0] !== ""
                            && $v[1] === ""
                            && $v[2] === "")
                        // xyz2: second corner or radius
                        $r = intval($val[0]);
                    else
                        $$k = array_map('intval', $val);
                }
                break;
            case "wid";
                $$k = intval($val);
                break;
            case "t":
                // Convert to int time from possible 0000-00-00T00:00
                $$k = is_numeric($v) ? intval($v) : strtotime($v);
            case "r":
            case "rollback":
            case "lim":
            case "offset":
                if ($val !== "")
                    $$k = intval($val);
            case "unixtime":
            case "asendt":
                if ($val === "on") $$k = true;
            case "keyword":
                // keyword search, contains commas as delimiter
                if ($val !== "") $q[$key] = str_getcsv($val); // TODO: SQL Escape
            }
        }
        
        // Defaults if the required parts of the query is empty:
        if (empty($a))         $q['a'] = array("block");
        if (!isset($q['asendt']))   $q['asendt'] = false;
        if (!isset($q['unixtime'])) $q['unixtime'] = false;
        if (!isset($q['offset']))   $q['offset'] = 0;
        if (!isset($q['lim'])) {
            if (isset($q['offset']) && $q['offset'] !== 0)
                $q['lim'] = $c['form']['loadMoreLimit'];
            else
                $q['lim'] = $c['form']['limit'];
        }

        return $q;
        */
    }
    
    
        //$action
        //$block
        //$user
        //$block
        //$location
        //$keyword
        //$date
        //$limit
        //$offset
        //$exclusiveBlock
        //$exclusiveUser
        //$ascendingDate
        //$rollback
        

}