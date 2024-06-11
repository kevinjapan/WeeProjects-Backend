<?php
include_once '../app/Utility.php';

class Response {

   public static function send($query_key,$success,$message,$data = null) {

      $response = [
         'query_key' => $query_key,
         'outcome' => $success ? 'success' : 'fail',
         'message' => $message,    
      ];

      if($data) $response['data'] = $data;

      // we package debug info to permit process to continue (cf. var_dump)
      if(Utility::$debug_info) $response['data']['debug_info'] = Utility::$debug_info;

      echo json_encode($response);
   }
}
        
 