<?php
include_once '../app/Model.php';


class User extends Model {

   private $connection;
   private $table = 'users';

   public $id;
   public $user_name;
   public $email;
   public $projects;
   public $created_at;
   public $updated_at;
   public $deleted_at;

   // note : User has no 'password_hash' property for security.


   public function __construct($db) {

      $this->connection = $db;

      // cols permitted in building our WHERE clauses
      $this->set_permitted_search_cols(['id','slug']);
   }


   //
   // Read All
   // all non 'soft-deleted' todos
   //
   public function read($inc_soft_deleted = false) {

      $where_clause = $inc_soft_deleted ? '' : ' WHERE t.deleted_at IS NULL';

      $query = 
         'SELECT
               u.id,
               u.user_name,
               u.email,
               u.created_at,
               u.updated_at,
               u.deleted_at
         FROM
               ' . $this->table . ' u 
         '. $where_clause . '
         ORDER BY
               u.user_name DESC';

      $stmt = $this->connection->prepare($query);
      $data = [];
      $stmt->execute();
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $row['projects'] = $this->projects($row['id']);
         $data[] = $row;
      }

      return $data;

   }  


   //
   // Read Single
   // $user_col may be 'id' or 'user_name'
   //
   public function read_single($user_col,$user_col_value) {

      if(!$this->is_permitted_search_col($user_col)) return null;

      $query = 'SELECT 
                  u.id,
                  u.user_name,
                  u.email,
                  u.created_at,
                  u.updated_at,
                  u.deleted_at
               FROM ' . $this->table . ' u
               WHERE
                  u.' . $user_col . ' = ?
               LIMIT 0,1';
      $stmt = $this->connection->prepare($query);

      $stmt->bindValue(1,$user_col_value);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->user_name = $row['user_name'];
         $this->email = $row['email'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
         $this->deleted_at = $row['deleted_at'];
      }
   }



   //
   // Create Single
   //
   public function create($password_hash) {

      $query = 'INSERT INTO ' . $this->table . 
               ' SET
                  user_name = :user_name,
                  email = :email,
                  password_hash = :password_hash';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':user_name',$this->user_name);
      $stmt->bindValue(':email',$this->email);
      $stmt->bindValue(':password_hash',$password_hash);
      $stmt->execute();
      return $this->connection->lastInsertId();
   } 



   //
   // Update
   //
   public function update($password_hash = '') {
      
      // if Controller has created a password_hash, we trust it is valid and updated
      $password_change = '';
      if(!is_null($password_hash) && $password_hash !== '') {
         $password_change = 'password_hash = :password_hash,';
      }

      $query = 'UPDATE ' . $this->table . ' SET
                  user_name = :user_name,
                  email = :email,
                  ' . $password_change . '
                  updated_at = CURRENT_TIMESTAMP
               WHERE 
                  id = :id';

      $stmt = $this->connection->prepare($query);      
      $stmt->bindValue(':id',$this->id);
      $stmt->bindValue(':user_name',$this->user_name);
      $stmt->bindValue(':email',$this->email);
      if($password_change !== '') $stmt->bindValue(':password_hash',$password_hash);
      return $stmt->execute();
   }

   

   //
   // Soft Delete and no cascade
   // 
   public function delete() {

      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->deleted_at = htmlspecialchars(strip_tags($this->deleted_at));

      $query = 'UPDATE ' . $this->table . '
                  SET
                     deleted_at = :deleted_at
                  WHERE 
                     id = :id';
      $stmt = $this->connection->prepare($query);

      $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);      
      $stmt->bindValue(':deleted_at',$this->deleted_at,PDO::PARAM_STR);

      return $stmt->execute();
   }


   //
   // Permanent Delete and cascade
   //
   public function delete_permanently() {

         $this->id = htmlspecialchars(strip_tags($this->id));
         $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':id',$this->id);
         return $stmt->execute();
   }



   // 
   // Load
   //
   public function load($user_col,$user_col_value) {
      $query = 
               'SELECT
                  u.id,
                  u.user_name,
                  u.email,
                  u.created_at,
                  u.updated_at,
                  u.deleted_at
               FROM ' . $this->table . ' u
               WHERE
                  u.' . $user_col . ' '. ' = ?
               LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$user_col_value);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->user_name = $row['user_name'];
         $this->email = $row['email'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
         $this->deleted_at = $row['deleted_at'];
         $this->projects = $this->projects($row['id']);
      }    
   }


   //
   // All projects
   // return array of project ids this user belongs to.
   //
   public function projects($user_id) {

      $result = null;
      if(!isset($user_id)) $user_id = $this->id;

      $query = 
         'SELECT DISTINCT
            project_id as id
         FROM
            project_user
         WHERE
            user_id = ?
         ORDER BY
            project_id';

      $data = [];
      $stmt = $this->connection->prepare($query);

      $stmt->bindValue(1,$user_id);
      $stmt->execute();

      $data = [];
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) $data[] = implode(",",$row);
      if($data) $result = implode(',',$data);

      return $result;
   }

   
   //
   // Register a list of projects for the user
   //
   public function add_projects($additional_projects) {

      // future : ensure we never add duplicates.
      
      if(!$additional_projects || count($additional_projects) < 1) return false;

      // build value for each project..
      $projects = $additional_projects;
      $values = '';
      $count = 1;

      // build values list
      foreach($projects as $project) {
         $project_id = ':project_id_' . $count;
         $values.= "($this->id," . $project_id . "),";
         $count++;
      }
      $values = trim($values,',');

      $query = 'INSERT INTO project_user 
                  (user_id,project_id)
                VALUES
                  ' . $values . '';
      $stmt = $this->connection->prepare($query);

      // bind value for each project_id    
      $count = 1;
      foreach($projects as $project) {
         $project_id = 'project_id_' . $count;
         $stmt->bindValue($project_id,$project);
         $count++;
      }
      return $stmt->execute();
   }


   //
   // Remove a list of registered projects for the user
   //
   public function remove_projects($removed_projects) {

      if(!$removed_projects || count($removed_projects) < 1) {

         $projects_csv = implode(',',$removed_projects);
      
         if($projects_csv !== '') {
            $query = 
            'DELETE FROM project_user 
               WHERE 
                  user_id = :user_id 
               AND 
                  project_id IN (' . $projects_csv . ')';
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':user_id',$this->id);
            return $stmt->execute(); 
         }
      }
      return false;
   }


}