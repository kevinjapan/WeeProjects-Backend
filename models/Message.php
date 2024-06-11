<?php
include_once '../app/Model.php';


class Message extends Model {

   private $connection;
   private $table = 'messages';

   public $id;
   public $title;
   public $slug;
   public $project_id;
   public $body;
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
   // Read all
   //
   public function read($project_id = null,$inc_soft_deleted = false) {

      $where_clause = $inc_soft_deleted ? '' : ' WHERE m.deleted_at IS NULL';
      $insert = $where_clause === '' ? ' WHERE ' : ' AND ';
      $where_clause .= $insert . 'm.project_id = ? ';

      $query = 
         'SELECT 
            m.id,
            m.title,
            m.slug,
            m.project_id,
            m.body,
            m.author_id,
            m.created_at,
            m.updated_at,
            m.deleted_at
         FROM
            ' . $this->table . ' m
         '. $where_clause . '
         ORDER BY
            m.created_at DESC';

      $stmt = $this->connection->prepare($query);

      if(isset($project_id)) {
         $stmt->bindValue(1,$project_id);
      }

      $data = null;
      $stmt->execute();
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $data[] = $row;
      }
      return $data;
   }  


   //
   // Read single 
   //
   public function read_single($message_col,$message_col_value) {

      if(!$this->is_permitted_search_col($message_col)) return null;

      $query = 
         'SELECT 
            m.id,
            m.title,
            m.slug,
            m.project_id,
            m.body,
            m.author_id,
            m.created_at,
            m.updated_at
         FROM 
            ' . $this->table . ' m
         WHERE
            m.' . $message_col . ' ' . ' = ?
         LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$message_col_value,PDO::PARAM_STR);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->slug = $row['slug'];
         $this->title = $row['title'];
         $this->project_id = $row['project_id'];
         $this->body = $row['body'];
         $this->author_id = $row['author_id'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
         $this->deleted_at = $row['deleted_at'];
      }
   }



   //
   // Create Single
   //
   public function create() {

      $query = 'INSERT INTO ' . $this->table . 
               ' SET title = :title, 
                     slug = :slug,
                     body = :body,
                     project_id= :project_id';
      
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->body = htmlspecialchars(strip_tags($this->body));
      $this->project_id = htmlspecialchars(strip_tags($this->project_id));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':title',$this->title,PDO::PARAM_STR);
      $stmt->bindValue(':slug',$this->slug,PDO::PARAM_STR);
      $stmt->bindValue(':body',$this->body,PDO::PARAM_STR);
      $stmt->bindValue(':project_id',$this->project_id,PDO::PARAM_INT);

      $stmt->execute();
      return $this->connection->lastInsertId();
   } 
   

   //
   // Update single 
   //
   public function update() {

      if(!is_int($this->id)) return false;

      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->body = htmlspecialchars(strip_tags($this->body));

      $query = 'UPDATE ' . $this->table . '
                  SET 
                     title = :title,
                     slug = :slug, 
                     body = :body,
                     updated_at = CURRENT_TIMESTAMP
                  WHERE 
                     id = :id';


      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);
      $stmt->bindValue(':title',$this->title,PDO::PARAM_STR);
      $stmt->bindValue(':slug',$this->slug,PDO::PARAM_STR);
      $stmt->bindValue(':body',$this->body,PDO::PARAM_STR);

      return $stmt->execute();
   }


   //
   // Soft Delete and no cascade
   //
   public function delete() {
         
      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->deleted_at = htmlspecialchars(strip_tags($this->deleted_at));

      $query = 'UPDATE ' . $this->table . ' SET deleted_at = :deleted_at WHERE id = :id';
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
         $comment->delete_comments_permanently($this->id,'message');


      // delete Message

         $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
         $this->id = htmlspecialchars(strip_tags($this->id));
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':id',$this->id);
         return $stmt->execute();
   
   }
   
   
   //
   // Delete all for given parents
   // future : we can't bind a single parameter to 'IN' operator input - so we are exposing sql here - review and improve.
   // OK for now, since we know this function is only called w/ csv generated inside the code - not front-end input.
   //
   public function delete_messages_permanently($project_id_csv) { 

      $message_id_csv = $this->get_message_ids_csv($project_id_csv);
      
      // delete children
      $comment = new Comment($this->connection);
      $comment->delete_comments_permanently($message_id_csv,'message');

      // delete messages
      if($project_id_csv !== '') { 
         $query = 'DELETE FROM ' . $this->table . ' WHERE project_id IN ('  . $project_id_csv . ') ';
         $stmt = $this->connection->prepare($query);
         return $stmt->execute(); 
      }
      return null;

   }

   //
   private function get_message_ids_csv($project_id) {

      // get array of Message ids
      $data = [];
      $query = 'SELECT m.id FROM ' . $this->table . ' m WHERE m.project_id = :project_id';
      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':project_id',$project_id);
      $stmt->execute();
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $data[] = $row;
      }

      // build csv of Message ids
      $message_id_csv = "";
      foreach($data as $message_ids) {
         $message_id_csv.= $message_ids['id'] . ",";
      }
      return rtrim($message_id_csv,",");
}

   //
   // Load
   //
   public function load($col,$value) {

   $query = 
            'SELECT 
               m.id,
               m.title,
               m.slug,
               m.project_id,
               m.body,
               m.author_id,
               m.created_at,
               m.updated_at,
               m.deleted_at
            FROM 
               ' . $this->table . ' m
            WHERE
                  m.' . $col . ' '. ' = ?
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
         $this->project_id = $row['project_id'];
         $this->body = $row['body'];
         $this->author_id = $row['author_id'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
         $this->deleted_at = $row['deleted_at'];
      }
   }
   


}