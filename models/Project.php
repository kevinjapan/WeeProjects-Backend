<?php
include_once '../app/Model.php';
include_once '../app/DateTimeUtility.php';
include_once 'Comment.php';
include_once 'Message.php';
include_once 'ProjectUser.php';



class Project extends Model {

   private $connection;
   private $table = 'projects';

   public $id;
   public $title;
   public $slug;
   public $author_id;
   public $created_at;
   public $updated_at;
   public $deleted_at;


   public function __construct($db) {

      $this->connection = $db;

      // cols permitted in building our WHERE clauses
      $this->set_permitted_search_cols(['id','slug']);
   }
 

   //
   // We split end-points on returning only non 'soft deleted'.
   // Client assumes that read() returns all *valid* projects - that is non deleted projects.
   // We offer an alternative for client admin ProjectsManager - toggled on argument 
   // 'inc_soft_deleted' - which returns all projects including those 'soft-deleted'. 
   //
   

   //
   // Read all
   // all non 'soft-deleted' projects
   // only Projects registered to the user in 'project_user' are returned
   // 
   // Currently provides list of projects the requesting user is registered for.
   // OK since our single 'admin' role will be registered for all projects.
   //
   public function read($user_id,$inc_soft_deleted = false) {

      if(!is_int($user_id)) return null;

      $where_clause = '';
      // include soft_delete?
      $where_clause = $inc_soft_deleted ? '' : ' WHERE p.deleted_at IS NULL ';
      $insert = $where_clause === '' ? ' WHERE ' : ' AND ';
      // constrain to 'project_user' registered projects
      $where_clause.= $insert . ' p.id = pu.project_id AND pu.user_id = :user_id ';

      $query = 
      'SELECT DISTINCT
         p.id,
         p.title,
         p.slug,
         p.author_id,
         p.created_at,
         p.updated_at,
         p.deleted_at
      FROM
         ' . $this->table . ' p, project_user pu
      '. $where_clause . '  
      ORDER BY
         p.created_at DESC';
               
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':user_id',$user_id,PDO::PARAM_INT);

      $data = [];
      $stmt->execute();
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $data[] = $row;
      }
      return $data;
   }

   
   
   //
   // Read Single
   // only Projects registered to the user in 'project_user' are returned
   // future : we are using project slug to identify - ok?
   //
   public function read_single($user_id,$project_col,$project_col_value) {

      if(!$this->is_permitted_search_col($project_col)) return null;

      if(!is_int($user_id)) return null;

      $where_clause = ' WHERE p.' . $project_col . ' '. ' = :project_col_value ';
      $where_clause.= ' AND  pu.user_id = :user_id ';

      $query = 'SELECT
           p.id,
           p.title,
           p.slug,
           p.author_id,
           p.created_at,
           p.updated_at,
           p.deleted_at
        FROM ' . $this->table . ' p
        LEFT JOIN
            project_user pu ON p.id = pu.project_id 
        '. $where_clause . ' 
        LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':project_col_value',$project_col_value,PDO::PARAM_STR);
      $stmt->bindValue(':user_id',$user_id,PDO::PARAM_INT);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->slug = $row['slug'];
         $this->title = $row['title'];
         $this->author_id = $row['author_id'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
      }
   }


   //
   // Create Single
   // we create a Project & register it for the current user in 'project_user' (no unassigned projects)
   //
   public function create($user_id) {

      if(!is_int($user_id)) return null;

      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));

      $query = 'INSERT INTO ' . $this->table . ' 
               SET 
                  title = :title,
                  slug = :slug,
                  author_id = :user_id';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':title',$this->title,PDO::PARAM_STR);
      $stmt->bindValue(':slug',$this->slug,PDO::PARAM_STR);
      $stmt->bindValue(':user_id',$user_id,PDO::PARAM_INT);

      $stmt->execute();
      $new_project_id = $this->connection->lastInsertId();

      $project_user = new ProjectUser($this->connection);
      $project_user->register_project_user($new_project_id,$user_id);

      return $new_project_id;
   } 



   

   //
   // Update
   //
   public function update() {

      $query = 'UPDATE ' . $this->table . '
                  SET 
                     title = :title,
                     slug = :slug, 
                     updated_at = CURRENT_TIMESTAMP
                  WHERE 
                     id = :id';

      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);
      $stmt->bindValue(':title',$this->title,PDO::PARAM_STR);
      $stmt->bindValue(':slug',$this->slug,PDO::PARAM_STR);

      return $stmt->execute();
   }


   // 
   // Delete
   // We 'soft delete' primary artefacts - populating 'deleted_at' field in db table.
   // All child artefacts of 'soft deleted' parents are left untouched until we 'permanently delete' 
   // a primary artefact - we assume child artefacts of 'soft-deleted' parents are always un-accessible.
   //




   //
   // Soft Delete and no cascade
   // to comply w/ 'created_at' etc, client provides our 'deleted_at' datetimestamp
   // 
   public function delete() {

      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->deleted_at = htmlspecialchars(strip_tags($this->deleted_at));
      
      if (!DateTimeUtility::is_valid_date($this->deleted_at)) return false;  // eg '2023-09-15 11:33:20'

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

      // delete children

         $comment = new Comment($this->connection);
         $comment->delete_comments_permanently($this->id,'project');
         
         $task = new Task($this->connection);
         $task->delete_tasks_permanently($this->id);

         $message = new Message($this->connection);
         $message->delete_messages_permanently($this->id);

      // delete Project

         $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
         $this->id = htmlspecialchars(strip_tags($this->id));
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);
         return $stmt->execute();

   }


   //
   // Load
   //
   public function load($col,$value) {

      $query = 
               'SELECT 
                  p.id,
                  p.title,
                  p.slug,
                  p.author_id,
                  p.created_at
               FROM ' . $this->table . ' p
               WHERE
                  p.' . $col . ' '. ' = ?
               LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$value);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // future : any bool value - stored in db as 0 or 1
      // so we must cast to boolean if we want to appear as bool literal in json
      // eg $row['is_published'] = (bool) $row['is_published']

      if($row) {
         $this->id = $row['id'];
         $this->title = $row['title'];
         $this->slug = $row['slug'];
         $this->author_id = $row['author_id'];
         $this->created_at = $row['created_at'];
      }
   }


   //
   // All tasks
   //
   function tasks() {

      $query = 
         'SELECT DISTINCT 
               t.id,
               t.title,
               t.slug,
               t.outline,
               t.pin,
               t.project_id,
               t.author_id,
               t.created_at,
               t.updated_at
         FROM
               tasks t
         LEFT JOIN
               sessions s ON t.id = s.sessionable_id AND s.sessionable_type = "task" 
         WHERE
               project_id = ? AND deleted_at IS NULL 
         ORDER BY
            pin DESC, created_at DESC';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$this->id);
      $stmt->execute();
      $data = [];
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $data[] = $row;
      }
      return $data;
   }


   //
   // All comments
   //
   function comments() {

      $query = 
         'SELECT 
               id,
               title,
               slug,
               body,
               author_id,
               created_at,
               updated_at
         FROM
               comments
         WHERE
               commentable_type = "project" AND commentable_id = ?
         ORDER BY
               created_at ASC';

      $data = [];
      $stmt = $this->connection->prepare($query);

      $stmt->bindValue(1,$this->id);
      $stmt->execute();
      $data = [];
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $data[] = $row;
      }
      return $data;
   }


   //
   // All users
   //
   function users() {

      $query = 
         'SELECT 
            u.id,
            u.user_name
         FROM
            users u
         LEFT JOIN
            project_user pu ON pu.user_id = u.id
         WHERE
            pu.project_id = :project_id 
         ORDER BY
            u.user_name ASC';

      $data = [];
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':project_id',$this->id,PDO::PARAM_INT);

      $stmt->execute();
      $data = [];
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $data[] = $row;
      }
      return $data;
   }


}