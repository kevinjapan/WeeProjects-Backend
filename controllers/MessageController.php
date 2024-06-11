<?php
include_once '../database/Database.php';
include_once '../app/Response.php';
include_once '../models/Project.php';
include_once '../models/Task.php';
include_once '../models/Todo.php';
include_once '../models/Message.php';
include_once '../models/message.php';



class MessageController {

    private $database;
    private $db;

    public function __construct() {
      $this->database = Database::get_instance();
      $this->db = $this->database->get_connection();
    }


   //
   // Read all 'valid' messages (not including 'soft-deleted')
   // Controller interface has by-design a definate separation of function, though we are only changing a flag on underlying model method read().
   //
   public function read($route_params) {

      $message = new Message($this->db);
      $project_id = 0;
      $data = [];

      if($route_params) {

         if(isset($route_params['project'])) {
            $project = new Project($this->db);
            $project->load($route_params['project']['col'],$route_params['project']['value']);
            $project_id = $project->id;
            $data['message'] = $message->read($project_id);
         }
      } 
      else {
         // eg http://weeprojects/messages (generic)
         $data['message'] = $message->read();
      }

      $data['project_id'] = $project_id;
      
      if($message) {
         Response::send('messages',true,'Messages.',$data);
      }
      else {
         Response::send('messages',false,'Messages not found.');
      }
    }


    

   // 
   // Read all - including 'soft deleted'
   //
   public function read_inclusive($route_params) {


      $message = new Message($this->db);
      $project_id = 0;
      $data = [];

      if($route_params) {

         if(isset($route_params['project'])) {
            $project = new Project($this->db);
            $project->load($route_params['project']['col'],$route_params['project']['value']);
            $project_id = $project->id;
            $data['message'] = $message->read($project_id,true);
         }
      } else {
         // eg http://weeprojects/messages (generic)
         $data['message'] = $message->read(null,true);
      }
      
      $data['project_id'] = $project_id;
    
      if($message) {
         Response::send('messages_inclusive',true,'messages_inclusive',$data);
      }
      else {
         Response::send('messages_inclusive',false,'Messages not found.');
      }
   }


   //
   // Update
   //
   public function read_single($route_parameters) {

      $message = new Message($this->db);        
      $message->read_single($route_parameters['Message']['col'],$route_parameters['Message']['value']);

      $message_data = array(
         'id' => $message->id,
         'title' => $message->title,
         'slug' => $message->slug,
         'project_id' => $message->project_id,
         'body' => $message->body,
         'author_id' => $message->author_id,
         'created_at' => $message->created_at,
         'updated_at' => $message->updated_at
      );

      if($message->id) {
         Response::send('message',true,'message',$message_data);
      }
      else {
         Response::send('message',false,'Message not found.');
      }
    }


   //
   // Create single
   //
   public function create($route_params) {

      $message = new Message($this->db);
      $data = json_decode(file_get_contents("php://input"),true);

      $message->title = $data['title'];
      $message->slug = $data['slug'];
      $message->project_id = $data['project_id'];
      $message->body = $data['body'];
      $message->author_id = $data['author_id'];

      // create and get id of new Message
      $new_message_id = $message->create();

      if($new_message_id) {
         Response::send('create_message',true,'Message Created.',$message);
      }
      else {
         Response::send('create_message',false,'Message not created.');
      }
   }



   //
   // Update
   //
   public function update($route_params) {
      
      $message = new Message($this->db);
      $data = json_decode(file_get_contents("php://input"));

      if(!$data->id || !$data->title || !$data->slug || !$data->body) {
         Response::send('update_message',false,'Message not updated.');
         exit;
      }

      $message->id = $data->id;
      $message->title = $data->title;
      $message->slug = $data->slug;     
      $message->body = $data->body;
      
      if(isset($data->created_at)) $message->created_at = $data->created_at;
      if(isset($data->updated_at)) $message->updated_at = $data->update_at;
      
      if($message->update()) {
         Response::send('update_message',true,'Message updated.');
      } 
      else {
         Response::send('update_message',false,'Message not updated.');
      }
   }



   //
   // Delete
   //
   public function delete() {

      $message = new Message($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $message->id = $data->id;
      $message->deleted_at = $data->deleted_at;

      if($message->delete()) {
         Response::send('delete_message',true,'Message deleted.');
      }
      else {
         Response::send('delete_message',false,'Message not deleted.');
      }
   }

   //
   //
   //
   public function delete_permanently() {

      $message = new Message($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $message->id = $data->id;

      if($message->delete_permanently()) {
         Response::send('delete_message_permanently',true,'Message permanently deleted.');
      }
      else {
         Response::send('delete_message_permanently',false,'Message not permanently deleted.');
      }

   }



}