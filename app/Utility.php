<?php

//
// general global utilities 
// 

class Utility {

   //
   // since we have a minimum of configuration, we bundle in Utility 
   //


   // Password configuration
   public static $password_min_len = 8;
   public static $password_max_len = 36;


   // We can package debug info in Response::send() - allowing flow to continue
   public static $debug_info;


   // Sort CSV
   public static function sort_csv($csv) {
      $temp_array = explode(',',$csv);
      sort($temp_array);
      return implode(',',array_unique($temp_array));
   }


   // Validate Password
   // future : review password requirements
   public static function is_valid_password($password) {

      // min length
      if(strlen($password) < self::$password_min_len) {
         return false;
      }

      // contains at least one character and one number
      if(!preg_match("/[a-z]/i",$password) || !preg_match("/[0-9]/",$password) ) {
         return false;
      }
      return true;
   }


   // Random String
   public static function str_random($len = 64) {
      $len = $len < 8 ? 8 : $len;
      return bin2hex(random_bytes(($len-($len%2))/2));
   }

   
   // Generate a JWT Signature
   public static function generate_token_signature($data,$secret_key) {
      $hashing_algorithm = 'sha256';
      $binary_output = true;
      $signature = hash_hmac($hashing_algorithm,$data,$secret_key,$binary_output);
      return str_replace(['+','/','='],['-','_',''],base64_encode(($signature)));
   }



}
        
 