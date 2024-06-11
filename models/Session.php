<?php
include_once '../app/Model.php';

//
// Session
//
// Record work sessions on specific Tasks or Todos - initially we support sessions for Tasks only.
// Modelled on Comment to future-proof - 'sessions' may belong to either Tasks or Todos.
//
//

class Session extends Model {

   private $connection;
   private $table = 'sessions';

   public $id;
   public $sessionable_id;    // Task or Todo id
   public $sessionable_type;  // Task or Todo
   public $author_id;
   public $started_at;        // equivalent to 'created_at'
   public $ended_at;        

   public function __construct($db) {

      $this->connection = $db;

      // cols permitted in building our WHERE clauses
      $this->set_permitted_search_cols(['id']);
   }

   
   //
   // Read all
   //
   public function read($sessionable_type = null,$sessionable_id = null) {

      $where_clause = ($sessionable_type && $sessionable_id)
         ?  'WHERE p.sessionable_type = ? AND p.sessionable_id = ? '
         :  '';

      $query = 
         'SELECT 
            p.id,
            p.sessionable_type,
            p.sessionable_id,
            p.author_id,
            p.started_at,
            p.ended_at
         FROM
            ' . $this->table . ' p
         '. $where_clause . '
         ORDER BY
            p.started_at DESC';

      $stmt = $this->connection->prepare($query);
      if(isset($sessionable_type) && isset($sessionable_id)) {
         $stmt->bindValue(1,$sessionable_type);
         $stmt->bindValue(2,$sessionable_id);
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
   public function read_single($session_col,$session_col_value) {

      if(!$this->is_permitted_search_col($session_col)) return null;

      $query = 
         'SELECT 
            p.id,
            p.sessionable_type,
            p.sessionable_id,
            p.author_id,
            p.started_at,
            p.ended_at
         FROM 
            ' . $this->table . ' p
         WHERE
            p.' . $session_col . ' ' . ' = ?
         LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$session_col_value,PDO::PARAM_STR);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->sessionable_type = $row['sessionable_type'];
         $this->sessionable_id = $row['sessionable_id'];
         $this->author_id = $row['author_id'];
         $this->started_at = $row['started_at'];
         $this->ended_at = $row['ended_at'];
      }
   }


   //
   // Create 
   //
   public function create() {

      $started_at = isset($this->started_at) ? 'started_at = :started_at,' : '' ;

      $query = 'INSERT INTO ' . $this->table . 
               ' SET sessionable_type = :sessionable_type,
                     sessionable_id= :sessionable_id,
                     '. $started_at . '
                     author_id= :author_id';
      
      $this->sessionable_type = htmlspecialchars(strip_tags($this->sessionable_type));
      $this->sessionable_id = htmlspecialchars(strip_tags($this->sessionable_id));
      if(isset($this->started_at)) $this->started_at = htmlspecialchars(strip_tags($this->started_at));
      $this->author_id = htmlspecialchars(strip_tags($this->author_id));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':sessionable_type',$this->sessionable_type,PDO::PARAM_STR);
      $stmt->bindValue(':sessionable_id',$this->sessionable_id,PDO::PARAM_INT);
      if(isset($this->started_at)) $stmt->bindValue(':started_at',$this->started_at,PDO::PARAM_STR);
      $stmt->bindValue(':author_id',$this->author_id,PDO::PARAM_INT);

      $stmt->execute();
      return $this->connection->lastInsertId();
   } 
   

   //
   // Update single 
   //
   public function update() {

      // if ended_at is null, don't update 
      $ended_at = isset($this->ended_at) ? 'ended_at = :ended_at,' : '' ;

      $query = 'UPDATE ' . $this->table . '
                  SET 
                     started_at = :started_at,
                     '. $ended_at . '
                     author_id = :author_id
                  WHERE 
                     id = :id';

      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->started_at = htmlspecialchars(strip_tags($this->started_at));
      if(isset($this->ended_at)) $this->ended_at = htmlspecialchars(strip_tags($this->ended_at));
      $this->author_id = htmlspecialchars(strip_tags($this->author_id));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);
      $stmt->bindValue(':started_at',$this->started_at,PDO::PARAM_STR);
      if(isset($this->ended_at)) $stmt->bindValue(':ended_at',$this->ended_at,PDO::PARAM_STR);
      $stmt->bindValue(':author_id',$this->author_id);

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
   public function delete_sessions_permanently($sessionable_id_csv,$sessionable_type) { 

      if($sessionable_id_csv !== '') {
         $query = 'DELETE FROM ' . $this->table . ' WHERE sessionable_id IN ('  . $sessionable_id_csv . ') AND sessionable_type =  :sessionable_type ';
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':sessionable_type',$sessionable_type,PDO::PARAM_STR);
         return $stmt->execute(); 
      }
      return null;

   }


   public function load($col,$value) {

   $query = 
            'SELECT 
               p.id,
               p.sessionable_type,
               p.sessionable_id,
               p.author_id,
               p.started_at,
               p.ended_at
            FROM 
               ' . $this->table . ' p
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
         $this->sessionable_type = $row['sessionable_type'];
         $this->sessionable_id = $row['sessionable_id'];
         $this->author_id = $row['author_id'];
         $this->started_at = $row['started_at'];
         $this->ended_at = $row['ended_at'];
      }
   }
   


}