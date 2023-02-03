<?php
/**
 * DB - Mr.Hubič's class for database, specially for output processing
 * Created only for MySQL
 *
 * Very quick data manipulation on output
 * You can’t fix stupid, always be sure to fix your data at input on your own, this library will help you, but it is not automatic!
 *
 * @package Model
 * @version v0.0.1 (2023-02-03)
 * @copyright 2008 - NOW() Tomáš Hubálek
 * @author Tomáš Hubálek
 * @license BSD 3-Clause License
 */

namespace hubalekt\DB;

class DB {
  /**
   * Internal variables
   */
  private $sqllog = array(); // for SQL log data
  private $link = false; // for mysqli connection
  private $config = array(
      "db_host" => "localhost",
      "db_user" => "root",
      "db_pass" => "root",
      "db_name" => "test",
      "db_port" => "3306",
      "db_charset" => "UTF8",
      "log" => false,
    ); // default connection variables

  public function __construct($db_host = NULL, $db_user = NULL, $db_password = NULL, $db_name = NULL, $db_port = NULL, $db_charset = NULL) {
    if ($db_host !== NULL AND $db_user !== NULL AND $db_password !== NULL) {
      // credentials are passed, try to connect
      if ($db_host !== NULL)
        $this->config["db_host"] = $db_host;
      if ($db_user !== NULL)
        $this->config["db_user"] = $db_user;
      if ($db_password !== NULL)
        $this->config["db_pass"] = $db_password;
      if ($db_name !== NULL)
        $this->config["db_name"] = $db_name;
      if ($db_port !== NULL)
        $this->config["db_port"] = $db_port;
      if ($db_charset !== NULL)
        $this->config["db_charset"] = $db_charset;
    }

    if ($this->isMysqli($db_host)) {
      // mysqli is passed, just load it
      $this->setConncetion($db_host);
    }
  }

  /**
   * Support function to check if the variable is mysqli object
   *
   * @return bool
   */
  private function isMysqli($connection) {
    if (is_object($connection) AND get_class($connection) == "mysqli") {
      $out = true;
    } else {
      $out = false;
    }
    return $out;
  }

  /**
   * Get the current mysqli object
   *
   * @return object(mysqli) / bool(false)
   */
  public function getConncetion() {
    return $this->link;
  }

  /**
   * Set the current mysqli object
   *
   * @return bool
   */
  public function setConncetion($connection) {
    if ($this->isMysqli($connection)) {
      $this->link = $connection;
      $out = true;
    } else {
      $out = false;
    }
    return $out;
  }


  /**
   * Mysqli connect
   *
   * @return object
   */
  public function connect($db_host = NULL, $db_user = NULL, $db_password = NULL, $db_name = NULL, $db_port = NULL) {
    return new \mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
  }

  /**
   * Mysqli connection with charset.
   * Creates new connection if not yet connected.
   *
   * @return mysqli
   */
  private function getDB() {
    if ($this->link === false) {
      $connection = $this->connect($this->config["db_host"], $this->config["db_user"], $this->config["db_pass"], $this->config["db_name"], $this->config["db_port"]);
      if ($this->isMysqli($connection) AND $connection->errno === 0) {
        $connection->query("SET CHARACTER SET " . $this->config["db_charset"] . ";");
        $this->link = $connection;
      }
    }

    return $this->link;
  }


   /**
    * Returns single value from array based on a key.
    * This function is ment for inline use.
    *
    * @param array $array - the source array
    * @param string $key - the choosen key
    * @return string
    */
  private function oneFromArray($array, $key = 0) {
    if (ISSET($array[$key]))
      {
      return $array[$key];
      }
   }

   /**
    * Fix input string or array before data manipulation.
    *
    * @param string $data
    * @param int $length - maximum legth of the string to trim the input data on the fly
    * @return string
    */
  public function escape($data, $length = false) {
    if ($length !== false)
      {
      if (is_numeric($length))
        {
        $data = substr($data, 0, $length);
        }
      }

    if (is_array($data))
      {
        foreach ($data AS $index => $value)
        {
          $data[$index] = $this->escape($value, $length);
        }
      return $data;
      }
    else
      {
        return \mysqli_real_escape_string($this->getDB(), $data);
      }
  }

   /**
    * Any Query to be processed (default call)
    *
    * @param string $sql - the query
    */
  public function data($sql) {
    if ($this->config["log"]) {
      $this->sqllog[] = $sql;
    }

    $result = $this->getDB()->query($sql);
    if ($this->getDB()->errno)
      {
      echo ($this->getDB()->error . " (" . $this->getDB()->errno . ")"); // TODO: throw new exception ?
      }

    return $result;
  }

   /**
    * Any Query to be processed (compatability call)
    *
    * @param string $sql - the query
    */
  public function query($sql) {
    return $this->data($sql);
  }

   /**
    * INSERT Query (default call)
    *
    * @param string $sql - the query
    * @return int last insert ID
    */
  public function datai($sql) {
    //$this->getDB()->query($sql);
    $this->data($sql);
    return $this->getDB()->insert_id;
  }

   /**
    * INSERT Query (compatability call)
    *
    * @param string $sql - the query
    * @return int last insert ID
    */
  public function insert($sql) {
    return $this->datai($sql);
  }

   /**
    * UPDATE Query (default call)
    *
    * @param string $sql - the query
    * @return int number of affected rows
    */
  public function datau($sql) {
    $this->getDB()->query($sql);
    return $this->getDB()->affected_rows;
  }

   /**
    * UPDATE Query (compatability call)
    *
    * @param string $sql - the query
    * @return int number of affected rows
    */
  public function update($sql) {
    return $this->datau($sql);
  }

   /**
    * Returns array for single line of resource
    *
    * @param resource
    * @param bool $both - return both string and numeric keys in output array
    * @return array for single row
    */
  private function mfa($data, $both = false) {
    if ($data->num_rows)
      {
      if ($both)
        {
        return $data->fetch_array(MYSQLI_BOTH);
        }
      else
        {
        return $data->fetch_array(MYSQLI_ASSOC);
        }
      }
    return false;
  }

   /**
    * SELECT data from database, output is two dimensional array - native array + rows
    *
    * @param string $sql - sql query
    * @return array - keys are native, values are rows with array as labeled by each column
    */
  public function lines($sql, $both = false) {
    $data_out = array();
    $sql_data = $this->data($sql);
    while ($data = $this->mfa($sql_data, $both))
      {
      $data_out[] = $data;
      }
    return $data_out;
  }

   /**
    * SELECT data from database, output is two dimensional array - key based array + rows
    *
    * @param string $sql - sql query
    * @return array - keys are first value from row, values are rows with array as labeled by each column
    */
  public function more($sql, $both = false) {
    $data_out = array();
    foreach ($this->lines($sql, $both) AS $data)
      {
      $data_out[current($data)] = $data;
      }
    return $data_out;
  }

   /**
    * SELECT data from database, output is three dimensional array - double keys based array + third column as a value
    *
    * @param string $sql - sql query
    * @return array - keys are first and second value from row, values is third row
    */
  public function three($sql) {
    $data_out = array();
    foreach ($this->lines($sql, true) AS $data)
      {
      $data_out[$data[0]][$data[1]] = $data[2];
      }
    return $data_out;
  }

   /**
    * SELECT data from database, output is two dimensional array - key based array + single value - the pair
    *
    * @param string $sql - sql query
    * @return array - key is first value from row, value is second value from row
    */
  public function two($sql) {
    $data_out = array();
    foreach ($this->lines($sql, true) AS $data)
      {
      $data_out[$data[0]] = $data[1];
      }
    return $data_out;
  }

   /**
    * SELECT data from database, output is two dimensional array - native array + values
    *
    * @param string $sql - sql query
    * @return array - keys are native, value is first value from row
    */
  public function less($sql) {
    $data_out = array();
    foreach ($this->lines($sql, true) AS $data)
      {
      $data_out[] = $this->oneFromArray($data, 0);
      }
    return $data_out;
  }

   /**
    * SELECT data from database, output is three dimensional array - keys and native based array + value
    *
    * @param string $sql - sql query
    * @return array - first key is first value from row, second key is native, values is second value from row
    */
  public function tree($sql) {
    $data_out = array();
    foreach ($this->lines($sql, true) AS $data)
      {
      $data_out[$data[0]][] = $data[1];
      }
    return $data_out;
  }

   /**
    * SELECT data from database, output is single value
    *
    * @param string $sql - sql query
    * @return string - first value field of the first row only
    */
  public function one($sql, $col = 0) {
    return $this->oneFromArray($this->rowx($sql, true), $col);
  }

   /**
    * SELECT data from database, output is single row
    *
    * @param string $sql - sql query
    * @param bool $both - to return both string and numeric keys
    * @return array - first row only
    */
  public function rowx($sql, $both = false) {
    return $this->mfa($this->data($sql), $both);
  }


   /**
    * Group input fix for rows and multi inserts
    *
    * @param array $pole - array of values to be escaped
    * @param array $special - array of colums to be skipped from fixing, for example containing NOW() or intentional default "empty values"
    * @param bool $values_only - multiinsert = true, singleinsert = false (default)
    * @param bool $escape - do the fixing, skip fixing for already fixed values, false = do not fix, true = do fix (default)
    * @return string - multiinsert based on params
    */
  public function dataset($pole = array(), $special = array(), $values_only = false, $escape = true) {
    if (!is_array($special) AND is_string($special)) {
      $special = array($special);
    }
    $q = "";
    foreach($pole AS $index => $hodnota)
      {
      if ($escape)
        $hodnota = $this->escape($hodnota);

      $is_numeric = true;
      if (($hodnota + 0) !== $hodnota) // is_numeric ma problem napriklad s +420
        $is_numeric = false;
      if ($this->OneFromArray(array_flip($special),$index) === NULL AND !$is_numeric)
        $hodnota = "'" . $hodnota . "'";

      if (!$values_only)
        $q .= "`" . $index . "` = ";

      $q .= "" . $hodnota . ", ";
      }

    $q = substr($q, 0,-2);
    if ($values_only)
      $q = ", (" . $q . ")";

    return $q;
  }

  /**
   * Returns the SQL log
   *
   * @return string / bool(false)
   */
  public function readLog($sep = "\n") {
      if (count($this->sqllog)) {
        return implode($sep, $this->sqllog);
      } else {
        return false;
      }
  }

  /**
   * Enables the SQL log
   *
   * @return bool - true
   */
  public function setLog($value = false) {
    $this->config["log"] = $value;
    return true;
  }
}
?>