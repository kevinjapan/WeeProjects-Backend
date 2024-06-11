<?php
include_once '../app/Model.php';


class ProjectUser extends Model {

   private $connection;
   private $table = 'project_user';

   public $id;
   public $project_id;
   public $user_id;


   public function __construct($db) {

      $this->connection = $db;

      // cols permitted in building our WHERE clauses
      $this->set_permitted_search_cols(['id','project_id','user_id']);
   }


   //
   // Register a project for the user
   // 
   public function register_project_user($project_id,$user_id) {

      $query = 'INSERT INTO ' . $this->table . 
               ' SET 
                  user_id = :user_id, 
                  project_id = :project_id ';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':user_id',$user_id,PDO::PARAM_INT);
      $stmt->bindValue(':project_id',$project_id,PDO::PARAM_INT);
      $stmt->execute();
      return $this->connection->lastInsertId();
   }

   // 
   // Register a list of projects w/ a single user
   // future : cf w/ User->add_projects - we are switching btwn csv and array (inconsistent)
   // future : w/ all registering - ensure we never create duplicates (or at least they are all removed on remove_projects)
   //
   public function register_projects($projects,$user_id) {

      if(!$projects || $projects === '') return false;

      $projects_array = explode(',',$projects);
      $values = '';
      $count = 1;

      // build values list
      foreach($projects_array as $project_id) {
         $project_id = ':project_id_' . $count;
         $values.= "($user_id," . $project_id . "),";
         $count++;
      }
      $values = trim($values,',');

      $query = 'INSERT INTO ' . $this->table . '
                  (user_id,project_id) 
                VALUES
                  ' . $values . '';
      $stmt = $this->connection->prepare($query);

      // bind value for each project_id
      $count = 1;
      foreach($projects_array as $project) {
         $project_id = 'project_id_' . $count;
         $stmt->bindValue($project_id,$project);
         $count++;
      }
      return $stmt->execute();
   }


}