<?php
include_once '../app/Model.php';


class CheckListItem extends Model {

   private $connection;
   private $table = 'checklistitems';

   public $id;
   public $todo_id;
   public $task_title;
   public $title;
   public $slug;
   public $author_id;
   public $done_at;
   public $created_at;
   public $updated_at;

   public function __construct($db) {

      $this->connection = $db;

      // cols permitted in building our WHERE clauses
      $this->set_permitted_search_cols(['id']);
   }

 

   //
   // Read all
   // currently - we never actually call eg read() on CheckListItems.
   //
   public function read() {

      $where_clause = isset($this->todo_id) ? 'WHERE c.todo_id = ? ' : '';

      $query = 
         'SELECT 
               todos.title as todo_title,
               c.id,
               c.todo_id,
               c.title,
               c.slug,
               c.author_id,
               c.done_at,
               c.created_at,
               c.updated_at
         FROM
               ' . $this->table . ' c
         LEFT JOIN
               todos ON c.todo_id = todos.id
         '. $where_clause . '
         ORDER BY
               c.created_at DESC';

      $stmt = $this->connection->prepare($query);
      if(isset($this->todo_id)) {
         $stmt->bindValue(1,$this->todo_id);
      }

      $data = [];
      $stmt->execute();
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $data[] = $row;
      }
      return $data;

   }  


   //
   // Read single 
   //
   public function read_single($todo_id,$checklistitem_col,$checklistitem_col_value) {

      // if(!$this->is_permitted_search_col($message_col)) return null;

      $query = 
               'SELECT 
                  todos.title as task_title,
                  c.id,c.todo_id,c.title,c.slug,c.author_id,
                  c.done_at,c.created_at,c.updated_at
               FROM ' . $this->table . ' c
               LEFT JOIN
                  todos
               ON 
                  c.todo_id = todos.id
               WHERE
                  c.' . $checklistitem_col . ' = ?
               AND
                  c.todo_id  = ?
               LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$checklistitem_col_value);
      $stmt->bindValue(2,$todo_id);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->title = $row['title'];
         $this->slug = $row['slug'];
         $this->author_id = $row['author_id'];
         $this->todo_id = $row['todo_id'];
         $this->done_at = $row['done_at'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
      }
   }


   //
   // Create 
   //
   public function create() {

      $query = 'INSERT INTO ' . $this->table . 
               ' SET 
                  title = :title, 
                  slug = :slug,
                  author_id = :author_id, 
                  todo_id = :todo_id';
      
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->author_id = htmlspecialchars(strip_tags($this->author_id));
      $this->todo_id = htmlspecialchars(strip_tags($this->todo_id));
   
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':title',$this->title);
      $stmt->bindValue(':slug',$this->slug);
      $stmt->bindValue(':author_id',$this->author_id);
      $stmt->bindValue(':todo_id',$this->todo_id);
   
      $stmt->execute();
      return $this->connection->lastInsertId();
   } 

   

   //
   // Update single 
   //
   public function update() {

      $query = 'UPDATE ' . $this->table . '
                  SET 
                     title = :title, 
                     slug = :slug,
                     author_id = :author_id, 
                     todo_id = :todo_id,
                     done_at = :done_at,
                     updated_at = CURRENT_TIMESTAMP
                  WHERE 
                     id = :id';

      $this->todo_id = htmlspecialchars(strip_tags($this->todo_id));
      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->done_at = htmlspecialchars(strip_tags($this->done_at));
      $this->author_id = htmlspecialchars(strip_tags($this->author_id));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':title',$this->title);
      $stmt->bindValue(':slug',$this->slug);
      $stmt->bindValue(':author_id',$this->author_id);
      $stmt->bindValue(':done_at',$this->done_at === "" ? NULL : $this->done_at);
      $stmt->bindValue(':todo_id',$this->todo_id);
      $stmt->bindValue(':id',$this->id);

      return $stmt->execute();
   }



   //
   // Delete single
   // 
   public function delete() {

      $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
      $this->id = htmlspecialchars(strip_tags($this->id));
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);
      return $stmt->execute();

   }

   
   //
   // Delete all for given parents
   // future : we can't bind a single parameter to 'IN' operator input - so we are exposing sql here - review and improve.
   // OK for now, since we know this function is only called w/ csv generated inside the code - not front-end input.
   //
   public function delete_checklistitems_permanently($todo_id_csv) { 

      if($todo_id_csv !== '') { 
         $query = 'DELETE FROM ' . $this->table . ' WHERE todo_id IN ('  . $todo_id_csv . ')';
         $stmt = $this->connection->prepare($query);
         return $stmt->execute(); 
      }
      return false;
   }

   // 
   // load
   // since Todo slugs are not unique, we qualify by $todo_id
   //
   public function load($todo_id,$checklistitem_col,$checklistitem_col_value) {
      $query = 
               'SELECT 
                  c.id,
                  c.title,
                  c.slug,
                  c.done_at,
                  c.created_at
               FROM ' . $this->table . ' c
               WHERE
                  c.' . $checklistitem_col . ' '. ' = ?
               AND
                  c.todo_id = ?
               LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$checklistitem_col_value);
      $stmt->bindValue(2,$todo_id);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->title = $row['title'];
         $this->slug = $row['slug'];
         $this->done_at = $row['done_at'];
         $this->created_at = $row['created_at'];
      }    
   }


   // get parent Todo
   function todo() {

      // $query = 
      //          'SELECT 
      //             id,title,done_at,created_at,updated_at
      //          FROM tasks
      //          WHERE
      //             id = ?
      //          ORDER BY created_at DESC
      //          LIMIT 0,1';
      // $stmt = $this->connection->prepare($query);

      // $stmt->bindValue(1,$this->todo_id);
      // $stmt->execute();
      // $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // echo($row);
   }

   


}