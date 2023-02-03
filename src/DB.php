<?php
/**
 * DB - Hubičova třída pro práci s DB, především pro opakované přežvýkávání výstupů
 * Určeno výhradně pro práci s MySQL
 *
 * @package Model
 * @version 3.99 short (2018-12-05)
 * @copyright 2008 - NOW() Tomáš Hubálek
 * @author Tomáš Hubálek
 * @license BSD 3-Clause License
 */

namespace hubalekt\DB;

class DB
{
  /**
   * konfigurace pro spojeni s DB
   */
  private $sqllog = array();

  private $config = array(
      "db_host" => "localhost",
      "db_user" => "root",
      "db_pass" => "root",
      "db_name" => "test",
      "db_port" => "3306",
      "db_charset" => "utf8",
      "log" => false,
    );

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
      // mysqli is passed
      $this->setConncetion($db_host);
    }

  }

  /**
   * 
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
   * 
   */
  public function getConncetion() {
    return $this->link;
  }

  /**
   * 
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
   * navaze spojeni s mysql podle configu
   */
  public function connect($db_host = NULL, $db_user = NULL, $db_password = NULL, $db_name = NULL, $db_port = NULL) {
    return new \mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
  }

  /**
   * Vrací objekt MySQLi připojený k DB
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
   * databázový link (mysqli)
   */
  public $link = false;

   /**
    * Vrací jednu hodnotu z pole, podle předurčeného klíče
    *
    * @param array $array - pole pro výběr
    * @param string $key - datový klíč
    * @return string
    */
  private function oneFromArray($array, $key = 0)
    {
    if (ISSET($array[$key]))
      {
      return $array[$key];
      }
    }

   /**
    * Ošetří string nebo pole stringů před vložením do DB
    *
    * @param string $data
    * @return string
    */
  public function escape($data, $length = false)
    {
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
    * libovolný dotaz do DB
    *
    * @param string $sql - sql dotaz
    */
  public function data($sql)
    {
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
    * libovolný dotaz do DB
    *
    * @param string $sql - sql dotaz
    */
  public function query($sql)
    {
    return $this->data($sql);
    }

   /**
    * libovolný dotaz do DB
    *
    * @param string $sql - sql dotaz, insert
    * @return last insert ID
    */
  public function datai($sql)
    {
    //$this->getDB()->query($sql);
    $this->data($sql);
    return $this->getDB()->insert_id;
    }

   /**
    * libovolný dotaz do DB
    *
    * @param string $sql - sql dotaz, insert
    * @return last insert ID
    */
  public function insert($sql)
    {
    return $this->datai($sql);
    }

   /**
    * libovolný dotaz do DB
    *
    * @param string $sql - sql dotaz, update
    * @return počet ovlivněných řádek
    */
  public function datau($sql)
    {
    $this->getDB()->query($sql);
    return $this->getDB()->affected_rows;
    }

   /**
    *
    */
  public function update($sql)
    {
    return $this->datau($sql);
    }

   /**
    * vrací pole pro jeden řádek z resoursu
    *
    * @param resource
    * @param bool $both - vracet i ciselne prezentaceT
    * @return pole jednoho řádku
    */
  private function mfa($data, $both = false)
    {
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
    * dotaz do DB, výsledek vrací ve dvourozměrném poli - řádek, sloupec
    *
    * @param string $sql - sql dotaz, SELECT na n sloupců
    * @return Array - jeden řádek v poli, názvy sloupců jsou klíče v poli
    */
  public function lines($sql, $both = false)
    {
    $data_out = array();
    $sql_data = $this->data($sql);
    while ($data = $this->mfa($sql_data, $both))
      {
      $data_out[] = $data;
      }
    return $data_out;
    }

   /**
    * dotaz do DB, výsledek vrací ve dvourozměrném poli - kde klíčem řádku je první sloupec z řádku, sloupec
    *
    * @param string $sql - sql dotaz, SELECT na n sloupců
    * @return Array - klíčem řádku je první sloupec z řádku, názvy sloupců jsou klíče v druhém poli
    */
  public function more($sql, $both = false)
    {
    $data_out = array();
    foreach ($this->lines($sql, $both) AS $data)
      {
      $data_out[current($data)] = $data;
      }
    return $data_out;
    }

   /**
    * dotaz do DB, výsledek vrací v trojrozměrném poli - kde prvními dvěma klíči řádků jsou první dvě hodnoty z dotazu a hodnotou je hodnota třetí
    *
    * @param string $sql - sql dotaz, SELECT na 3 sloupce (více je ignorováno)
    * @return Array - první hodnota se stává klíčem, druhá hodnotou
    */
  public function three($sql)
    {
    $data_out = array();
    foreach ($this->lines($sql, true) AS $data)
      {
      $data_out[$data[0]][$data[1]] = $data[2];
      }
    return $data_out;
    }

   /**
    * dotaz do DB právě na dva sloupce, výsledek vrací v poli
    *
    * @param string $sql - sql dotaz, SELECT na dva sloupce (více je ignorováno)
    * @return Array - první hodnota se stává klíčem, druhá hodnotou
    */
  public function two($sql)
    {
    $data_out = array();
    foreach ($this->lines($sql, true) AS $data)
      {
      $data_out[$data[0]] = $data[1];
      }
    return $data_out;
    }

   /**
    * dotaz do DB právě na jeden sloupec, výsledek vrací v poli
    *
    * @param string $sql - sql dotaz, SELECT na jeden sloupec (více je ignorováno)
    * @return Array - první hodnota přirozené pole, druhá hodnota je sloupec z řádku
    */
  public function less($sql)
    {
    $data_out = array();
    foreach ($this->lines($sql, true) AS $data)
      {
      $data_out[] = $this->oneFromArray($data, 0);
      }
    return $data_out;
    }

   /**
    * dotaz do DB, výsledek vrací v trojrozměrném poli - kde prvními dvěma klíči řádků jsou první dvě hodnoty z dotazu a hodnotou je hodnota třetí
    *
    * @param string $sql - sql dotaz, SELECT na 3 sloupce (více je ignorováno)
    * @return Array - první hodnota se stává klíčem, druhá hodnotou
    */
  public function tree($sql)
    {
    $data_out = array();
    foreach ($this->lines($sql, true) AS $data)
      {
      $data_out[$data[0]][] = $data[1];
      }
    return $data_out;
    }

   /**
    * dotaz do DB právě na jednu hodnotu, tedy 1 sloupec + LIMIT 1
    *
    * @param string $sql - sql dotaz, SELECT na jeden sloupec jednu hodnotu (více je ignorováno)
    * @return string - první hodnota přirozené pole, druhá hodnota je sloupec z řádku
    */
  public function one($sql, $col = 0)
    {
    return $this->oneFromArray($this->rowx($sql, true), $col);
    }

   /**
    * dotaz do DB, od kterého se očekává právě jeden řádek ve výstupu, tedy LIMIT 1
    *
    * @param string $sql - sql dotaz, SELECT
    * @param bool $both - Vracet i ciselne prezentaceT
    * @return Array - jeden řádek v poli, názvy sloupců jsou klíče v poli
    */
  public function rowx($sql, $both = false)
    {
    return $this->mfa($this->data($sql), $both);
    }

   /**
    * hromadné escapování hodnot, pro single i multi inserty
    *
    * @param Array $pole - pole hodnot k escapování
    * @param Array $special - pole sloupců které nebudou escapovány, například obsahující NOW() (default prázdné)
    * @param Bool $values_only - multiinsert = true, singleinsert = false (default)
    * @param Bool $escape - neescapovat, například pokud již jednou escapováno je, false = neescapovatm true = escapovat (default)
    * @return string - multiinsert dle parametrů
    */
  public function dataset($pole = array(), $special = array(), $values_only = false, $escape = true)
    {
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
   * načtle log SQL dotazů
   */
  public function readLog($sep = "\n")
    {
      if (count($this->sqllog)) {
        return implode($sep, $this->sqllog);
      } else {
        return false;
      }
    }

  /**
   * zapíná a vypína logování SQL dotazů
   */
  public function setLog($value = false)
    {
    $this->config["log"] = $value;
    }
}
?>