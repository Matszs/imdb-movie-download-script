<?php
/*
* A MySQLi class.
*/
class Database {
    private static $__DB;
    private static $_host;
    private static $_user;
    private static $_password;
    private static $_database;
    private static $_params = array();
    private static $_lastSql;

    public static function init($config = array()) {
        self::$_host		= (isset($config["host"]) ? $config["host"] : null);
        self::$_user		= (isset($config["user"]) ? $config["user"] : null);
        self::$_password	= (isset($config["password"]) ? $config["password"] : null);
        self::$_database	= (isset($config["database"]) ? $config["database"] : null);

        self::_connection();
    }

    private static function _connection() {
        self::$__DB	= mysqli_connect(self::$_host, self::$_user, self::$_password);

        if(!self::$__DB)
            trigger_error("ERROR: Couldn't connect to database: " . mysqli_error(self::$__DB));

        if(!mysqli_select_db(self::$__DB, self::$_database))
            trigger_error("ERROR: Couldn't connect to database: " . mysqli_error(self::$__DB));
    }

    /*
    * Give parameters using setParam function and use this params in your query like {param}, it will parse the value
    */
    public static function query($query = null) {
        $search		= array();
        $replace	= array();

        foreach(self::$_params as $param => $value) {
            $search[] = "{" . $param . "}";
            $replace[] = ($value === null ? 'null' : $value);
        }

        $query	= str_replace($search, $replace, $query);
        $sql	= mysqli_query(self::$__DB, $query);

        if(!$sql)
            trigger_error("ERROR: Couldn't execute query: " . mysqli_error(self::$__DB));

        self::$_lastSql = $sql;

        return $sql;
    }

    public static function getArray($sql = null) {
        $toReturn	= array();

        while($data = mysqli_fetch_assoc(($sql ? $sql : self::$_lastSql)))
            $toReturn[]	= $data;

        return $toReturn;
    }

    public static function lastInsertId() {
        return mysqli_insert_id(self::$__DB);
    }

    /*
    * param can be a string and value as a string or
    * param can be an array (index, value) and value is null.
    */
    public static function setParam($param, $value = null, $escape = true) {
        if(!$param)
            return false;

        if(is_array($param)) {
            foreach($param as $index => $value)
                self::$_params[$index] = ($escape && gettype($value) !== 'NULL' ? mysqli_real_escape_string(self::$__DB, $value) : $value);
        } else {
            self::$_params[$param] = ($escape && gettype($value) !== 'NULL' ? mysqli_real_escape_string(self::$__DB, $value) : $value);
        }
    }

    /*
    * Close the MySQL Connection, function will be called in the beforeViewing because at
    * that moment al the processing is done.
    */
    public static function dispatch() {
        @mysqli_close(self::$__DB);
    }
}
?>