<?php
include_once '../database/Database.php';
include_once '../app/Response.php';
include_once '../app/Utility.php';
include_once '../models/User.php';
include_once '../models/ProjectUser.php';



class UserController {

   private $database;
   private $db;

   public function __construct() {
      $this->database = Database::get_instance();
      $this->db = $this->database->get_connection();
   }



   //
   // Read All
   //
   public function read($route_params) {

      $user = new User($this->db);


      // resolve Project from route param ( typically '{project:slug}' )
      // if(isset($route_params['project'])) {
      //    $project = new Project($this->db);
      //    $project->load($route_params['project']['col'],$route_params['project']['value']);
      //    $project_id = $project->id;
      // }

      // resolve Task from route param
      // if(isset($route_params['task'])) {
      //    $task = new Task($this->db);
      //    $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
      //    $user->task_id = $task->id;
      // }

      // response
      if($user) {
         Response::send('users',true,'Users.',$user->read());

      }
      else {
         Response::send('users',false,'Users not found.');
      }
   }

   
   // 
   // Read all - including 'soft deleted'
   //
   public function read_inclusive($route_params) {

      $user = new User($this->db);

      // resolve Project from route param ( typically '{project:slug}' )
      // if(isset($route_params['project'])) {
      //    $project = new Project($this->db);
      //    $project->load($route_params['project']['col'],$route_params['project']['value']);
      //    $project_id = $project->id;
      // }

      // resolve Task from route param
      // if(isset($route_params['task'])) {
      //    $task = new Task($this->db);
      //    $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
      //    $user->task_id = $task->id;
      // }

      // response
      if($user) {
         Response::send('users',true,'Users.',$user->read(true));
      }
      else {
         Response::send('users',false,'Users not found.');
      }
   }

   

   //
   // Read Single
   //
   public function read_single($route_params) {

      $user = new User($this->db);
   
      $user->read_single($route_params['user']['col'],$route_params['user']['value']);

      $send_package = array(
         'id' => $user->id,
         'user_name' => $user->user_name,
         'email' => $user->email,
         'created_at' => $user->created_at,
         'updated_at' => $user->updated_at,
         'deleted_at' => $user->deleted_at
      );

      if($user->id) {
         Response::send('user',true,'User.',$send_package);
      }
      else {
         Response::send('user',false,'User not found.');
      }
   }



   //
   // Create Single
   // future : prevent duplicate username/email etc. server-side.
   //
   public function create($route_params) {

      $user = new User($this->db);
      $data = json_decode(file_get_contents("php://input"),true);

      if($data['user_name'] === '' || $data['email'] === '' || $data['password'] === '') {
         Response::send('create_user',false,'User not created. [ 1 ]');
         exit;
      }
      if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL)) {
         Response::send('create_user',false,'User not created. [ 2 ]');
         exit;
      }

      if($data['password'] !== $data['password_confirmation'] || !Utility::is_valid_password($data['password'])) {
         Response::send('create_user',false,'User not created. [ 3 ]');
         exit;
      }

      $user->user_name = $data['user_name'];
      $user->email = $data['email'];

      // password_hash is not a property of User
      $password_hash = password_hash($data['password'],PASSWORD_DEFAULT); 

      // create and get id of new User
      $new_user_id = $user->create($password_hash);
      $user->id = $new_user_id;
      
      if($data['projects']) {     
         $user->projects = Utility::sort_csv($data['projects']);
         $project_user = new ProjectUser($this->db);
         $project_user->register_projects($data['projects'],$new_user_id);
      }
      if($new_user_id) {
         Response::send('create_user',true,'User created.',$user);
      }
      else {
         Response::send('create_user',false,'User not created.');
      }
   }



   //
   // Update
   //
   public function update($route_params) {
        
      $user = new User($this->db);
      $data = json_decode(file_get_contents("php://input"));      

      if(!$data->id || !$data->user_name || !$data->email) {
         Response::send('update_user',false,'User not updated.');
         exit;
      }

      $user->id = $data->id;                    //
      $user->user_name = $data->user_name;      // fixed data - we currently don't permit changes - verify contain valid data
      $user->email = $data->email;              //

      $password = $data->password;              // password change is optional - may be empty string   
      $password_hash = '';
      if($password !== '') {
         // password change attempted - let's validate
         $password_confirmation = $data->password_confirmation;

         if($password === $password_confirmation && Utility::is_valid_password($password)) {
            $password_hash = password_hash($password,PASSWORD_DEFAULT);
         }         
      }

      // User's projects (registered in project_user)
      // Does rcvd csv list match existing? if not, update w/ new projects list
      $projects = str_replace(' ','',$data->projects);
      
      if($projects !== '') { 

         $current_projects = $user->projects($user->id); 

         // create csv of rcvd projects list (sorted, no duplicates)
         // we don't use sort_csv() since we need array below anyhow 
         $rcvd_projects_array = explode(',',$projects);
         sort($rcvd_projects_array);
         $no_duplicate_rcvd_projects = array_unique($rcvd_projects_array);
         $rcvd_projects = implode(',',$no_duplicate_rcvd_projects);

         // direct comparison of csv lists
         if($rcvd_projects !== $current_projects) {
            
            // build lists of added and removed projects
            $current_projects_array = explode(',',$current_projects);
            $additional_projects = array_diff($no_duplicate_rcvd_projects,$current_projects_array);
            $removed_projects = array_diff($current_projects_array,$no_duplicate_rcvd_projects);

            $user->add_projects($additional_projects);
            $user->remove_projects($removed_projects);
         }
         else {
            // echo('we have no change in projects.');
         }
      }

      // to do : we are returning complete user - ensure all fields are valid
      // public $projects;
      // public $created_at;
      // public $updated_at;
      // public $deleted_at;


      if($user->update($password_hash)) {
         // refresh User to gather complete updated dataset
         $user->load('id',$data->id);
         Response::send('update_user',true,'User updated.',$user);
      } else {
         Response::send('update_user',false,'User not updated.');
      }
   }


   // 
   // Delete
   // We perform 'soft deletes' on UI delete requests.
   // Currently, for 'soft deletes' we do not touch child artefacts
   // - we assume they are un-accessible since parent is 'soft deleted'.
   // Any possible issue with proliferation will be resolved by cascading 
   // deletes on delete_permanently() since they retain relationship.
   // Any possible issue with accessing children of 'soft deleted' artefacts 
   // is largely negated if we continue not to suppport end-point routes to 
   // individual artefacts (since all routes are through parent).
   //
   public function delete() {

      $user = new User($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $user->id = $data->id;
      $user->deleted_at = $data->deleted_at;

      if($user->delete()) {
         Response::send('delete_user',true,'User deleted.');
      } else {
         Response::send('delete_user',false,'User not deleted.');
      }
   
   }
      
   public function delete_permanently() {

      $user = new User($this->db);

      $data = json_decode(file_get_contents("php://input"));
      $user->id = $data->id;

      if($user->delete_permanently()) {
         Response::send('delete_user_permanently',true,'User permanently deleted.');
      }
      else {
         Response::send('delete_user_permanently',false,'User not permanently deleted.');
      }
   }


}