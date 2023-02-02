<?php
/**
 * DB - Hubi�ova t��da pro pr�ci s DB, p�edev��m pro opakovan� p�e�v�k�v�n� v�stup�
 * Ur�eno v�hradn� pro pr�ci s MySQL
 *
 * @package Model
 * @version 3.99 short (2018-12-05)
 * @copyright 2008 - NOW() Tom� Hub�lek
 * @author Tom� Hub�lek
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

    // konfigurace pro constuct
    if ($db_host != NULL)
      $this->config["db_host"] = $db_host;
    if ($db_user != NULL)
      $this->config["db_user"] = $db_user;
    if ($db_password != NULL)
      $this->config["db_pass"] = $db_password;
    if ($db_name != NULL)
      $this->config["db_name"] = $db_name;
    if ($db_port != NULL)
      $this->config["db_port"] = $db_port;
    if ($db_charset != NULL)
      $this->config["db_charset"] = $db_charset;
  }

  /**
   * navaze spojeni s mysql podle configu
   */
  private function connect() {
    return new mysqli($this->config["db_host"], $this->config["db_user"], $this->config["db_pass"], $this->config["db_name"], $this->config["db_port"]);
  }
  /**
   * Vrac� objekt MySQLi p�ipojen� k DB
   *
   * @return mysqli
   */
  private function getDB()
    {
    if ($this->link === false)
      {
      $this->link = $this->connect();
      $this->link->query("SET CHARACTER SET " . $this->config["db_charset"] . ";");
      }

    return $this->link;
    }

  /**
   * datab�zov� link (mysqli)
   */
  public $link = false;

   /**
    * Vrac� jednu hodnotu z pole, podle p�edur�en�ho kl��e
    *
    * @param array $array - pole pro v�b�r
    * @param string $key - datov� kl��
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
    * O�et�� string nebo pole string� p�ed vlo�en�m do DB
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
          $data[$index] = $this->escape($value);
        }
      return $data;
      }
    else
      {
        return mysqli_real_escape_string($this->getDB(), $data);
      }
    }

   /**
    * libovoln� dotaz do DB
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
      echo ($this->getDB()->error . " (" . $this->getDB()->errno . ")");
      }
    return $result;
    }

   /**
    * libovoln� dotaz do DB
    *
    * @param string $sql - sql dotaz
    */
  public function query($sql)
    {
    return $this->data($sql);
    }

   /**
    * libovoln� dotaz do DB
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
    * libovoln� dotaz do DB
    *
    * @param string $sql - sql dotaz, insert
    * @return last insert ID
    */
  public function insert($sql)
    {
    return $this->datai($sql);
    }

   /**
    * libovoln� dotaz do DB
    *
    * @param string $sql - sql dotaz, update
    * @return po�et ovlivn�n�ch ��dek
    */
  public function datau($sql)
    {
    $this->getDB()->query($sql);
    return $this->getDB()->affected_rows;
    }

   /**
    * vrac� pole pro jeden ��dek z resoursu
    *
    * @param resource
    * @param bool $both - vracet i ciselne prezentaceT
    * @return pole jednoho ��dku
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
    * dotaz do DB, v�sledek vrac� ve dvourozm�rn�m poli - ��dek, sloupec
    *
    * @param string $sql - sql dotaz, SELECT na n sloupc�
    * @return Array - jeden ��dek v poli, n�zvy sloupc� jsou kl��e v poli
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
    * dotaz do DB, v�sledek vrac� ve dvourozm�rn�m poli - kde kl��em ��dku je prvn� sloupec z ��dku, sloupec
    *
    * @param string $sql - sql dotaz, SELECT na n sloupc�
    * @return Array - kl��em ��dku je prvn� sloupec z ��dku, n�zvy sloupc� jsou kl��e v druh�m poli
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
    * dotaz do DB, v�sledek vrac� v trojrozm�rn�m poli - kde prvn�mi dv�ma kl��i ��dk� jsou prvn� dv� hodnoty z dotazu a hodnotou je hodnota t�et�
    *
    * @param string $sql - sql dotaz, SELECT na 3 sloupce (v�ce je ignorov�no)
    * @return Array - prvn� hodnota se st�v� kl��em, druh� hodnotou
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
    * dotaz do DB pr�v� na dva sloupce, v�sledek vrac� v poli
    *
    * @param string $sql - sql dotaz, SELECT na dva sloupce (v�ce je ignorov�no)
    * @return Array - prvn� hodnota se st�v� kl��em, druh� hodnotou
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
    * dotaz do DB pr�v� na jeden sloupec, v�sledek vrac� v poli
    *
    * @param string $sql - sql dotaz, SELECT na jeden sloupec (v�ce je ignorov�no)
    * @return Array - prvn� hodnota p�irozen� pole, druh� hodnota je sloupec z ��dku
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
    * dotaz do DB, v�sledek vrac� v trojrozm�rn�m poli - kde prvn�mi dv�ma kl��i ��dk� jsou prvn� dv� hodnoty z dotazu a hodnotou je hodnota t�et�
    *
    * @param string $sql - sql dotaz, SELECT na 3 sloupce (v�ce je ignorov�no)
    * @return Array - prvn� hodnota se st�v� kl��em, druh� hodnotou
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
    * dotaz do DB pr�v� na jednu hodnotu, tedy 1 sloupec + LIMIT 1
    *
    * @param string $sql - sql dotaz, SELECT na jeden sloupec jednu hodnotu (v�ce je ignorov�no)
    * @return string - prvn� hodnota p�irozen� pole, druh� hodnota je sloupec z ��dku
    */
  public function one($sql, $col = 0)
    {
    return $this->oneFromArray($this->rowx($sql, true), $col);
    }

   /**
    * dotaz do DB, od kter�ho se o�ek�v� pr�v� jeden ��dek ve v�stupu, tedy LIMIT 1
    *
    * @param string $sql - sql dotaz, SELECT
    * @param bool $both - Vracet i ciselne prezentaceT
    * @return Array - jeden ��dek v poli, n�zvy sloupc� jsou kl��e v poli
    */
  public function rowx($sql, $both = false)
    {
    return $this->mfa($this->data($sql), $both);
    }

   /**
    * hromadn� escapov�n� hodnot, pro single i multi inserty
    *
    * @param Array $pole - pole hodnot k escapov�n�
    * @param Array $special - pole sloupc� kter� nebudou escapov�ny, nap��klad obsahuj�c� NOW() (default pr�zdn�)
    * @param Bool $values_only - multiinsert = true, singleinsert = false (default)
    * @param Bool $escape - neescapovat, nap��klad pokud ji� jednou escapov�no je, false = neescapovatm true = escapovat (default)
    * @return string - multiinsert dle parametr�
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
   * na�tle log SQL dotaz�
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
   * zap�n� a vyp�na logov�n� SQL dotaz�
   */
  public function setLog($value = false)
    {
    $this->config["log"] = $value;
    }
}
?>