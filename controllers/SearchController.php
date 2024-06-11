<?php
include_once '../database/Database.php';
include_once '../models/Project.php';
include_once '../models/Task.php';
include_once '../models/Todo.php';



class SearchController {

   private $database;
   private $db;

   public function __construct() {
      $this->database = Database::get_instance();
      $this->db = $this->database->get_connection();
   }

   public function search($route_parameters) {

      // -------------------------------------------------------------------------------------------
      // future : stub for now - to get client working and onto Github portfolio
      //
      // we want to get '..?search_term' from the url :
      // BareBonesRouter always returns key(col)/value pair - default matches a given Model
      // for Search, we can extract the value and discard the key(col):
      // echo($route_parameters['search_term']['value']);
      // -------------------------------------------------------------------------------------------
      
      $data = [ 
         [
            "id" => 416,
            "title" => "Make all forms consistent layout and styling",
            "slug" => "Make-all-forms-consistent-layout-and-styling",
            "done_at" => null,
            "on_going" => 0,
            "created_at" => "2023-04-03 12:44:26",
            "updated_at" => "2023-04-29 11:16:18",
            "author_id" => 1,
            "task_id" => 6,
         ],
         [
            "id" => 7,
            "title" => "theenglshshelf 1 b ahere",
            "slug" => "theenglshshelf-1-b-ahere",
            "done_at" => "2023-03-17 18:57:48",
            "on_going" => 0,
            "created_at" => "2023-03-04 12:33:08",
            "updated_at" => "2023-04-04 13:25:56",
            "author_id" => 1,
            "task_id" => 4,
        ],
        [
            "id" => 5,
            "title" => "theenglshshelf 1 a",
            "slug" => "theenglshshelf-1-a",
            "done_at" => "2023-03-17 18:57:47",
            "on_going" => 0,
            "created_at" => "2023-03-04 12:33:08",
            "updated_at" => "2023-03-17 18:57:47",
            "author_id" => 2,
            "task_id" => 4,
        ],
        [
            "id" => 4,
            "title" => "General Project Tasks layout - column or row?",
            "slug" => "General-Project-Tasks-layout---column-or-row?",
            "done_at" => null,
            "on_going" => 0,
            "created_at" => "2023-03-04 12:33:08",
            "updated_at" => "2023-03-30 12:55:00",
            "author_id" => 1,
            "task_id" => 43,
        ],
        [
            "id" => 3,
            "title" => "Implement CRUD for Tasks",
            "slug" => "Implement-CRUD-for-Tasks",
            "done_at" => "2023-03-27 17:54:35",
            "on_going" =>  0,
            "created_at" => "2023-03-04 12:33:08",
            "updated_at" => "2023-03-27 17:54:35",
            "author_id" => 5,
            "task_id" => 1,
        ],
        [
            "id" => 1,
            "title" => "ellaAa bella",
            "slug" => "ellaAa-bella",
            "done_at" => null,
            "on_going" => 0,
            "created_at" => "2023-03-04 12:33:08",
            "updated_at" => "2023-04-28 16:18:54",
            "author_id" => 1,
            "task_id" => 20,
        ]
      ];

      echo json_encode(
         array(
            'outcome' => 'success',
            'bearer_token' => 'kadfbnadfpoiyoaidn823ksdfdat_eo24jlad',
            'query_key' => 'search',
            'data' => $data,
         )
     );
   }


}