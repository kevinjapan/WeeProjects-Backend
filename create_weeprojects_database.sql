
-- PROJECTS

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `author_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `projects` (`id`, `title`, `slug`,`author_id`) VALUES
(1, 'weeprojects', 'weeprojects',1),
(2, 'songs', 'songs',1),
(3, 'kanjistudysets', 'kanjistudysets',1),
(4, 'theenglishshelf', 'theenglishshelf',1);



-- TASKS

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `outline` text NOT NULL,
  `pin` BOOLEAN NOT NULL,
  `author_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `tasks` (`id`, `project_id`, `title`, `slug`, `body`, `author_id`) VALUES
(1, 1, 'weeprojects 1', 'weeprojects-1', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ',1),
(2, 2, 'songs 1', 'songs-1', 'Adipiscing elit. Ut interdum est nec lorem mattis interdum.is.',3),
(3, 1, 'weeprojects 2', 'weeprojects-2', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ',1),
(4, 4, 'theenglishshelf 1', 'theenglishshelf-1', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ',2),
(5, 4, 'kanjistudysets 1', 'kanjistudysets-1', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ',2),
(6, 1, 'weeprojects 3', 'weeprojects-3', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ',1);




-- TODOS

CREATE TABLE `todos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `author_id` int(11) NOT NULL,
  `on_going` BOOLEAN NOT NULL,
  `has_checklist` BOOLEAN NOT NULL,
  `done_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `todos` (`id`, `task_id`, `title`, `slug`, `author_id`) VALUES
(1, 1, 'weeprojects 1 a', 'weeprojects-1-a','1'),
(2, 2, 'songs 1 a', 'songs-1-a',3),
(3, 1, 'weeprojects 1 b', 'weeprojects-1-b','1'),
(4, 6, 'weeprojects 3 a', 'weeprojects-3-a',2),
(5, 4, 'theenglishshelf 1 a', 'theenglishshelf-1-a',2),
(6, 1, 'weeprojects 1 c', 'weeprojects-1-c',1),
(7, 4, 'theenglishshelf 1 b', 'theenglishshelf-1-b',2),
(8, 4, 'theenglishshelf 1 c', 'theenglishshelf-1-c',2),
(9, 4, 'weeprojects 1 d', 'weeprojects-1-d',2),
(10, 4, 'weeprojects 2 a', 'weeprojects-2-a',2),
(11, 4, 'weeprojects 2 b', 'weeprojects-2-b',2),
(12, 4, 'weeprojects 2 c', 'weeprojects-2-c',2);




-- COMMENTS

CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `commentable_id` int(11) NOT NULL,
  `commentable_type` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `comments` (`id`, `commentable_type`, `commentable_id`, `title`, `slug`, `body`, `author_id`) VALUES
(1,  'todo', 457, 'comment for todo 457',  'comment-for-todo-457', 'Lorem ipsum dolor sit amet.',1),
(2,  'task', 21,  'comment for task 21',   'comment-for-task-21',   'lorem shlorem',3),
(3,  'todo', 404, 'comment for todo 404',  'comment-for-todo-404', 'Lorem ipsum dolor sit amet.',1),
(4,  'project', 1,'comment for project 1', 'comment-for-project-1','Lorem ipsum dolor sit amet.',12),
(5,  'task', 6,   'comment for task 6',    'comment-for-task-6',   'Lorem ipsum dolor sit amet.',2),
(6,  'todo', 404, 'comment for todo 404',  'comment-for-todo-404', 'Lorem ipsum dolor sit amet.',2),
(7,  'todo', 405, 'comment for todo 405',  'comment-for-todo-405', 'Lorem ipsum dolor sit amet.',4),
(8,  'task', 45,  'comment for task 45',   'comment-for-task-45',  'Lorem ipsum dolor sit amet.',1),
(9,  'todo', 417, 'comment for todo 417',  'comment-for-todo-417', 'Lorem ipsum dolor sit amet.',2),
(10, 'todo', 404, 'comment for todo 404',  'comment-for-todo-404', 'Lorem ipsum dolor sit amet.',12),
(11, 'todo', 415, 'comment for todo 415',  'comment-for-todo-415', 'Lorem ipsum dolor sit amet.',3),
(12, 'project', 1,'comment for project 1', 'comment-for-project-1','Lorem ipsum dolor sit amet.',4);




-- SESSIONS

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessionable_id` int(11) NOT NULL,
  `sessionable_type` varchar(255) NOT NULL,
  `author_id` int(11) NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `sessions` (`id`, `sessionable_type`, `sessionable_id`,`author_id`,`started_at`,`ended_at`) VALUES
(1,   'task',  457,   1,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(2,   'task',   21,   3,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(3,   'task',  404,   1,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(4,   'task',    1,  12,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(5,   'task',    6,   2,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(6,   'task',  404,   2,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(7,   'task',  405,   4,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(8,   'task',   45,   1,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(9,   'task',  417,   2,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(10,  'task',  404,  12,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(11,  'task',  415,   3,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24'),
(12,  'task',    1,   4,   '2023-04-24 15:07:24',   '2023-04-24 15:07:24');





-- CHECKLISTITEMS

CREATE TABLE `checklistitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `todo_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `author_id` int(11) NOT NULL,
  `on_going` BOOLEAN NOT NULL,
  `done_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

INSERT INTO `checklistitems` (`id`, `todo_id`, `title`, `slug`, `author_id`) VALUES
(1, 1, 'weeprojects 1 a', 'weeprojects-1-a','1'),
(2, 2, 'songs 1 a', 'songs-1-a',3),
(3, 1, 'weeprojects 1 b', 'weeprojects-1-b','1'),
(4, 6, 'weeprojects 3 a', 'weeprojects-3-a',2),
(5, 4, 'theenglishshelf 1 a', 'theenglishshelf-1-a',2),
(6, 1, 'weeprojects 1 c', 'weeprojects-1-c',1),
(7, 4, 'theenglishshelf 1 b', 'theenglishshelf-1-b',2),
(8, 4, 'theenglishshelf 1 c', 'theenglishshelf-1-c',2),
(9, 4, 'weeprojects 1 d', 'weeprojects-1-d',2),
(10, 4, 'weeprojects 2 a', 'weeprojects-2-a',2),
(11, 4, 'weeprojects 2 b', 'weeprojects-2-b',2),
(12, 4, 'weeprojects 2 c', 'weeprojects-2-c',2);



-- USERS

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `users` (`id`, `username`, `email`,`password_hash`) VALUES
(1, 'user-1', 'user-1', 'user-1'),
(2, 'user-2', 'user-2', 'user-2'),
(3, 'user-3', 'user-3', 'user-3');



-- PROJECT_USER LOOKUP

CREATE TABLE `project_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `project_user` (`id`, `project_id`, `user_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 1);




-- MESSAGES

CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `project_id` int NOT NULL,
  `body` text NOT NULL,
  `author_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `messages` (`id`,  `project_id`, `title`, `slug`, `body`, `author_id`) VALUES
(1,  1, 'message for project 457',  'message-for-project-457', 'Lorem ipsum dolor sit amet.',1),
(2,  2,  'message for project 21',   'message-for-project-21',   'lorem shlorem',3),
(3,  3, 'message for project 404',  'message-for-project-404', 'Lorem ipsum dolor sit amet.',1),
(4,  1, 'message for project 1', 'message-for-project-1','Lorem ipsum dolor sit amet.',12),
(5,  1,   'message for project 6',    'message-for-project-6',   'Lorem ipsum dolor sit amet.',2),
(6,  3, 'message for project 404',  'message-for-project-404', 'Lorem ipsum dolor sit amet.',2),
(7,  5, 'message for project 405',  'message-for-project-405', 'Lorem ipsum dolor sit amet.',4),
(8,  7,  'message for project 45',   'message-for-project-45',  'Lorem ipsum dolor sit amet.',1),
(9, 11, 'message for project 417',  'message-for-project-417', 'Lorem ipsum dolor sit amet.',2),
(10, 7, 'message for project 404',  'message-for-project-404', 'Lorem ipsum dolor sit amet.',12),
(11, 2, 'message for project 415',  'message-for-project-415', 'Lorem ipsum dolor sit amet.',3),
(12, 3,'message for project 1', 'message-for-project-1','Lorem ipsum dolor sit amet.',4);


