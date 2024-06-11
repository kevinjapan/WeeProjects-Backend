<?php
include_once '../app/Model.php';
include_once 'Comment.php';
include_once 'Session.php';
include_once 'CheckListItem.php';


class Todo extends Model {

   private $connection;
   private $table = 'todos';

   // to do : should properties be public?! rollout.
   public $id;
   public $task_id;
   public $task_title;
   public $title;
   public $slug;
   public $outline;
   public $solution;
   public $pin;
   public $author_id;
   public $done_at;
   public $on_going;
   public $has_checklist;
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
   // Read All
   //
   public function read($inc_soft_deleted = false) {

      $where_clause = $inc_soft_deleted ? '' : ' WHERE t.deleted_at IS NULL';
      $insert = $where_clause === '' ? ' WHERE ' : ' AND ';
      $where_clause .= isset($this->task_id) ? $insert . 't.task_id = ? ' : '';

      $query = 
         'SELECT 
               tasks.title as task_title,
               t.id,
               t.task_id,
               t.title,
               t.slug,
               t.outline,
               t.solution,
               t.pin,
               t.author_id,
               t.done_at,
               t.on_going,
               t.has_checklist,
               t.created_at,
               t.updated_at,
               t.deleted_at
         FROM
               ' . $this->table . ' t
         LEFT JOIN
               tasks ON t.task_id = tasks.id
         '. $where_clause . '
         ORDER BY
               t.pin DESC, t.created_at DESC';

      $stmt = $this->connection->prepare($query);
      if(isset($this->task_id)) {
         $stmt->bindValue(1,$this->task_id);
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
   public function read_single($task_id,$todo_col,$todo_col_value) {

      if(!$this->is_permitted_search_col($todo_col)) return null;

      $query = 
               'SELECT 
                  tasks.title as task_title,
                  t.id,t.task_id,t.title,t.slug,
                  t.outline,t.solution,t.pin,t.author_id,
                  t.done_at,t.on_going,t.has_checklist,t.created_at,t.updated_at
               FROM ' . $this->table . ' t
               LEFT JOIN
                  tasks
               ON 
                  t.task_id = tasks.id
               WHERE
                  t.' . $todo_col . ' = ?
               AND
                  t.task_id  = ?
               LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$todo_col_value);
      $stmt->bindValue(2,$task_id);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->title = $row['title'];
         $this->slug = $row['slug'];
         $this->outline = $row['outline'];
         $this->solution = $row['solution'];
         $this->pin = $row['pin'];
         $this->author_id = $row['author_id'];
         $this->task_id = $row['task_id'];
         $this->task_title = $row['task_title'];
         $this->done_at = $row['done_at'];
         $this->on_going = $row['on_going'];
         $this->has_checklist = $row['has_checklist'];
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
                  solution = :solution,
                  pin = :pin,
                  author_id = :author_id, 
                  task_id = :task_id';
      
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->outline = htmlspecialchars(strip_tags($this->outline));
      $this->solution = htmlspecialchars(strip_tags($this->solution));
      $this->pin = htmlspecialchars(strip_tags($this->pin));
      $this->author_id = htmlspecialchars(strip_tags($this->author_id));
      $this->task_id = htmlspecialchars(strip_tags($this->task_id));
   
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':title',$this->title);
      $stmt->bindValue(':slug',$this->slug);
      $stmt->bindValue(':outline',$this->outline);
      $stmt->bindValue(':solution',$this->solution);
      $stmt->bindValue(':pin',$this->pin);
      $stmt->bindValue(':author_id',$this->author_id);
      $stmt->bindValue(':task_id',$this->task_id);
   
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
                     solution = :solution,
                     pin = :pin,
                     author_id = :author_id, 
                     task_id = :task_id,
                     done_at = :done_at,
                     on_going = :on_going,
                     has_checklist = :has_checklist,
                     updated_at = CURRENT_TIMESTAMP
                  WHERE 
                     id = :id';

      $this->task_id = htmlspecialchars(strip_tags($this->task_id));
      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->outline = htmlspecialchars(strip_tags($this->outline));
      $this->solution = htmlspecialchars(strip_tags($this->solution));
      $this->pin = htmlspecialchars(strip_tags($this->pin));
      $this->done_at = htmlspecialchars(strip_tags($this->done_at));
      $this->on_going = htmlspecialchars(strip_tags($this->on_going));
      $this->has_checklist = htmlspecialchars(strip_tags($this->has_checklist));
      $this->author_id = htmlspecialchars(strip_tags($this->author_id));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':title',$this->title);
      $stmt->bindValue(':slug',$this->slug);
      $stmt->bindValue(':outline',$this->outline);
      $stmt->bindValue(':solution',$this->solution);
      $stmt->bindValue(':pin',$this->pin);
      $stmt->bindValue(':author_id',$this->author_id);
      $stmt->bindValue(':done_at',$this->done_at === "" ? NULL : $this->done_at);
      $stmt->bindValue(':on_going',$this->on_going);
      $stmt->bindValue(':has_checklist',$this->has_checklist);
      $stmt->bindValue(':task_id',$this->task_id);
      $stmt->bindValue(':id',$this->id);

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
   //
   public function delete_permanently() {

      // delete children
      
         $comment = new Comment($this->connection);
         $comment->delete_comments_permanently($this->id,'todo');  

         $checklistitem = new CheckListItem($this->connection);
         $checklistitem->delete_checklistitems_permanently($this->id);

      // delete Todo

         $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
         $this->id = htmlspecialchars(strip_tags($this->id));
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':id',$this->id);
         return $stmt->execute();
   }


   //
   // Delete all for given parent Task
   // future : if any step fails - notify? log?
   //
   public function delete_todos_permanently($task_id) {

      $todo_id_csv = $this->get_todo_ids_csv($task_id);

      // delete children of the Todos
      $comment = new Comment($this->connection);
      $comment->delete_comments_permanently($todo_id_csv,'todo');
      $checklistitem = new CheckListItem($this->connection);
      $checklistitem->delete_checklistitems_permanently($todo_id_csv);

      // delete Todos
      $query = 'DELETE FROM ' . $this->table . ' WHERE task_id = :task_id ';
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':task_id',$task_id,PDO::PARAM_INT);
      return $stmt->execute();

   }


   private function get_todo_ids_csv($task_id) {

         // get array of Todo ids
         $data = [];
         $query = 'SELECT t.id FROM ' . $this->table . ' t WHERE t.task_id = :task_id';
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':task_id',$task_id);
         $stmt->execute();
         while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
         }

         // build csv of Todos
         $todo_id_csv = "";
         foreach($data as $todo_ids) {
            $todo_id_csv.= $todo_ids['id'] . ",";
         }
         return rtrim($todo_id_csv,",");
   }


   // 
   // Load
   // since Todo slugs are not unique, we qualify by $task_id
   //
   public function load($task_id,$todo_col,$todo_col_value) {
      $query = 
               'SELECT 
                  t.id,
                  t.title,
                  t.slug,
                  t.outline,
                  t.solution,
                  t.pin,
                  t.done_at,
                  t.on_going,
                  t.has_checklist,
                  t.created_at
               FROM ' . $this->table . ' t
               WHERE
                  t.' . $todo_col . ' '. ' = ?
               AND
                  t.task_id = ?
               LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$todo_col_value);
      $stmt->bindValue(2,$task_id);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->title = $row['title'];
         $this->slug = $row['slug'];
         $this->pin = $row['pin'];
         $this->done_at = $row['done_at'];
         $this->on_going = $row['on_going'];
         $this->has_checklist = $row['has_checklist'];
         $this->created_at = $row['created_at'];
      }    
   }


   //
   // Parent task
   //
   function task() {

      $query = 
               'SELECT 
                  id,title,done_at,outline,solution,created_at,updated_at
               FROM tasks
               WHERE
                  id = ?
               ORDER BY created_at DESC
               LIMIT 0,1';
      $stmt = $this->connection->prepare($query);

      $stmt->bindValue(1,$this->task_id);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      echo($row);
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
               commentable_type = "todo" AND commentable_id = ?
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


}