<?php

return function($class) {
        
    $file = strtolower(str_replace('\\', '/', $class));

    // Check for class in the controllers dir
    if (file_exists($path = CONTROLLERS_PATH.$file.EXT)) {
        require $path;
    }

    // Check for class in the app dir
    if (file_exists($path = APP_PATH.$file.EXT)) {
        require $path;
    }
};