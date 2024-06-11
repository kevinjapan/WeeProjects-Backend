<?php
include_once '../database/Database.php';
include_once '../app/Response.php';
include_once '../Controllers/AuthenticationController.php';
include_once '../models/Project.php';
include_once '../models/Task.php';
include_once '../models/Todo.php';



class TodoController {

   private $database;
   private $db;

   public function __construct() {
      $this->database = Database::get_instance();
      $this->db = $this->database->get_connection();
   }


   // We split end-points on returning only non 'soft deleted'.
   // Since we use soft deletes, client has to be able to assume that read() returns
   // all *valid* projects - that is non deleted projects.
   // We offer an alternative for client admin ProjectsManager - read_inclusive() - 
   // which returns all projects including those 'soft-deleted'. 

   //
   // Read All
   //
   public function read($route_params) {

      $todo = new Todo($this->db);
      $project_id = 0;
      
      // resolve Project from route param ( typically '{project:slug}' )
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         $project_id = $project->id;
      }

      // resolve Task from route param
      if(isset($route_params['task'])) {
         $task = new Task($this->db);
         $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
         $todo->task_id = $task->id;
      }

      // response
      if($todo) {
         Response::send('todos',true,'todos.',$todo->read());
      }
      else {
         Response::send('todos',false,'Todos not found.');
      }
   }
   
   // 
   // Read all - including 'soft deleted'
   //
   public function read_inclusive($route_params) {

      $todo = new Todo($this->db);
      $project_id = 0;

      // resolve Project from route param ( typically '{project:slug}' )
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         $project_id = $project->id;
      }

      // resolve Task from route param
      if(isset($route_params['task'])) {
         $task = new Task($this->db);
         $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
         $todo->task_id = $task->id;
      }

      // response
      if($todo) {
         Response::send('todos',true,'todos.',$todo->read());
      }
      else {
         Response::send('todos',false,'Todos not found.');
      }
   }

   

   //
   // Read Single
   //
   public function read_single($route_params) {

      $todo = new Todo($this->db);
      $project_id = 0;

      // resolve project from route param ( typically '{project:slug}' )
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         $project_id = $project->id;
      }

      // resolve task from route param
      if(isset($route_params['task'])) {
         $task = new Task($this->db);
         $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
         $todo->task_id = $task->id;
      }

      $todo->read_single($todo->task_id,$route_params['todo']['col'],$route_params['todo']['value']);
      $comments = $todo->comments();

      $hydrated_todo = array(
         'id' => $todo->id,
         'title' => $todo->title,
         'slug' => $todo->slug,
         'outline' => $todo->outline,
         'solution' => $todo->solution,
         'pin' => $todo->pin,
         'done_at' => $todo->done_at,
         'on_going' => $todo->on_going,
         'created_at' => $todo->created_at,
         'updated_at' => $todo->updated_at,
         'author_id' => $todo->author_id,
         'task_id' => $todo->task_id,
         'comments' => $comments
      );

      if($todo->id) {
         Response::send('todo',true,'todo',$hydrated_todo);
      }
      else {
         Response::send('todo',false,'Todo not found.');
      }
   }


   //
   // Create Single
   //
   public function create($route_params) {

      $todo = new Todo($this->db);
      $data = json_decode(file_get_contents("php://input"),true);

      $todo->title = $data['title'];
      $todo->slug = $data['slug'];
      $todo->outline = isset($data['outline']) ? $data['outline'] : '';
      $todo->solution = isset($data['solution']) ? $data['solution'] : '';
      $todo->author_id = $data['author_id'];

      $project_id = 0;

      // resolve project from route param ( typically '{project:slug}' )
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         $project_id = $project->id;
      }

      $task = new task($this->db);
      $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
      $todo->task_id = $task->id;

      // create and get id of new Todo
      $new_todo_id = $todo->create();
      $todo->id = $new_todo_id;

      if($new_todo_id) {
         http_response_code(201);
         Response::send('create_todo',true,'Todo created.',$todo);
      } 
      else {
         Response::send('create_todo',false,'Todo not created.');
      }
   }



   //
   // Update
   //
   public function update($route_params) {

      $todo = new Todo($this->db);
      $data = json_decode(file_get_contents("php://input"));

      if(!$data->id || !$data->title || !$data->slug || !$data->outline || !$data->task_id) {
         Response::send('update_todo',false,'Todo not updated.');

         // to do : cf ::send(..false..) below
         // we have different causes here - without overloading user w/ unnecessary info,
         // can we somehow capture the different reasons (for future support/debugging)?
         exit;
      }

      $todo->id = $data->id;
      $todo->title = $data->title;
      $todo->slug = $data->slug;
      $todo->outline = $data->outline;
      $todo->solution = $data->solution;
      $todo->author_id = $data->author_id;
      $todo->pin = $data->pin;
      $todo->task_id = $data->task_id;

      if(isset($data->done_at)) {$todo->done_at = $data->done_at;}
      $todo->on_going = empty($data->on_going) ? false : $data->on_going;
      $todo->has_checklist = empty($data->has_checklist) ? false : $data->has_checklist;
      
      // $project_id = 0;

      // resolve project from route param ( typically '{project:slug}' )
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         //$project_id = $project->id;
      }  

      if(isset($data->created_at)) $todo->created_at = $data->created_at;
      if(isset($data->updated_at)) $todo->updated_at = $data->update_at;
      
      if($todo->update()) {
         Response::send('update_todo',true,'Todo updated.');
      } else {
         Response::send('update_todo',false,'Todo not updated.');
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

      $todo = new Todo($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $todo->id = $data->id;
      $todo->deleted_at = $data->deleted_at;

      if($todo->delete()) {
         Response::send('delete_todo',true,'Todo deleted.');
      } else {
         Response::send('delete_todo',false,'Todo not deleted.');
      }
   }
      
   public function delete_permanently() {

      $todo = new Todo($this->db);
      $data = json_decode(file_get_contents("php://input"));
      $todo->id = $data->id;

      if($todo->delete_permanently()) {
         Response::send('delete_todo_permanently',true,'Todo permanently deleted.');
      }
      else {
         Response::send('delete_todo_permanently',false,'Todo not permanently deleted.');
      }
   }
}