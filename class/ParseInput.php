<?php
/**
 * ParseInput class
 * State: Getter implementation needed
 * 
 * CoreProtect Lookup Web Interface
 * @author      Simon Chuu <simonorj@outlook.com>
 * @copyright   Simon Chuu, 2015-2017
 * @license     MIT
 */

include_once "DataCache.php";

class ParseInput {
    // string[]
    private $action, $block, $user, $keyword,
    // a, b, u, kw
    // int[]
        $cornerLo, $cornerHi,
    // int
        $date, $limit, $offset, $rollback,
    // boolean
        $exclusiveBlock, $exclusiveUser, $ascendingDate;

    // actions
    const A_BLOCK = "block",
        A_CHAT = "chat",
        A_CLICK = "click",
        A_CONTAINER = "container",
        A_COMMAND = "command",
        A_KILL = "kill",
        A_SESSION = "session",
        A_USERNAME = "username_log";
    
    /**
     * Input parser
     * 
     * @param string[] $input   The input as fetched directly from $_REQUEST
     * @param mixed[]  $c       Configuration input for grabbing defaults
     */
    public function __construct($input, $c, $dia) {
        // parse input
        if (isset($input['cmd'])) {
            $input['cmd'] = explode(" ", $input);
            foreach ($input as $v) {
                $tmp = explode(":", $v, 2);
                // Possible first indexes: u t r a b e
                // info passed down straight from $input: c1
                switch ($tmp[0]) {
                case 'a':
                    // TODO: Implement all these possible inputs
                    // block +block -block click container +container -container
                    // kill chat session +session -session username
                case 'b':
                case 'u':
                    $tmp[1] = explode(",", $tmp[1]);
                    foreach ($tmp[1] as $tmp1)
                        if ($tmp1 !== "")
                            $input[$tmp[0]][] = $tmp1;
                    break;
                case 't':
                    // split at the non-character between letter and digit
                    $tmp1 = preg_split("/(?<=[wdhms])(?=\d)/",
                            str_replace(",","",$tmp[1]));
                    if (empty($input['t'])) {
                        $input['t'] = time();
                    }
                    foreach($tmp1 as $v) {
                        $v = preg_split("/(?<=\d)(?=[wdhms])/",$v,2);
                        switch($v[1]) {
                            case "w": $input['t'] -= $v[0]*604800; break;
                            case "d": $input['t'] -= $v[0]*86400;  break;
                            case "h": $input['t'] -= $v[0]*3600;   break;
                            case "m": $input['t'] -= $v[0]*60;     break;
                            case "s": $input['t'] -= $v[1];        break;
                        }
                    }
                    break;
                case 'r':
                    $input['r'] = $tmp[1];
                    break;
                case 'e':
                    $tmp[1] = explode(",", $tmp[1]);
                    foreach ($tmp[1] as $tmp1) {
                        // differenciate between user and block.
                        if ($DIA->getId($tmp1, DataIDAccess::USER) !== NULL) {
                            // Specified excludes is a user.
                            if ($input['eu'] === "on") {
                                
                                // Exclusive is on. now check for duplication.
                                if (!array_search($tmp1, $input['u']))
                                    $input['u'][] = $tmp1;
                                
                            } elseif (empty($input['u'])) {
                                
                                // Empty. Initialize it.
                                $input['eu'] = "on";
                                $input['u'][] = $tmp1;
                                
                            } elseif (($k = array_search($tmp1, $input['u'])
                                    !== false)) {
                                
                                // User is in the array, and 'eu' DNE.
                                unset($input['u'][$k]);
                                
                            }
                            continue;
                        } elseif ($DIA->getId($tmp1, DataIDAccess::BLOCK) !== NULL) {
                            
                            // Specified excludes is a block.
                            if ($input['eb'] === "on") {
                                
                                // Exclusive is on. now check for duplication.
                                if (!array_search($tmp1, $input['b']))
                                    $input['b'][] = $tmp1;
                                
                            } elseif (empty($input['b'])) {
                                
                                // Empty. Initialize it.
                                $input['eb'] = "on";
                                $input['b'][] = $tmp1;
                                
                            } elseif (($k = array_search($tmp1, $input['u'])
                                    !== false)) {
                                
                                // User is in the array, and 'eu' DNE.
                                unset($input['b'][$k]);
                                
                            }
                            continue;
                        }
                    }
                }
            }
        }
        
        function checkArray($var, $ifNone = null, $trimEmpty = false) {
            if (!is_array($var))
                return $ifNone;
            
            // empty() does not support functions for PHP under v5.5.0.
            $trimTest = array_filter($var,function($ke){
                return $ke !== "";
            });
            
            return empty($trimTest) ? $ifNone : $trimEmpty ? $trimTest : $var;
        }
        
        function checkString($var, $ifNone = null) {
            return $var === "" ? $ifNone : $var;
        }
        
        function checkBoolean($var) {
            return $var === "on";
        }
        
        $this->action = checkArray($input['a'], array('block'));
        $this->block  = checkArray($input['b']);
        $this->user   = checkArray($input['u']);
        
        // code for $cornerLo and $cornerHi
        $l1 = checkArray($input['c1']);
        if ($l1 === null) {
            $this->cornerLo = $this->cornerHi = null;
        } else {
            $l1 = array_map('intval', $l1);

            $l2 = checkArray($input['c2']);
            if ($l2 === null) {
                // one-block search
                $this->cornerLo = $this->cornerHi = $l1;
            } else {
                // coordinate 2: figure out if radius or second corner.
                if ($l2[0] !== ""
                        && $l2[1] === ""
                        && $l2[2] === "") {
                    // radius
                    $l2 = intval($l2[0]);
                    $this->cornerLo = array(
                        $l1[0] - $l2,
                        $l1[1] - $l2,
                        $l1[2] - $l2
                    );
                    $this->cornerHi = array(
                        $l1[0] + $l2,
                        $l1[1] + $l2,
                        $l1[2] + $l2,
                    );
                }
                else {
                    $l2 = array_map('intval', $l2);
                    // Second corner
                    $this->cornerLo = array(
                        min($l1[0], $l2[0]),
                        min($l1[1], $l2[0]),
                        min($l1[2], $l2[0])
                    );
                    $this->cornerHi = array(
                        max($l1[0], $l2[0]),
                        max($l1[1], $l2[1]),
                        max($l1[2], $l2[2])
                    );
                }
            }
        }
        
        // Convert to int time from possible 0000-00-00T00:00
        $this->date = is_numeric(checkString($input['t'], time()))
                ? intval($t) : strtotime($t);
        
        $this->offset = intval(checkString($input['os'], 0));
        $this->limit  = intval(checkString($input['lm'],
                $offset === 0 ? 50 : 20));// TODO: Use config val
        $this->rollback = intval(checkString($input['rb']));
        $this->exclusiveBlock = checkBoolean($input['eb']);
        $this->exclusiveUser  = checkBoolean($input['eu']);
        $this->ascendingDate  = checkBoolean($input['rt']);
        $this->keyword = $input !== "" ? null : str_getcsv($val); // TODO: SQL Escape
    }
}