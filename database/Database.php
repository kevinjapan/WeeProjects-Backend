<?php
include_once '../app/Log.php';



class Database {


   private static $instance = null;
   private $connection;


   private function __construct() {

      $this->connection = null;

      $host = getenv('HOST');
      $db_name = getenv('DB_NAME');
      $username = getenv('DB_USERNAME');
      $password = getenv('DB_PASSWORD');

      // to do : bug - on every c. 10th time it fails to initialise values in the credentials variables.
      // Log::record('Connecting to database : HOST:' . $host . ' DATABASE:' . $db_name . ' USERNAME:' . $username . ' PASSWORD:' . $password . '     ');
      
      $dsn = "mysql:host={$host};dbname={$db_name}";

      $this->connection = new PDO($dsn,
                                  $username,
                                  $password,[
                                  PDO::ATTR_EMULATE_PREPARES => false,
                                  PDO::ATTR_STRINGIFY_FETCHES => false,
                              ]);

      return $this->connection;
   }

   public static function get_instance() {
      
      if(!self::$instance) {
         self::$instance = new Database();
      }
      return self::$instance;
   }

   public function get_connection() {
      return $this->connection;
   }
}