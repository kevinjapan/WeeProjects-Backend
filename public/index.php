<?php

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Requested-With,Authorization');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Credentials: true');
    // header('Content-Length: 0's);
    header('Content-Type: application/json');
    header('HTTP/1.1 200 OK');
    exit(0);
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Requested-With, Accept, Accept-Encoding,Authorization');
header('Content-Type: application/json');


// $method = $_SERVER['REQUEST_METHOD'];
// if($method === 'OPTIONS') {
//     header('Access-Control-All-Origin: *');
//     header('Access-Control-Allow-Headers: Origin, Content-Type');
//     header('HTTP/1.1 200 OK');
// }



define('EXT', '.php');
define('APP_PATH', realpath('../app').'/');
define('CONTROLLERS_PATH', realpath('../controllers').'/'); 
define('ROOT',$_SERVER['DOCUMENT_ROOT']);

// register locations for class autoload
spl_autoload_register(require '../app/'.'autoloader'.EXT);

set_exception_handler("ErrorHandler::handleException");
set_error_handler("ErrorHandler::handleError");
                   

Env::load();



// routes

require_once '../router/BareBonesRouter.php';
$router = new BareBonesRouter();


// Search
$router->get('/search/{search_term}',[SearchController::class,'search']);

//http://weeprojects-api/task/comments/task/6

// Projects
$router->get('/',[ProjectController::class,'read']); 
$router->get('/projects',[ProjectController::class,'read']);
$router->get('/projects_inclusive',[ProjectController::class,'read_inclusive']);                      // include 'soft-deleted'
$router->get('/projects/{project:slug}',[ProjectController::class,'read_single']);
$router->get('/projects/{project}/{id}',[ProjectController::class,'read_single']);
$router->post('/projects',[ProjectController::class,'create']);
$router->put('/projects/{project:slug}',[ProjectController::class,'update']);
$router->delete('/projects',[ProjectController::class,'delete']);                                           // 'soft-delete'
$router->delete('/projects/delete_permanently',[ProjectController::class,'delete_permanently']);


// Some of these routes for Tasks and Todos appear very generic - and can trip up later routes - 
// however, we want the primary artefacts to be the main endpoints and hence give them priority.
// Later endpoints just have to be more specific.

// Tasks
$router->get('/tasks',[TaskController::class,'read']);
$router->get('/{project:slug}/tasks',[TaskController::class,'read']);
$router->get('/{project:slug}/tasks_inclusive',[TaskController::class,'read_inclusive']);                   // include 'soft-deleted' tasks
$router->get('/{project:slug}/{task:slug}',[TaskController::class,'read_single']);
$router->post('/{project:slug}/tasks',[TaskController::class,'create']);
$router->put('/{project:slug}/tasks/{task}',[TaskController::class,'update']);
$router->delete('/{project:slug}/{task}',[TaskController::class,'delete']);                                 // 'soft-delete'
$router->delete('/{project:slug}/{task}/delete_permanently',[TaskController::class,'delete_permanently']);


// Todos
$router->get('/todos',[TodoController::class,'read']);
$router->get('/{project:slug}/{task:slug}/todos',[TodoController::class,'read']);
$router->get('/{project:slug}/{task:slug}/todos_inclusive',[TodoController::class,'read_inclusive']);       // include 'soft-deleted' tasks
$router->get('/{project:slug}/{task:slug}/{todo:slug}',[TodoController::class,'read_single']);
$router->post('/{project:slug}/{task:slug}/todos',[TodoController::class,'create']);
$router->put('{project:slug}/{task:slug}/todos/{todo:slug}',[TodoController::class,'update']);
$router->delete('{project:slug}/{task:slug}/{todo:slug}',[TodoController::class,'delete']);                 // 'soft-delete'
$router->delete('/{project:slug}/{task:slug}/{todo:slug}/delete_permanently',[TodoController::class,'delete_permanently']);


// Comments
$router->get('/comments',[CommentController::class,'read']);
$router->get('/{commentable_type}/comments/{commentable_type}/{commentable_id}',[CommentController::class,'read']);
$router->get('/comments/{comment}',[CommentController::class,'read_single']);
$router->post('/comments',[CommentController::class,'create']);
$router->put('/comments',[CommentController::class,'update']);
$router->delete('/comments',[CommentController::class,'delete']);          // delete is permanent


// Sessions
$router->get('/sessions',[SessionController::class,'read']);
$router->get('/sessions/{sessionable_type}/{sessionable_id}',[SessionController::class,'read']);
$router->get('/sessions/{session}',[SessionController::class,'read_single']);
$router->post('/sessions',[SessionController::class,'create']);
$router->put('/sessions',[SessionController::class,'update']);
$router->delete('/sessions',[SessionController::class,'delete']);          // delete is permanent


// Checklistitems
$router->get('/checklistitems',[CheckListItemController::class,'read']);
$router->get('/{project:slug}/{task:slug}/{todo:slug}/checklistitems/{todo_id}',[CheckListItemController::class,'read']);
$router->get('/checklistitems/{checklistitem}',[CheckListItemController::class,'read_single']);
$router->post('/checklistitems',[CheckListItemController::class,'create']);
$router->put('/checklistitems/{checklistitem}',[CheckListItemController::class,'update']);
$router->delete('/{project:slug}/{task:slug}/{todo:slug}/checklistitems/{checklistitem}',[CheckListItemController::class,'delete']);   // delete is permanent


// Users
$router->get('/users/usersmanager/users',[UserController::class,'read']);
$router->get('/users/usersmanager/users/users_inclusive',[UserController::class,'read_inclusive']);    // include 'soft-deleted'
$router->get('/users/usersmanager/{user}',[UserController::class,'read_single']);
$router->post('/users/usersmanager',[UserController::class,'create']);
$router->put('/users/usersmanager/{user}',[UserController::class,'update']);
$router->delete('/users/usersmanager/users/{user}',[UserController::class,'delete']);                                     // 'soft-delete'
$router->delete('/users/usersmanager/users/{user}/delete_permanently',[UserController::class,'delete_permanently']);


// AuthenticationController
$router->post('/login',[AuthenticationController::class,'login'],'public');
$router->get('/logout',[AuthenticationController::class,'logout'],'public');


// Messages
$router->get('/projects/{project:slug}/messageboard/messages',[MessageController::class,'read']);
$router->get('/messages/{message_id}',[MessageController::class,'read']);
$router->get('/projects/{project:slug}/messageboard/messages_inclusive',[MessageController::class,'read_inclusive']);    // include 'soft-deleted'
$router->get('/messages/{comment}',[MessageController::class,'read_single']);
$router->post('/projects/{project:slug}/messageboard/messages',[MessageController::class,'create']);
$router->put('/projects/{project:slug}/messageboard/messages/{message}',[MessageController::class,'update']);
$router->delete('/projects/{project:slug}/messageboard/messages/{message}',[MessageController::class,'delete']);        // 'soft-delete'
$router->delete('/projects/{project:slug}/messageboard/messages/delete_permanently/{message}',[MessageController::class,'delete_permanently']);



// $router->get('/about','about.html');
// $router->get('/project?id=1',$projectcontroller->read());


$router->notfound();