<?php
include_once '../database/Database.php';
include_once '../app/Response.php';
include_once '../models/Project.php';
include_once '../models/Task.php';
include_once '../models/Todo.php';
include_once '../models/Comment.php';



class CommentController {

    private $database;
    private $db;

    public function __construct() {
      
      // ----------------------------------------------------------------------------------
      // to do :
      // CAPTURE THIS IN WEEPROJECTS AS SOLUTION TO BUG.
      // bug - sometimes doesn't access database when retrieving comments - 
      // likely since we are re-initialising a new database here...
      //
      // bug - 
      // we fire up Database for AuthenticationController - but then discard this and
      // attempt to re-create from new here (or any other controller)
      // sometimes it doesn't work
      // main issue is - we always get Database connection successfully with
      // AuthenticationController - why throw it away.
      //
      // solution - 
      // change Database into a singleton and use it in all Controllers.
      //
      // replication -
      // front-end: in a project page, change the tasks
      // check the network tab - every c.7th or so change fails to access database
      // to retrieve comments list.
      // error : SQLSTATE[HY000] [1045] SQL Access denied for user
      // ----------------------------------------------------------------------------------



        $this->database = Database::get_instance();
        $this->db = $this->database->get_connection();
    }


   public function read($route_params) {


      $comment = new Comment($this->db);
      $data = [];

      if($route_params) {
         // eg http://weeprojects/comments/todo/404  ../{commentable_type}/{commentable_id} (specific)
         if(isset($route_params['commentable_type']) && isset($route_params['commentable_id'])) {
            $commentable_type = $route_params['commentable_type']['value'];
            $commentable_id = $route_params['commentable_id']['value'];
            $data = $comment->read($commentable_type,$commentable_id);
         }
      } else {
         // eg http://weeprojects/comments (generic)
         $data = $comment->read();
      }
    
      if($comment) {
         Response::send('comments',true,'Comments.',$data);

      }
      else {
         Response::send('comments',false,'Comments not found.');
      }
    }


   public function read_single($route_parameters) {

      $comment = new Comment($this->db);        
      $comment->read_single($route_parameters['comment']['col'],$route_parameters['comment']['value']);

      $comment_data = array(
         'id' => $comment->id,
         'title' => $comment->title,
         'slug' => $comment->slug,
         'commentable_type' => $comment->commentable_type,
         'commentable_id' => $comment->commentable_id,
         'body' => $comment->body,
         'author_id' => $comment->author_id,
         'created_at' => $comment->created_at,
         'updated_at' => $comment->updated_at
      );

      if($comment->id) {
         Response::send('comment',true,'Comment.',$comment_data);
      }
      else {
         Response::send('comment',false,'Comment not found.');
      }
    }

   public function create($route_params) {

      $comment = new Comment($this->db);
      $data = json_decode(file_get_contents("php://input"),true);

      $comment->title = $data['title'];
      $comment->slug = $data['slug'];
      $comment->commentable_type = $data['commentable_type'];
      $comment->commentable_id = $data['commentable_id'];
      $comment->body = $data['body'];
      $comment->author_id = $data['author_id'];

      // $comment_id = 0;

      // // resolve project from route param ( typically '{project:slug}' )
      // if(isset($route_params['project'])) {
      //    $project = new Project($this->db);
      //    $project->load($route_params['project']['col'],$route_params['project']['value']);
      //    $comment_id = $project->id;
      // }

      // $task = new task($this->db);
      // $task->load($project_id,$route_params['task']['col'],$route_params['task']['value']);
      // $todo->task_id = $task->id;

      // create and get id of new Comment
      $new_comment_id = $comment->create();

      if($new_comment_id) {
         Response::send('create_comment',true,'Comment created.',$comment);
      } else {
         Response::send('create_comment',false,'Comment not created.');
      }
   }


   public function update($route_params) {
      
      $comment = new Comment($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $comment->id = $data->id;
      $comment->title = $data->title;
      $comment->slug = $data->slug;     
      $comment->body = $data->body;
      
      if(isset($data->created_at)) $comment->created_at = $data->created_at;
      if(isset($data->updated_at)) $comment->updated_at = $data->update_at;
      
      if($comment->update()) {
         Response::send('update_comment',true,'Comment updated.');
      } else {
         Response::send('update_comment',false,'Comment not updated.');
      }
   }


   public function delete() {

      $comment = new Comment($this->db);
      $data = json_decode(file_get_contents("php://input"));

      $comment->id = $data->id;

      if($comment->delete()) {
         Response::send('delete_comment',true,'Comment deleted.');
      }
      else {
         Response::send('delete_comment',false,'Comment not deleted.');
      }
   }
}