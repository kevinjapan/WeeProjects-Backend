<?php

//
// App-wide Token object
// 

class BearerToken {

   static protected $app_token_obj;
   static protected $lifespan = 7200;     // 2 hours in secs

   static public function set_bearer_token($value) {
      static::$app_token_obj = $value;
   }

   static public function get_bearer_token() {
      return static::$app_token_obj;
   }

   static public function get_bearer_token_lifespan() {
      return static::$lifespan;
   }
}