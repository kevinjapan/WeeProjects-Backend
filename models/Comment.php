<?php
include_once '../app/Model.php';


class Comment extends Model {

   private $connection;
   private $table = 'comments';

   public $id;
   public $title;
   public $slug;
   public $commentable_id;
   public $commentable_type;
   public $body;
   public $author_id;
   public $created_at;
   public $updated_at;

   public function __construct($db) {

      $this->connection = $db;

      // cols permitted in building our WHERE clauses
      $this->set_permitted_search_cols(['id','slug']);
   }
   

   //
   // Read all
   //
   public function read($commentable_type = null,$commentable_id = null) {

      $where_clause = ($commentable_type && $commentable_id)
         ?  'WHERE p.commentable_type = ? AND p.commentable_id = ? '
         :  '';

      $query = 
         'SELECT 
            p.id,
            p.title,
            p.slug,
            p.commentable_type,
            p.commentable_id,
            p.body,
            p.author_id,
            p.created_at,
            p.updated_at
         FROM
            ' . $this->table . ' p
         '. $where_clause . '
         ORDER BY
            p.created_at DESC';

      $stmt = $this->connection->prepare($query);
      if(isset($commentable_type) && isset($commentable_id)) {
         $stmt->bindValue(1,$commentable_type);
         $stmt->bindValue(2,$commentable_id);
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
   public function read_single($comment_col,$comment_col_value) {

      if(!$this->is_permitted_search_col($comment_col)) return null;

      $query = 
         'SELECT 
            p.id,
            p.title,
            p.slug,
            p.commentable_type,
            p.commentable_id,
            p.body,
            p.author_id,
            p.created_at,
            p.updated_at
         FROM 
            ' . $this->table . ' p
         WHERE
            p.' . $comment_col . ' ' . ' = ?
         LIMIT 0,1';

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(1,$comment_col_value,PDO::PARAM_STR);

      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) {
         $this->id = $row['id'];
         $this->slug = $row['slug'];
         $this->title = $row['title'];
         $this->commentable_type = $row['commentable_type'];
         $this->commentable_id = $row['commentable_id'];
         $this->body = $row['body'];
         $this->author_id = $row['author_id'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
      }
   }


   //
   // Create 
   //
   public function create() {

      $query = 'INSERT INTO ' . $this->table . 
               ' SET  title = :title, 
                     slug = :slug,
                     body = :body,
                     commentable_type = :commentable_type,
                     commentable_id= :commentable_id';
      
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->body = htmlspecialchars(strip_tags($this->body));
      $this->commentable_type = htmlspecialchars(strip_tags($this->commentable_type));
      $this->commentable_id = htmlspecialchars(strip_tags($this->commentable_id));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':title',$this->title,PDO::PARAM_STR);
      $stmt->bindValue(':slug',$this->slug,PDO::PARAM_STR);
      $stmt->bindValue(':body',$this->body,PDO::PARAM_STR);
      $stmt->bindValue(':commentable_type',$this->commentable_type,PDO::PARAM_STR);
      $stmt->bindValue(':commentable_id',$this->commentable_id,PDO::PARAM_INT);

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
                     body = :body,
                     updated_at = CURRENT_TIMESTAMP
                  WHERE 
                     id = :id';

      $this->id = htmlspecialchars(strip_tags($this->id));
      $this->title = htmlspecialchars(strip_tags($this->title));
      $this->slug = htmlspecialchars(strip_tags($this->slug));
      $this->body = htmlspecialchars(strip_tags($this->body));

      $stmt = $this->connection->prepare($query);
      $stmt->bindValue(':id',$this->id,PDO::PARAM_INT);
      $stmt->bindValue(':title',$this->title,PDO::PARAM_STR);
      $stmt->bindValue(':slug',$this->slug,PDO::PARAM_STR);
      $stmt->bindValue(':body',$this->body,PDO::PARAM_STR);

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
   public function delete_comments_permanently($commentable_id_csv,$commentable_type) { 

      if($commentable_id_csv !== '') {
         $query = 'DELETE FROM ' . $this->table . ' WHERE commentable_id IN (' . $commentable_id_csv . ')  AND commentable_type =  :commentable_type ';
         $stmt = $this->connection->prepare($query);
         $stmt->bindValue(':commentable_type',$commentable_type,PDO::PARAM_STR);
         return $stmt->execute(); 
      }
      return false;
   }



   public function load($col,$value) {

   $query = 
            'SELECT 
               p.id,
               p.title,
               p.slug,
               p.commentable_type,
               p.commentable_id,
               p.body,
               p.author_id,
               p.created_at,
               p.updated_at
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
         $this->title = $row['title'];
         $this->slug = $row['slug'];
         $this->commentable_type = $row['commentable_type'];
         $this->commentable_id = $row['commentable_id'];
         $this->body = $row['body'];
         $this->author_id = $row['author_id'];
         $this->created_at = $row['created_at'];
         $this->updated_at = $row['updated_at'];
      }
   }
   


}