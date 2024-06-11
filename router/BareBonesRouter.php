<?php
include_once '../app/Response.php';
include_once '../app/BearerToken.php';
include_once '../Controllers/AuthenticationController.php';


// BareBonesRouter class
// to 'exact' match, we check both (1) the number of tokens (path segments) and (2) the desired reg exp
// longer uri's may contain our route (and hence part match) but will fail immediately on token numbers.
// we only route - we don't validate rcvd routes or URIs - any non-match simply fails


class BareBonesRouter {

   //
   // the current URI request rcvd
   //
   private $uri;

   //
   // the current HTTP request method
   //
   private $request_method;

   // 
   // flag to inform notFound method
   //
   private $route_match_found = false;


   function __construct() {
      $this->request_method = $_SERVER['REQUEST_METHOD'];
      $this->uri = $_SERVER['REQUEST_URI'];
   }



   //
   // public HTTP methods
   //
   // All routes are by default 'protected' - requiring authentication.
   // Only routes marked route_access 'public' are not authenticated.
   //

   // Get
   public function get($route, $controller_array, $route_access = 'protected') {

      if(($this->request_method !== 'GET') || ($this->route_match_found)) return false;

      // flag 'found' to exclude subsequent routes 
      $this->route_match_found = $this->uri_matches_route($route);

      if($this->route_match_found) {
         if($route_access !== 'public') $this->authenticate();
         $this->spin_up_controller($route,$controller_array);
      }
   }


   // Post
   public function post($route, $controller_array, $route_access = 'protected') {

      if(($this->request_method !== 'POST') || ($this->route_match_found)) return false;

      $this->route_match_found = $this->uri_matches_route($route);

      if($this->route_match_found) {
         if($route_access !== 'public') $this->authenticate();
         $this->spin_up_controller($route,$controller_array);
      }
   }

   
   // Put
   public function put($route, $controller_array, $route_access = 'protected') {

      if(($this->request_method !== 'PUT') || ($this->route_match_found)) return false;

      $this->route_match_found = $this->uri_matches_route($route);

      if($this->route_match_found) {
         if($route_access !== 'public') $this->authenticate();
         $this->spin_up_controller($route,$controller_array);
      }
   }


   // Delete
   public function delete($route, $controller_array, $route_access = 'protected') {

      if(($this->request_method !== 'DELETE') || ($this->route_match_found)) return false;

      $this->route_match_found = $this->uri_matches_route($route);

      if($this->route_match_found) {
         if($route_access !== 'public') $this->authenticate();
         $this->spin_up_controller($route,$controller_array);
      }
   }
   


   // 
   // Authentication
   //
   public function authenticate() {

      $authenticator = new AuthenticationController();
      if($authenticator->init_token()) {
         if(!BearerToken::get_bearer_token()) {
            Response::send('projects',false,'You need to be logged in to access this resource (Router:Authentication).');
            exit;
         }
      }
   }



   //
   // Check current route against the URI
   //
   private function uri_matches_route($route) {

      // check token (uri path segments) count
      if(substr_count(ltrim($this->uri,'/'), '/') !== substr_count(ltrim($route,'/'), '/')) {
         return false;
      }

      // replace any route params with regexp - then test uri against route
      $route_with_reg_exp = $this->replace_params_with_regexp($route);
      if(!preg_match($route_with_reg_exp,$this->uri)) {
         return false;
      }
      return true;
   }


   //
   // Spin up the Controller
   //
   private function spin_up_controller($route,$controller_array) {
      // spin up Controller, call given Controller method w/ any route parameters
      $controller_class = $controller_array[0];
      $controller = new $controller_class();
      $controller_action = $controller_array[1];
      $controller->$controller_action($this->get_itemised_route_parameters($route));
      return true;
   }

   //
   // Replace the named parameters in the route with reg exp
   //
   private function replace_params_with_regexp($raw_route) {
      $parameter_pattern = "/{[^}]*}/"; 
      $route_with_reg_exp = preg_replace($parameter_pattern, ".*", $raw_route);   
      return "/^" . str_replace("/","\/",$route_with_reg_exp) . "$/";
   }

   //
   //  Itemise route parameters
   //  extract and package as associative array for controllers
   //
   private function get_itemised_route_parameters($route) {

      $itemised_params = [];
      $route_tokens = [];
      $uri_tokens = [];
      $pos = stripos($route,mb_chr(123));
      if($pos === 0 || $pos !== FALSE) {
         $route_tokens = explode('/',trim($route,'/'));
         $uri_tokens = explode('/',trim($this->uri,'/'));
      }

      for($i=0;$i<count($route_tokens);$i++) {

         $pos = stripos($route_tokens[$i],mb_chr(123));
         if($pos === 0 || $pos !== FALSE) {

               $model_key = trim($route_tokens[$i],'{}');
               $model_key = explode(':',$model_key);

               // default col is 'id'
               if(count($model_key) === 1) array_push($model_key,'id');

               $param_item = array(
                  'col' => $model_key[1],
                  'value' => $uri_tokens[$i],
               );

               $itemised_params[$model_key[0]] = $param_item;
         }
      }
      return $itemised_params;
   }

   //
   //  Not Found URIs
   //  we don't use __destruct to prevent duplication of output if error thrown previously
   //  Must to be final call to router
   //
   public function notFound() {
      if($this->route_match_found ===  false) {
          
         echo json_encode(
               array('message' => 'Resource not found.')
         );      
      }
   }

}