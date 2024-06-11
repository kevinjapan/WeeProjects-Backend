<?php
include_once '../database/Database.php';
include_once '../models/Project.php';
include_once '../models/Task.php';
include_once '../models/Todo.php';
include_once '../models/Session.php';



class SessionController {

    private $database;
    private $db;

    public function __construct() {
      $this->database = Database::get_instance();
      $this->db = $this->database->get_connection();
    }


   public function read($route_params) {

      $session = new Session($this->db);
      $data = [];

      if($route_params) {
         // eg http://weeprojects/sessions/todo/404  ../{sessionable_type}/{sessionable_id} (specific)
         if(isset($route_params['sessionable_type']) && isset($route_params['sessionable_id'])) {
            $sessionable_type = $route_params['sessionable_type']['value'];
            $sessionable_id = $route_params['sessionable_id']['value'];
            $data = $session->read($sessionable_type,$sessionable_id);
         }
      } else {
         // eg http://weeprojects/sessions (generic)
         $data = $session->read();
      }
    
      if($session) {
         echo json_encode(
            array(
               'query_key' => 'sessions',
               'outcome' => 'success',
               'data' => $data,
            )
         );
      }
      else {
         
         echo json_encode([
            'query_key' => 'sessions',
            'outcome' => 'fail',
            'message' => 'Sessions not found'
         ]);
      }
    }


   public function read_single($route_parameters) {

      $session = new Session($this->db);        
      $session->read_single($route_parameters['session']['col'],$route_parameters['session']['value']);

      $session_data = array(
         'id' => $session->id,
         'sessionable_type' => $session->sessionable_type,
         'sessionable_id' => $session->sessionable_id,
         'author_id' => $session->author_id,
         'started_at' => $session->started_at,
         'ended_at' => $session->ended_at
      );

      if($session->id) {
          echo json_encode(
            array(
               'query_key' => 'session',
               'outcome' => 'success',
               'data' => $session_data
            )
          );
      }
      else {
         
         echo json_encode([
            'query_key' => 'session',
            'outcome' => 'fail',
            'message' => 'Session not found'
         ]);
      }
    }



   public function create($route_params) {

      $session = new Session($this->db);
      $data = json_decode(file_get_contents("php://input"),true);

      $session->started_at = $data['started_at'];               // user may retrospectively add a session
      $session->sessionable_type = $data['sessionable_type'];
      $session->sessionable_id = $data['sessionable_id'];
      $session->author_id = $data['author_id'];

      // $session_id = 0;

      // // resolve project from route param ( typically '{project:slug}' )
      // if(isset($route_params['project'])) {
      //    $project = new Project($this->db);
      //    $project->load($route_params['project']['col'],$route_params['project']['value']);
      //    $session_id = $project->id;
      // }

      // $task = new task($this->db);
      // $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
      // $todo->task_id = $task->id;

      // create and get id of new Session
      $new_session_id = $session->create();

      if($new_session_id) {
         http_response_code(201);
         echo json_encode(
            array(
               'query_key' => 'create_session',
               'outcome' => 'success',
               'message' => 'Session Created',
               'id' => $new_session_id
            )
         );
      } else {
         echo json_encode(
            array(
               'query_key' => 'create_session',
               'outcome' => 'fail',
               'message'=>'Session Not Created'
            )
         );
      }
   }


   public function update($route_params) {
      
      $session = new Session($this->db);
      $data = json_decode(file_get_contents("php://input"));

      if(!$data->id) {
         Response::send('update_task',false,'Task not updated.');
         exit;
      }
      $session->id = $data->id;
      
      if(isset($data->started_at)) $session->started_at = $data->started_at;
      if(isset($data->ended_at)) $session->ended_at = $data->ended_at;
      if(isset($data->author_id)) $session->author_id = $data->author_id;

      if($session->update()) {
         echo json_encode(
            array(
               'query_key' => 'update_session',
               'outcome' => 'success',
               'message'=> 'Session updated'
            )
         );
      } else {
         echo json_encode (
            array(
               'query_key' => 'update_session',
               'outcome' => 'fail',
               'message' => 'Session not updated'
            )
         );
      }
   }


   public function delete() {

      $session = new Session($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $session->id = $data->id;

      if($session->delete()) {
         echo json_encode(
            array(
               'query_key' => 'delete_session',
               'outcome' => 'success',
               'message' => 'Session deleted.'
            )
         );
      }
      else {
         echo json_encode(
            array(
               'query_key' => 'delete_session',
               'outcome' => 'fail',
               'message' => 'Session not deleted.'
            )
         );
      }
   }
}