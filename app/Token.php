<?php


class Token {

   
   // Token self-verify
   public $is_verified = false;

   // Token Header - associative array
   private $token_header;

   // Token Payload - associative array
   private $token_payload;

   // Token Signature - string
   private $token_signature;


   public function __construct() {

   }


   //
   // Load rcvd bearer token into this Token
   //
   public function load($bearer_token_str) {

      $token_parts = explode('.',$bearer_token_str);

      if(count($token_parts) !== 3) {
         return false;
      }

      $decoded_header = base64_decode($token_parts[0]);
      $decoded_payload = base64_decode($token_parts[1]);

      if($decoded_header && $decoded_payload) {

         $this->token_header = json_decode($decoded_header,true);
         $this->token_payload =  json_decode($decoded_payload,true);
         $this->token_signature = $token_parts[2];

         if(!is_null($this->token_header) && !is_null($this->token_payload)) {
            
            // check signature
            $data = $token_parts[0] . "." . $token_parts[1];

            $secret_key = getenv('TOKEN');   
            // to do : verify we have rtrvd a secret key and it's valid (format etc).

            $valid_signature = Utility::generate_token_signature($data,$secret_key);

            if($this->token_signature !== $valid_signature) {
               return false;
            }

            // check expiry
            if($this->payload('exp') < time()) {
               return false;
            }

            $this->is_verified = true;
            return true;
         }
      }
      return false;
   }

   public function payload($field) {
      return $this->token_payload[$field];        
   }

   public function header($field) {
      return $this->token_header[$field];    
   }

   public function signature() {
      return $this->token_signature;
   }

   private function test() {
      
   }

}
