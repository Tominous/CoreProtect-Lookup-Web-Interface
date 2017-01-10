<?php
/**
 * FetchLookup class
 * 
 * CoreProtect Lookup Web Interface
 * @author      Simon Chuu <simonorj@outlook.com>
 * @copyright   Simon Chuu, 2015-2017
 * @license     MIT
 */

class FetchLookup {
    private $query, $statements, $error;

    /**
     * Constructor.
     * 
     * @param mixed[]  $c       Configuration
     * @param string[] $input   An array input. It can be passed down from a
     *                          HTML form, or directly inputted.
     * @param PDO      $pdo     A PDO object used to make database connections.
     * @param boolean $isFromHTML Denotes if the $input is directly from an HTML
     *                          form.
     */
    public function __construct($c, $input, $pdo, $isFromHTML = false) {
        $query = $isFromHTML ? parseHTMLForm($input, $c) : $input;
        $filter = prepareFilterSQL($query, $pdo);
        
    }
    
    private function fetch() {
        // something using $pdo
    }
    
    private function hasError() {
        return $error;
    }
    
    
    
    /**
     * Prepares "where" statements for SQL.
     * 
     * @param string[] $q   Queries
     * @param PDO      $pdo PDO connection class to CoreProtect
     * 
     * @return string[] where indexes include: coord, time, userid,
     *                  meta=>rbflag, meta=>a, and keyword.
     */
    private function prepareFilterSQL($q, $pdo) {
        $ret = array();
        
        // coord xyz, xyz2, r, wid
        if ((isset($q['xyz']) && (isset($q['r']) || isset($q['xyz2']))) || isset($q['wid'])) {
            if (isset($q['xyz'])) {
                if (isset($q['r']))
                    $ret['coord'] = "(x BETWEEN "
                            .($q['xyz'][0] - $q['r']) . " AND "
                            .($q['xyz'][0] + $q['r']) . ") AND (y BETWEEN "
                            .($q['xyz'][1] - $q['r']) . " AND "
                            .($q['xyz'][1] + $q['r']) . ") AND (z BETWEEN "
                            .($q['xyz'][2] - $q['r']) . " AND "
                            .($q['xyz'][2] + $q['r']) . ")";
                elseif (isset($q['xyz2']))
                    $ret['coord'] = "(x BETWEEN "
                            .min($q['xyz'][0], $q['xyz2'][0]) . " AND "
                            .max($q['xyz'][0], $q['xyz2'][0]) . ") AND (y BETWEEN "
                            .min($q['xyz'][1], $q['xyz2'][1]) . " AND "
                            .max($q['xyz'][1], $q['xyz2'][1]) . ") AND (z BETWEEN "
                            .min($q['xyz'][2], $q['xyz2'][2]) . " AND "
                            .max($q['xyz'][2], $q['xyz2'][2]) . ")";
            }
            
            if (isset($q['wid'])) {
                if (isset($ret['coord'])) $ret['coord'] .= " AND ";
                else               $ret['coord'] = "";
                
                $ret['coord'] .= "wid=".$Cc->getId($q['wid'],"world");
            }
        }

        // Time t, unixtime, asendt
        if (isset($q['t'])) {
            if ($q['unixtime'])
                // From web form
                $ret['meta']['t'] = $q['t'];
            else {
                // from MC
                $q['t'] = str_replace(",","",$q['t']);
                $q['t'] = preg_split("/(?<=[wdhms])(?=\d)/",$q['t']);
                $ret['time'] = time();
                foreach($q['t'] as $val) {
                    $val = preg_split("/(?<=\d)(?=[wdhms])/",$val,2);
                    switch($val[1]) {
                        case "w": $ret['meta']['t'] -= $val[0]*604800; break;
                        case "d": $ret['meta']['t'] -= $val[0]*86400;  break;
                        case "h": $ret['meta']['t'] -= $val[0]*3600;   break;
                        case "m": $ret['meta']['t'] -= $val[0]*60;     break;
                        case "s": $ret['meta']['t'] -= $val[0];        break;
                    }
                }
            }
            
            $ret['time'] = "time"
                    .(($q['asendt']) ? ">=" : "<=")
                    .$ret['meta']['t'];
            unset($ret['meta']['t']);
        }
        else $ret['time'] = "time<=".time();
        
        // User u, e
        if (isset($q['u'])) {
            // TODO: Make "e" option more dynamic...
            foreach($q['u'] as $key => $us)
                $q['u'][$key] = $Cc->getId($us,"user");
            
            $ret['userid'] = "user"
                    .($not = isset($q['e']) && in_array("u",$q['e'],true) ? " NOT " : " ")
                    ."IN ('"
                    .implode("','",$q['u'])
                    ."')";
            
            if (in_array("username",$q['a'],true)) {
                foreach($q['u'] as $us)
                    $us = $Cc->getValue($us,"user"); // for capitalization purposes
                $ret['username'] = "user".$not."IN ('".implode("','",$q['u'])."')";
            }
            unset($ret['meta']['uNot']);
        }
        else $ret['userid'] = false;
        
        // Rollback flag block, kill, container
        if(isset($q['rollback'])) {
            $ret['meta']['rbflag'] = "rolled_back=".(($q['rollback']) ? "1" : "0");
        }
        else $ret['meta']['rbflag'] = false;
        
        // actions, separate click, separate kill
        $ret['meta']['a'] = array(array(),false,false);
        
        // Block b, e; container action for block translation
        if(in_array("block",$q['a'],true) || in_array("click",$q['a'],true) || in_array("container",$q['a'],true)) {
            // TODO: Make "e" option more dynamic...
            // Blocks
            if(in_array("block",$q['a'],true)) {
                $ret['meta']['a'][0][] = 0; // destroy
                $ret['meta']['a'][0][] = 1; // place
            }
            // clicks
            if(in_array("click",$q['a'],true)) {
                if ($ret['meta']['rbflag'])
                    $ret['meta']['a'][1] = true;
                else
                    $ret['meta']['a'][0][] = 2;
            }
            
            // block search translation
            if(isset($q['b'])) {
                foreach($q['b'] as $key => $bk) {
                    $bk = $Cm->getBk($bk);
                    $q['b'][$key] = $bk;
                    // TODO: Get a better solution.
                    // TODO: Fix non-existant block lookup.
                    if($server['legacy']) if($bk !== ($bk2=preg_replace("/^minecraft:/","",$bk))) $q['b'][] = $bk2;
                }
                foreach($q['b'] as $key => $bk) $q['b'][$key] = $Cc->getId($bk,"material");
                $ret['block'] = "type"
                        .(isset($q['e']) && in_array("b",$q['e'],true) ? " NOT " : " ")
                        ."IN ('"
                        .implode("','",$q['b'])
                        ."')";
            } else $ret['block'] = false;
        } else $ret['block'] = false;
        
        // Error checking
        if(!empty($cc->error)) {
            $out[0]["status"] = 4;
            $out[0]["reason"] = "Username/Block Input not found";
            $out[1] = $Cc->error;
            exit();
        }
        
        // kill
        if(in_array("kill",$q['a'],true)) {
            if(isset($q['b'])) $ret['meta']['a'][2] = true;
            else $ret['meta']['a'][0][] = 3;
        }
        
        // keyword
        if(isset($q['keyword'])) {
            foreach($q['keyword'] as $word) {
                $terms = [];
                $words = str_getcsv($word,' ');
                foreach($words as $val) $terms[] = "message LIKE '%".$val."%'";
                $ret['keyword'][] = "(".implode(" AND ",$terms).")";
            }
            $ret['keyword'] = "(".implode(" OR ",$ret['keyword']).")";
            /*if(in_array("block",$a,true)) {
                foreach($keywords as $val) $serachSign[] = "(line_1 LIKE '%".$val."%' OR line_2 LIKE '%".$val."%' OR line_3 LIKE '%".$val."%' OR line_4 LIKE '%".$val."%')";
                $searchSign = "(".implode(" AND ",$searchSign).")";
            }*/
        }
        else $ret['keyword'] = false;
        
        return $ret;
    }
}