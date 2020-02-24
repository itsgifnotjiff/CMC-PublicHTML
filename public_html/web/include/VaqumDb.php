<?php

class VaqumDb {
   protected $host;
   protected $name;
   protected $port;

   public $conn = null;


   protected function readConfig() {
      $userProps = posix_getpwuid(posix_geteuid());

      $filePath = "{$userProps['dir']}/.verification/config";
      $fileHandle = fopen($filePath, 'r') or die("Can not open config file! ({$filePath})");
      $contents = fread($fileHandle, filesize($filePath));
      fclose($fileHandle);

      foreach (explode("\n", $contents) as $line) {
         $parts = explode('=', $line);
         switch ($parts[0]) {
            case 'db_host':
               $this->host = $parts[1];
               break;
            case 'db_name':
               $this->name = $parts[1];
               break;
            case 'db_port':
               $this->port = $parts[1];
               break;
         }
      }
   }


   public function niceExec($query, $echoQuery = false, $printTiming = false, $dieOnError = true) {
      if ($echoQuery) {
         print($query . "\n");
      }

      $beginTime = microtime(true);
      $rtn = $this->conn->exec($query);
      $endTime = microtime(true);
      if ( $rtn === false && $dieOnError) {
         $driverMsg = $this->conn->errorinfo();
         throw new Exception($driverMsg['2']);
      }

      if ($printTiming) {
         print("-- Execution time : " . ($endTime - $beginTime) . " s\n");
      }
      if ($echoQuery) {
         print("-- {$rtn} rows affected.\n");
      }
      return $rtn;
   }


   public function query($query) {
      return $this->conn->query($query, PDO::FETCH_ASSOC);
   }


   public function quote($str) {
      return $this->conn->quote($str);
   }


   public function errorInfo() {
      return $this->conn->errorInfo();
   }

   public function fetchAssoc($query) {
      $result = $this->conn->query($query);
      if ($result) {
         return $result->fetchAll(PDO::FETCH_ASSOC);
      } else {
         die("Query error");
      }
   }


   protected function connect($persistent, $host, $port, $name) {
      if ( is_null($host) ) {
         $host = $this->host;
      }
      if ( is_null($port) ) {
         $port = $this->port;
      }
      if ( is_null($name) ) {
         $name = $this->name;
      }
      $connStr = "pgsql:host={$host};port={$port};dbname={$name}";
      try {
         $this->conn = new PDO($connStr, null, null, array(
            PDO::ATTR_PERSISTENT => $persistent
         ));
      } catch (PDOException $e) {
         die("Connection failed:\n\t" . $e->getMessage() . "\n");
      }

      if (PHP_SAPI != "cli") {
         $sessionUserStr = $this->conn->quote("guest");
         if ( array_key_exists('PHP_AUTH_USER', $_SERVER) ) {
            if ( !( ($_SERVER['PHP_AUTH_USER'] == 'afsuair') || ($_SERVER['PHP_AUTH_USER'] == 'afsudev') ) ) {
               $sessionUserStr = $this->conn->quote($_SERVER['PHP_AUTH_USER']);
            }
         }

         // Run the session as the authenticated user instead of the Web server's user
         $query = "SET SESSION ROLE {$sessionUserStr}";
         $this->niceExec($query);
      }
   }


   protected function disconnect() {
      $this->conn = null;
   }


   function __construct($persistent = true, $host = null, $port = null, $name = null) {
      $this->readConfig();
      $this->connect($persistent, $host, $port, $name);
   }


   function __destruct() {
      $this->disconnect();
   }


   static function getPdoType($statement, $columnIdx) {
      $meta = $statement->getColumnMeta($columnIdx);
      switch ($meta['pdo_type']) {
         case PDO::PARAM_BOOL:
            return 'PDO::PARAM_BOOL';
         case PDO::PARAM_NULL:
            return 'PDO::PARAM_NULL';
         case PDO::PARAM_INT:
            return 'PDO::PARAM_INT';
         case PDO::PARAM_STR:
            return 'PDO::PARAM_STR';
         case PDO::PARAM_STR_NATL:
            return 'PDO::PARAM_STR_NATL';
         case PDO::PARAM_STR_CHAR:
            return 'PDO::PARAM_STR_CHAR';
         case PDO::PARAM_LOB:
            return 'PDO::PARAM_LOB';
         case PDO::PARAM_STMT:
            return 'PDO::PARAM_STMT';
         case PDO::PARAM_INPUT_OUTPUT:
            return 'PDO::PARAM_INPUT_OUTPUT';
         default:
            return 'Unknown';
      }
   }
}

?>