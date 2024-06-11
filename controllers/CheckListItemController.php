<?php
include_once '../database/Database.php';
include_once '../app/Response.php';
include_once '../models/Project.php';
include_once '../models/Task.php';
include_once '../models/CheckListItem.php';



class CheckListItemController {

   private $database;
   private $db;

   public function __construct() {
      $this->database = Database::get_instance();
      $this->db = $this->database->get_connection();
   }


   public function read($route_params) {

      $checklistitem = new CheckListItem($this->db);

      $checklistitem->todo_id = $route_params['todo_id']['value'];

   
      if($checklistitem) {
         Response::send('checklistitems',true,'Checklistitems.',$checklistitem->read());
      }
      else {
         Response::send('checklistitems',false,'Checklistitems not found.');
      }
   }


   public function read_single($route_params) {

      $checklistitem = new CheckListItem($this->db);

      $checklistitem->read_single($checklistitem->todo_id,$route_params['todo']['col'],$route_params['todo']['value']);

      $send_package = array(
         'id' => $checklistitem->id,
         'todo_id' => $checklistitem->todo_id,
         'title' => $checklistitem->title,
         'slug' => $checklistitem->slug,
         'done_at' => $checklistitem->done_at,
         'created_at' => $checklistitem->created_at,
         'updated_at' => $checklistitem->updated_at,
         'author_id' => $checklistitem->author_id
      );

      if($checklistitem->id) {
         Response::send('checklistitem',true,'checklistitem.',$send_package);
      }
      else {
         Response::send('checklistitem',false,'CheckListItem not found.');
      }
   }


   public function create($route_params) {

      $checklistitem = new CheckListItem($this->db);
      $data = json_decode(file_get_contents("php://input"),true);
      
      $checklistitem->todo_id = $data['todo_id'];
      $checklistitem->title = $data['title'];
      $checklistitem->slug = $data['slug'];
      $checklistitem->author_id = $data['author_id'];

      if(!is_int($checklistitem->todo_id) || !is_int($checklistitem->author_id)) {
         Response::send('create_checklistitem',false,'CheckListItem not created.');
         exit;
      } 

      $project_id = 0;

      // resolve project from route param ( typically '{project:slug}' )
      if(isset($route_params['project'])) {
         $project = new Project($this->db);
         $project->load($route_params['project']['col'],$route_params['project']['value']);
         $project_id = $project->id;
      }

      // $todo = new Todo($this->db);
      // $todo->load($project_id,$route_params['todo']['col'],$route_params['todo']['value']);
      // $checklistitem->todo_id = $todo->id;

      // create and get id of new CheckListItem
      $new_checklistitem_id = $checklistitem->create();

      if($new_checklistitem_id) {
         Response::send('create_checklistitem',true,'CheckListItem created.',$checklistitem);
      } 
      else {
         Response::send('create_checklistitem',false,'CheckListItem not created.');
      }
   }


   public function update($route_params) {
        
      $checklistitem = new CheckListItem($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $checklistitem->id = $data->id;
      $checklistitem->title = $data->title;
      $checklistitem->slug = $data->slug;
      $checklistitem->author_id = $data->author_id;
      $checklistitem->todo_id = $data->todo_id;

      if(isset($data->done_at)) {$checklistitem->done_at = $data->done_at;}
      
      $project_id = 0;

      // resolve project from route param ( typically '{project:slug}' )
      // if(isset($route_params['project'])) {
      //    $project = new Project($this->db);
      //    $project->load($route_params['project']['col'],$route_params['project']['value']);
      //    $project_id = $project->id;
      // }  
      
      // we prev. resolved task from the URL - no longer possible, client must provide task id.
      // $task = new task($this->db);
      // $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
      // $checklistitem->todo_id = $task->id;

      if(isset($data->created_at)) $checklistitem->created_at = $data->created_at;
      if(isset($data->updated_at)) $checklistitem->updated_at = $data->updated_at;
      
      if($checklistitem->update()) {
         Response::send('update_checklistitem',true,'Checklistitem updated.');
      } else {
         Response::send('update_checklistitem',false,'CheckListItem not updated.');
      }
   }


   public function delete() {
      $checklistitem = new CheckListItem($this->db);
      $data = json_decode(file_get_contents("php://input"));
      $checklistitem->id = $data->id;

      if($checklistitem->delete()) {
         Response::send('delete_checklistitem',true,'Checklistitem deleted.');
      }
      else {
         Response::send('delete_checklistitem',false,'CheckListItem not deleted.');
      }
   }
}