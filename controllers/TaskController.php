<?php
include_once '../database/Database.php';
include_once '../app/Response.php';
include_once '../Controllers/AuthenticationController.php';
include_once '../models/Project.php';
include_once '../models/Task.php';



class TaskController {

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
   // Read all 'valid' tasks (not including 'soft-deleted')
   // Controller interface has by-design a definate separation of function, though we are only changing a flag on underlying model method read().
   // future : constrain to 'project_user' registered projects - rollout to TodoController
   //
   public function read($route_params) {

      $task = new Task($this->db);


      // resolve project_id from route_param 'project:slug'
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         $task->project_id = $project->id;
      }

      // get array of tasks for the selected project
      $tasks = $task->read();
      $num_tasks = count($tasks);
      $modified_tasks = [];

      // load tasks with their todos and sessions
      foreach($tasks as $task) {
         $task_obj = new Task($this->db);
         $task_obj->load($task['project_id'],'id',$task['id']);
         $task['todos'] = $task_obj->todos();
         $task['sessions'] = $task_obj->sessions();
         array_push($modified_tasks,$task);
      }
      
     // response
      if($task) {
         Response::send('tasks',true,'Tasks.',$modified_tasks);
      }
      else {
         Response::send('tasks',false,'Tasks not found.');
      }
   }


   // 
   // Read all - including 'soft deleted'
   //
   public function read_inclusive($route_params) {

      $task = new Task($this->db);

      // resolve project_id from route_param 'project:slug'
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         $task->project_id = $project->id;
      }

      // get array of tasks for the selected project
      $tasks = $task->read($route_params['project'],true);
      $modified_tasks = [];

      // load tasks with their todos and sessions
      foreach($tasks as $task) {
         $task_obj = new Task($this->db);
         $task_obj->load($task['project_id'],'id',$task['id']);
         $task['todos'] = $task_obj->todos();
         $task['sessions'] = $task_obj->sessions();
         array_push($modified_tasks,$task);
      }
      
     // response
      if($task) {
         Response::send('tasks',true,'Tasks.',$modified_tasks);
      }
      else {
         Response::send('tasks',false,'Tasks not found.');
      }
   }


   //
   // Read Single
   //
   public function read_single($route_params) {

      // since we typically use '{slug}' - which is not unique - we must also qualify by '{project}' route param
      $task = new Task($this->db);
      $project_id = 0;

      // resolve project from route param ( typically '{project:slug}' )
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         $project_id = $project->id;
      }

      $task->read_single($project_id,$route_params['task']['col'],$route_params['task']['value']);

      $hydrated_task = array(
         'id' => $task->id,
         'title' => $task->title,
         'slug' => $task->slug,
         'created_at' => $task->created_at,
         'updated_at' => $task->updated_at,
         'outline' => $task->outline,
         'pin' => $task->pin,
         'comments' => $task->comments(),
         'sessions' => $task->sessions(),
      );
      
      if($task->id) {
         Response::send('task',true,'task',$hydrated_task);
      }
      else {
         Response::send('task',false,'Task not found.');
      }
   }


   //
   // Create Single
   //
   public function create($route_params) {

      $task = new Task($this->db);
      $data = json_decode(file_get_contents("php://input"),true);
      
      $task->title = $data['title'];
      $task->slug = $data['slug'];
      $task->outline = $data['outline'];  
      if(isset($data['pin'])) $task->pin = $data['pin'];   

      $project = new Project($this->db);
      $project->load($route_params['project']['col'],$route_params['project']['value']);
      $task->project_id = $project->id;
      $new_task_id = $task->create();
      $task->id = $new_task_id;
      
      if($new_task_id) {
         http_response_code(201);
         Response::send('create_task',true,'Task created.',$task);
      } 
      else {
         Response::send('create_project',false,'Task not created.');
      }
   }



   //
   // Update
   //
   public function update($route_params) {
      
      $task = new Task($this->db);
      $data = json_decode(file_get_contents("php://input"));

      if(!$data->id || !$data->title || !$data->slug || !$data->outline) {
         Response::send('update_task',false,'Task not updated.');
         exit;
      }

      $task->id = $data->id;
      $task->title = $data->title;
      $task->slug = $data->slug;
      $task->outline = $data->outline;
      $task->pin = $data->pin;
      $task->author_id = $data->author_id;

      $project = new Project($this->db);
      $project->load($route_params['project']['col'],$route_params['project']['value']);
      $task->project_id = $project->id;


      if($task->update()) {
         Response::send('update_task',true,'Task updated.');
      } else {
         Response::send('update_task',false,'Task not updated.');
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

      $task = new Task($this->db);
      $data = json_decode(file_get_contents("php://input"));
      $task->id = $data->id;
      $task->deleted_at = $data->deleted_at;

      if($task->delete()) {
         Response::send('delete_task',true,'Task deleted.');
      } else {
         Response::send('delete_task',false,'Task not deleted.');
      }
   }
      
   public function delete_permanently() {

      $task = new Task($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $task->id = $data->id;

      if ($task->delete_permanently()) {
         Response::send('delete_task_permanently',true,'Task permanently deleted.');
      }
      else {
         Response::send('delete_task_permanently',false,'Task not permanently deleted.');
      }
   }


}