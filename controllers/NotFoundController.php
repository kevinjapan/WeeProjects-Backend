<?php
include_once '../database/Database.php';
include_once '../models/Project.php';



class NotFoundController {

    // private $database;
    // private $db;

    public function __construct() {
        // $this->database = new Database();
        // $this->db = $this->database->connect();
    }


    public function notfound() {
        echo('NotFoundController 404 Not Found.');
    }
}