<?php
/**
 * Query class TODO: Complete migration
 * 
 * CoreProtect Lookup Web Interface
 * @author      Simon Chuu <simonorj@outlook.com>
 * @copyright   Simon Chuu, 2015-2017
 * @license     MIT
 */

class Query {
    private $a, $t, $tReverse, $b, $eb, $u, $eu;
    /**
     * HTML GET/POST Form parser
     * 
     * @param string[] $input   The input as fetched directly from $_REQUEST
     * @param mixed[]  $c       Configuration input for defaults
     * 
     * @return 
     */
    public function parseHTMLForm($input, $c) {
        $VARS = array(
            'string[]' => array("a","b","e","u"), // TODO: SQL escaping
            'int[]'    => array("xyz", "xyz2"),
            'string'   => array("t","wid"),
            'int'      => array("r","rollback","lim","offset"),
            'boolean'  => array("unixtime","asendt")
        );
        
        foreach ($input as $key => $val) {
            
            // Emptiness check
            if (is_string($val) && $val === "") continue; // String is empty.
            if (is_array($val)) {
                // empty() does not support functions for PHP under v5.5.0.
                $emptyValTest = array_filter($val,function($k){
                    return $k !== "";
                });
                if (empty($emptyValTest)) continue; // array only contains empty
                                                    // strings.
            }
            
            // Matching
            if (in_array($key, $VARS['string[]'], true)) {
                if (is_array($val))
                    // It's already written as an array (from the web form)
                    $q[$key] = $val;
                elseif (is_string($val) && $val !== "")
                    // Comma-separated values (from MC)
                    $q[$key] = explode(',',str_replace(' ', '', $val));
                    
            } elseif (in_array($key, $VARS['int[]'], true)) {
                if (is_array($val)) {
                    // coordinate 2: figure out if radius or second corner.
                    if ($key === "xyz2" && $val[0] !== ""
                            && $val[1] === ""
                            && $val[2] === "")
                        // xyz2: second corner or radius
                        $q['r'] = intval($val[0]);
                    else
                        $q[$key] = array_map('intval', $val);
                }
                elseif (is_string($val) && $val !== "")
                    $q[$key] = (is_array($val)) ? $val : explode(',', $val);
                
            } elseif (in_array($key, $VARS['string'], true)) {
                $q[$key] = $val;
                
            } elseif (in_array($key, $VARS['int'], true)) {
                if ($val !== "")
                    $q[$key] = intval($val);
                    
            } elseif (in_array($key, $VARS['boolean'], true)) {
                if ($val === "on") $q[$key] = true;
                
            } elseif ($key === "keyword") {
                // keyword search, contains commas as delimiter
                if ($val !== "") $q[$key] = str_getcsv($val); // TODO: SQL Escape
                
            }
        }
        
        // Defaults if the required parts of the query is empty:
        if (empty($q['a']))         $q['a'] = array("block");
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
    }
}