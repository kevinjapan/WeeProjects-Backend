<?php
include_once '../database/Database.php';
include_once '../app/Response.php';
include_once '../app/BearerToken.php';
include_once '../Controllers/AuthenticationController.php';
include_once '../models/Project.php';
include_once '../models/Task.php';


class ProjectController {

   private $database;
   private $db;

   public function __construct() {
      $this->database = Database::get_instance();
      $this->db = $this->database->get_connection();
   }


   //
   // We split end-points on returning only non 'soft deleted'.
   // Since we use soft deletes, client has to be able to assume that read() returns
   // all *valid* projects - that is non '(soft)-deleted' projects.
   // We offer an alternative for client admin ProjectsManager - read_inclusive() - 
   // which returns all projects including those 'soft-deleted'. 
   //
   // '' excluded - leave 404 for server resources - not for artefacts found or not.
   //

   
   //
   // Read All
   //
   public function read() {

      $token = BearerToken::get_bearer_token();
      
      if(!$token) {
         // to do : either make message enum equivalent for all client methods or make a separate utility function here for exiting w/ msg.
         //          eg we don't want to have to duplicate this msg again and again..
         Response::send('projects',false,'There was a problem accessing projects. You may not be authorised for this resource. Try to login again or contact admin.');
         exit;
      }

      $project = new Project($this->db);

      if($project) {
         Response::send('projects',true,'Projects.',$project->read($token->payload('user_id')));
      }
      else {
         Response::send('projects',false,'Projects not found.');
      }
   }


      
   // 
   // Read all - including 'soft deleted'
   //
   public function read_inclusive() {

      $token = BearerToken::get_bearer_token();
      if(!$token) {
         Response::send('projects',false,'There was a problem accessing projects. You may not be authorised for this resource. Try to login again or contact admin.');
         exit;
      }

      $project = new Project($this->db);

      if($project) {
         Response::send('projects_inclusive',true,'projects_inclusive',$project->read($token->payload('user_id'),true));
      }
      else {
         Response::send('projects',false,'Projects not found.');
      }
   }


   //
   // Read Single
   //
   public function read_single($route_parameters) {

      $token = BearerToken::get_bearer_token(); 
      if(!$token) {
         Response::send('projects',false,'There was a problem accessing projects. You may not be authorised for this resource. Try to login again or contact admin.');
         exit;
      }

      $project = new Project($this->db);
      $project->read_single($token->payload('user_id'),$route_parameters['project']['col'],$route_parameters['project']['value']);

      $tasks = $project->tasks();
      $modified_tasks = [];

      // Build each Task with associated Todos/Sessions/Comments
      //
      foreach($tasks as $task) {
         $task_obj = new Task($this->db);
         $task_obj->load($project->id,'id',$task['id']);
         $task['todos'] = $task_obj->todos();
         $task['sessions'] = $task_obj->sessions();
         // $task['comments'] = $task_obj->comments();
         array_push($modified_tasks,$task);
      }

      $hydrated_project = array(
         'id' => $project->id,
         'title' => $project->title,
         'slug' => $project->slug,
         'created_at' => $project->created_at,
         'updated_at' => $project->updated_at,
         'author_id' => $project->author_id,
         'tasks' => $modified_tasks,
         'users' => $project->users()
      );

      if($project->id) {
         Response::send('project',true,'project',$hydrated_project);
      }
      else {
         // The request for a project failed - this includes attempts to read unauthorized resources - 
         // project may exist, but there be no register in 'project_user'
         Response::send('project',false,'Project not found.');
      }
   }


   //
   // Create Single
   //
   public function create() {

      $token = BearerToken::get_bearer_token();
      if(!$token) {
         Response::send('projects',false,'There was a problem accessing projects. You may not be authorised for this resource. Try to login again or contact admin.');
         exit;
      }

      $project = new Project($this->db);
      $data = (array) json_decode(file_get_contents("php://input"),true);  // cast to array forces any rcvd null into an empty array

      $project->title = $data['title'];
      $project->slug = $data['slug']; 

      // create and get id of new Project
      $new_project_id = $project->create($token->user_id);
      $project->read_single($token->user_id,'id',$new_project_id);
      
      if($new_project_id) {
         Response::send('create_project',true,'Project created.',$project);
      } else {
         Response::send('create_project',false,'Project not created.');
      }
   }



   //
   // Update
   //
   public function update() {
      
      $token = BearerToken::get_bearer_token();
      if(!$token) {
         Response::send('projects',false,'There was a problem accessing projects. You may not be authorised for this resource. Try to login again or contact admin.');
         exit;
      }

      $project = new Project($this->db);
      $data = json_decode(file_get_contents("php://input"));

      if(!$data->id || !$data->title || !$data->slug) {
         Response::send('update_task',false,'Task not updated.');
         exit;
      }

      $project->id = $data->id;
      $project->title = $data->title;
      $project->slug = $data->slug;

      if($project->update()) {
         Response::send('update_project',true,'Project updated.');
      } else {
         Response::send('update_project',false,'Project not updated.');
      }
   }

   
   // 
   // Delete
   // We perform 'soft deletes' on UI delete requests.
   // Currently, for 'soft deletes' we do not touch child artefacts
   // - we assume they are un-accessible since parent is 'soft deleted'.
   // Any possible issue with proliferation will be resolved by cascading 
   // deletes on delete_permanently() since they retain relationship.
   //
   public function delete() {
      
      $token = BearerToken::get_bearer_token();
      if(!$token) {
         Response::send('projects',false,'There was a problem accessing projects. You may not be authorised for this resource. Try to login again or contact admin.');
         exit;
      }

      $project = new Project($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $project->id = $data->id;
      $project->deleted_at = $data->deleted_at;

      if($project->delete()) {
         Response::send('delete_project',true,'Project deleted.');
      }
      else {
         Response::send('delete_project',false,'Project not deleted.');
      }
   }

   public function delete_permanently() {
      
      $token = BearerToken::get_bearer_token();
      if(!$token) {
         Response::send('projects',false,'There was a problem accessing projects. You may not be authorised for this resource. Try to login again or contact admin.');
         exit;
      }

      $project = new Project($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $project->id = $data->id;

      if($project->delete_permanently()) {
         Response::send('delete_project_permanently',true,'Project permanently deleted.');
      }
      else {
         Response::send('delete_project_permanently',false,'Project not permanently deleted.');
      }
   }

}