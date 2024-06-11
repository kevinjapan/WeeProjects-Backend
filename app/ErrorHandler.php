<?php



class ErrorHandler {


    //
    // handle exceptions
    //
    public static function handleException(Throwable $exception) {

        http_response_code(500);

        $code = $exception->getCode();
        $message = $exception->getMessage();

        // customize messages
        switch($code) {
            case 1045:
                // SQL [1045] exposes database 'username' - we provide custom msg w/out this detail
                $message = $code === 1045 ? "SQLSTATE[HY000] [1045] SQL Access denied for user" : $exception->getMessage();
        }

        echo json_encode([
            "code" => $code,
            "message" => $message,
            "file" => str_replace("\\","/",$exception->getFile()),
            "line" => $exception->getLine()
        ],JSON_UNESCAPED_SLASHES);
    }


    //
    // handle errors
    //
    public static function handleError(
        int $errno,string $errstr, string $errfile, int $errline
    ) {
        // use ErrorException - represent errors as exceptions..
        throw new ErrorException($errstr,0,$errno,$errfile,$errline);
    }

}