<?php
include_once '../app/Log.php';
include_once '../app/Response.php';


// Env
// loads environmental variables
// access w/ getenv('key_name')

class Env {

   public static function load() {

      // to do : try/catch? we need to notify front-end cleanly if we fail to load .env (eg missing file)
      // (system not functioning - don't explicitly say anything about env vars!)
      // is_readable($filename)...

      $contents = file_get_contents(dirname(__DIR__)."/public/.env");  

      if($contents === false) {
         // to do : handle fail...
         Response::send('setup',false,'weeprojects failed to initialise.');
         die();
      }

      // Log::record('Getting env vars : ' . $contents);
      
      $lines = explode("\n",$contents);

      foreach($lines as $line) {

         // Log::record('LINE : ' . $line . '     ');
         // exclude comments - starting w/ '#' and empty values - if no value after '='
         preg_match("/([^#]+)\=(.*)/",$line,$matches);

         if(isset($matches[2])) {
            putenv(trim($line));
         }
      }

      // to do : ensure we are blocking public access to .env file at server.
      //       : add this to our testing suite (that .env file is not accessible).

   }
}
        
 