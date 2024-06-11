<?php
include_once '../app/Utility.php';



// to do : log
// build separate log class (see below)
// build a log string (as array initially) as we go
// - then flush to file as script is about to exit
// (so only a single file operation for multiple log items)

class Log {

   public static function record($msg) {


      // to do : log to file
      // - switch to disable (only run when actually debugging)
      // - prevent file lock?
      echo($msg);
   }
}
        
 