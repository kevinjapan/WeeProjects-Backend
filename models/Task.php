<?php
include_once '../app/Model.php';
include_once 'Todo.php';
include_once 'Comment.php';
include_once 'Session.php';



class Task extends Model {

   private $connection;
   private $table = 'tasks';

   public $id;
   public $project_id;
   public $project_title;
   public $title;
   public $slug;
   public $outline;
   public $pin;
   public $author_id;
   public $created_at;
   public $updated_at;
   public $deleted_at;


   public function __construct($db) {

      $this->connection = $db;

      // cols permitted in building our WHERE clauses
      $this->set_permitted_search_cols(['id','slug']);
   }





   // We split end-points on returning only non 'soft deleted'.
   // Client assumes that read() returns all *valid* projects - that is non deleted projects.
   // We offer an alternative for client admin ProjectsManager - toggled on 'inc_soft_deleted' 
   // which returns all projects including 'soft-deleted'. 

   //
   // Read all
   // all non 'soft-deleted' tasks
   //
   public function read($inc_soft_deleted = false) {

      $where_clause = $inc_soft_deleted ? '' : ' WHERE t.deleted_at IS NULL';
      $insert = $where_clause === '' ? ' WHERE ' : ' AND ';
      $where_clause .= isset($this->project_id) ? $insert . 't.project_id = ? ' : '';

      $query = 
         'SELECT 
               p.title as project_title,
               t.id,
               t.project_id,
               t.title,
               t.slug,
               t.outline,
               t.pin,
               t.author_id,
               t.created_at,
               t.updated_at,
               t.deleted_at
         FROM
               ' . $this->table . ' t
         LEFT JOIN
               projects p ON t.project_id = p.id
        '. $where_clause . '
         ORDER BY
               t.pin DESC, t.created_at DESC';


      $stmt = $this->connection->prepare($query);
      if(isset($this->project_id)) {
         $stmt->bindValue(1,$this->project_id);
      }               

      $data = [];
      $stmt->execute();
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $data[] = $row;
      }
      return $data;
   }


   //
   // Read Single
   //
   public function read_single($project_id,$task_col,$task_col_value) {

      if(!is_int($project_id)) return null;
      if(!$this->is_permitted_search_col($task_col)) return null;

      // future : validate $task_col_value (may be id or slug etc) - how do we validate? some constraints?      

      $query = 
         'SELECT 
            p.title as project_title,
            t.id,t.project_id,t.title,t.slug,
            t.outline,t.pin,t.author_id,
            t.created_at,t.updated_at
         FROM ' . $this->table . ' t
         LEFT JOIN
            projects p 
         ON 
            t.project_id = p.id
         WHERE
            t.' . $task_col . ' = :task_col_value
         AND
            p.id = :project_id
         LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':task_col_value',$task_col_value,PDO::PARAM_STR);
      $stmt->bindValue(':project_id',$project_id,PDO::PARAM_INT);

      

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->title = $row['title'];
         $this->slug = $row['slug'];
         $this->outline = $row['outline'];
         $this->pin = $row['pin'];
         $this->author_id = $row['author_id'];
         $this->project_id = $row['project_id'];
         $this->project_title = $row['project_title'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
      }
   }



   //
   // Create Single
   //
   public function create() {

      $query = 'INSERT INTO ' . $this->table . 
               ' SET 
                  title = :title, 
                  slug = :slug,
                  outline = :outline,
                  pin = :pin, 
                  author_id = :author_id, 
                  project_id = :project_id';
      
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->outline = htmlspecialchars(strip_tags($this->outline));
      $this->pin = htmlspecialchars(strip_tags($this->pin));
      $this->author_id = htmlspecialchars(strip_tags($this->author_id));
      $this->project_id = htmlspecialchars(strip_tags($this->project_id));
   
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':title',$this->title);
      $stmt->bindValue(':slug',$this->slug);
      $stmt->bindValue(':outline',$this->outline);
      $stmt->bindValue(':pin',$this->pin);
      $stmt->bindValue(':author_id',$this->author_id);
      $stmt->bindValue(':project_id',$this->project_id);
   
      $stmt->execute();
      return $this->connection->lastInsertId();
   } 
   


   //
   // Update
   //
   public function update() {

      $query = 'UPDATE ' . $this->table . '
                  SET 
                     title = :title, 
                     slug = :slug,
                     outline = :outline, 
                     pin = :pin,
                     author_id = :author_id, 
                     project_id = :project_id
                  WHERE 
                     id = :id';

      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->outline = htmlspecialchars(strip_tags($this->outline));
      $this->pin = htmlspecialchars(strip_tags($this->pin));
      $this->author_id = htmlspecialchars(strip_tags($this->author_id));
      $this->project_id = htmlspecialchars(strip_tags($this->project_id));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':id',$this->id);
      $stmt->bindValue(':title',$this->title);
      $stmt->bindValue(':slug',$this->slug);
      $stmt->bindValue(':outline',$this->outline);
      $stmt->bindValue(':pin',$this->pin);
      $stmt->bindValue(':author_id',$this->author_id);
      $stmt->bindValue(':project_id',$this->project_id);

      // return true or false 
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
   // 
   public function delete() {

      $query = 'UPDATE ' . $this->table . '
                  SET
                     deleted_at = :deleted_at
                  WHERE 
                     id = :id';

      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->deleted_at = htmlspecialchars(strip_tags($this->deleted_at));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);
      $stmt->bindValue(':deleted_at',$this->deleted_at,PDO::PARAM_STR);

      return $stmt->execute();
   }


   //
   // Permanent Delete and cascade
   // future : do we need any dependancy on cascading?
   //          if any steps fail, need try/catch at least for notifying?
   //
   public function delete_permanently() {

      // future : currently, deleting, eg a Project, only responds to client w/ "Project permanently deleted."
      //          do we want to log somewhere that all children assets have also been deleted.
      //          for a starter, at least "Project and all child artefacts have been permanently deleted."

      // delete children

         $comment = new Comment($this->connection);
         $comment->delete_comments_permanently($this->id,'task');

         $session = new Session($this->connection);
         $session->delete_sessions_permanently($this->id,'task');
         
         $todo = new Todo($this->connection);
         $todo->delete_todos_permanently($this->id);


      // delete Task

         $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
         $this->id = htmlspecialchars(strip_tags($this->id));
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':id',$this->id);
         return $stmt->execute();
   
   }


   //
   // Delete all for given parent Project
   // future : review and refactor this code appropriately
   //         if any step fails - how do we handle?
   //
   public function delete_tasks_permanently($project_id) {

      $task_id_csv = $this->get_task_ids_csv($project_id);

      // delete children of the Tasks
      if($task_id_csv !== '') {

         $comment = new Comment($this->connection);
         $comment->delete_comments_permanently($task_id_csv,'task');

         $session = new Session($this->connection);
         $session->delete_sessions_permanently($task_id_csv,'task');

         $todo = new Todo($this->connection);         
         foreach(explode(',',$task_id_csv) as $task_id) {
            $todo->delete_todos_permanently($task_id);
         }
      }

      // delete Tasks
      $query = 'DELETE FROM ' . $this->table . ' WHERE project_id = :project_id ';
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':project_id',$project_id,PDO::PARAM_INT);
      return $stmt->execute();

   }


   private function get_task_ids_csv($project_id) {

         // get array of Task ids
         $data = [];
         $query = 'SELECT t.id FROM ' . $this->table . ' t WHERE t.project_id = :project_id';
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':project_id',$project_id);
         $stmt->execute();
         while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
         }

         // build csv of Tasks
         $task_id_csv = "";
         foreach($data as $task_ids) {
            $task_id_csv.= $task_ids['id'] . ",";
         }
         return rtrim($task_id_csv,",");
   }

      



   // 
   // Load
   // since Task slugs are not unique, we qualify by $project_id
   //
   public function load($project_id,$task_col,$task_col_value) {
      $query = 
               'SELECT 
                  t.id,
                  t.title,
                  t.slug,
                  t.outline,
                  t.pin,
                  t.created_at
               FROM ' . $this->table . ' t
               WHERE
                  t.' . $task_col . ' '. ' = ?
               AND
                  t.project_id = ?
               LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$task_col_value);
      $stmt->bindValue(2,$project_id);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->title = $row['title'];
         $this->created_at = $row['created_at'];
      }    
   }


   //
   // Parent project
   //
   function project() {

      $query = 
               'SELECT 
                  id,title,slug,author_id,created_at,updated_at
               FROM 
                  projects
               WHERE
                  id = ?
               LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$this->project_id);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      echo($row);
   }


   //
   // All todos
   //
   function todos() {

      $query = 
         'SELECT 
               id,
               task_id,
               title,
               slug,
               outline,
               solution,
               pin,
               done_at,
               on_going,
               has_checklist,
               author_id,
               created_at
         FROM
               todos
         WHERE
               task_id = ? AND deleted_at IS NULL 
         ORDER BY
               pin DESC, created_at DESC';

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
   // All comments
   //
   function comments() {

      echo('*** IN TASK.comments ***');

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
               commentable_type = "task" AND commentable_id = ?
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
   // All sessions
   //
   function sessions() {

      $query = 
         'SELECT 
               id,
               author_id,
               started_at,
               ended_at
         FROM
               sessions
         WHERE
               sessionable_type = "task" AND sessionable_id = ?
         ORDER BY
               started_at DESC';

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


}