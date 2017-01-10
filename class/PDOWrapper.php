<?php
/**
 * PDOWrapper class
 * 
 * CoreProtect Lookup Web Interface
 * @author      Simon Chuu <simonorj@outlook.com>
 * @copyright   Simon Chuu, 2015-2017
 * @license     MIT
 */

class PDOWrapper extends PDO {
    private $error = null;
    
    /**
     * Constructor.
     * 
     * @param mixed[] $d an array of strings that should contain keys "type"
     *                   along with either "host", "data", "user", and "pass"
     *                   fields or "path" field.
     */
    public function __construct($d) {
        try {
            if ($d["type"] === "mysql")
                parent::__construct("mysql:charset=utf8;host=".$d["host"]
                    .";dbname=".$d["data"],
                    $d["user"],$d["pass"]);
            else
                parent::__construct("sqlite:".$d["path"]);
        } catch(PDOException $e) {
            return $e;
        }
    }
    
    public function getInitError() {
        return $error;
    }
}