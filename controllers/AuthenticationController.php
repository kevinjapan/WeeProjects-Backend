<?php
include_once '../database/Database.php';
include_once '../app/BearerToken.php';
include_once '../models/User.php';
include_once '../models/Project.php';

// Stateless API Authentication


// nice stackflow answer:
// "A few years ago, before the JWT revolution, a <token> was just a string with no intrinsic meaning,
// e.g. 2pWS6RQmdZpE0TQ93X. That token was then looked-up in a database, which held the claims for that token. 
// The downside of this approach is that DB access (or a cache) is required everytime the token is used.
// JWTs encode and verify (via signing) their own claims. This allows folks to issue short-lived JWTs 
// that are stateless (read: self-contained, don't depend on anybody else). They do not need to hit the DB.
// This reduces DB load and simplifies application architecture because only the service that issues the JWTs 
// needs to worry about hitting the DB/persistence layer (the refresh_token you've probably come across)."
// to do : remove/capture this.


// to do : support 'claims' and therefore authorization in our jwt. eg crud on Projects.




class AuthenticationController {


   private $database;
   private $db;



   public function __construct() {

      $this->database = Database::get_instance();
      $this->db = $this->database->get_connection();

   }


   //
   // Secure Bearer Token from HTTP header
   // constructor timing was out, so explicit method for securing authorization header
   //
   public function init_token() {
      
      $headers = getallheaders();

      if(isset($headers['Authorization'])) {         
         $token = new Token();
         if($token->load($headers['Authorization'])) {
            // make accessible as global Token Object to Controllers etc.
            BearerToken::set_bearer_token($token);
            return true;
         }
      }
      return false;
   }


   //
   // Login
   // Successful login returns a valid bearer_token to client
   //
   public function login() {

      $successful_login = false;
      $jwt = null;

      $data = json_decode(file_get_contents("php://input"),true);
      $user_name = '';

      if($data) {
      
         $rcvd_username = $data['username'];
         $rcvd_email = $data['email'];
         $rcvd_password = $data['password'];

         // to do : enable once we have legit credentials in test accounts
         // if(!filter_var($rcvd_email,FILTER_VALIDATE_EMAIL)) {
         //    Response::send('login',false,'Login attempt was unsuccessful.');
         //    exit;
         // }
         // if(!Utility::is_valid_password($rcvd_password)) {
         //    Response::send('login',false,'Login attempt was unsuccessful.');
         //    exit;
         // }

         // 'username' is our simple honeypot catch.
         if($rcvd_username === '' && $rcvd_email !== '' && $rcvd_password !== '') {

            $query = 'SELECT * FROM users WHERE email = ?';    // future : put sql in a Model?
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(1,$rcvd_email);

            $user_record = [];
            $stmt->execute();
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
               $user_record[] = $row;
            }
            if(count($user_record) > 0) {
               $user_record = $user_record[0];
               $user_name = $user_record['user_name'];

               if($user_record) {
                  
                  // to do : research : how should we handle passwords containing escaped chars.
                  // bug - we were failing on certain passwords containing '\/'
                  // eg    $2y$10$vt9ZbvxcDpT8gh.Fihuo.eJYvBTDeU016hy3WOKx\/MKT5JbbGGJf6
                  // workaround - we simply replace escaped chars for now
                  // to do : what is correct PHP function here to un-escape (?) these chars..
                  $test = str_replace("\/","/",$user_record['password_hash']);

                  if(password_verify($rcvd_password,$test)) {  
                     $jwt = $this->create_jwt($user_record);
                     $successful_login = true;
                  }
               }
            }
         }
      }

      // build response
      $data['bearer_token'] = $jwt;
      $data['user_name'] = $user_name;

      if($successful_login) {
         Response::send('login',true,'Login successful.',$data);
      }
      else {
         Response::send('login',false,'Login attempt was unsuccessful.');
      }
   }


   //
   // Logout - invalidate bearer Token
   // Generally never an issue - since we don't persist our Token - one will never get created server-side.
   // future : do we need additional layer of security - eg invalidate any token given to current user
   //          (would require some tie in with current user rather than all tokens..) 
   //
   public function logout() {
      BearerToken::set_bearer_token(null);      
   }



   // IMPORTANT :
   // all JWT claims are ultimately accessible on client - so, DO NOT expose sensitive info.
   // note, the parts are separately encoded - the final token is made from discrete parts.


   //
   // Create JWT
   //
   private function create_jwt($user) {

      // header 
      $header = json_encode(['type' => 'JWT','alg' => 'HS256']);
      $base64url_header = str_replace(['+','/','='],['-','_',''],base64_encode(($header)));

      // payload - claims can identify the user, check their role, and grant access to the resources or actions they are allowed to perform.
      $payload = json_encode([
         'iss' => 'kjadfkljasdf',                  // issuer
         'sub' => 'sub something here',            // subject (eg the user id)
         'aud' => 'audience',                      // audience
         'iat' => 'issued at',                     // issued at
         'exp' => time() + BearerToken::get_bearer_token_lifespan(),        // expires at - Unix timestamp (seconds since January 1 1970) + lifespan
         'jti' => '3432dfdasfdsf',                 // id - prevents replay - once only token use
         'user_id' =>$user['id'],
         'user_name' => $user['user_name'],
         'email' => $user['email'],
         'permissions' => 'permissions to do stuff',
         'groups' => 'group_one,group_two'
      ]);
      $base64url_payload = str_replace(['+','/','='],['-','_',''],base64_encode(($payload)));

      // signature 
      $data = $base64url_header . "." . $base64url_payload;
      // to do : where store secret key? (generate?) - protect file in htaccess?  - .env file?


      $secret_key = 'oiuadfiocl6adofiuadof78890kladflll';
      $base64url_signature = Utility::generate_token_signature($data,$secret_key);

      // token
      $jwt = $base64url_header . '.' . $base64url_payload . '.' . $base64url_signature;
      return $jwt;
   }

}