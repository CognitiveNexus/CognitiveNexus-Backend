<?php

class CodeController {
    public static function runCode() {
        ['code' => $code, 'stdin' => $stdin] = Flight::request()->data;

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode(['code' => $code, 'stdin' => $stdin, 'usst' => '1906']),
            ],
        ]);

        $response = file_get_contents("{$_ENV['CODE_RUNNER_HOST']}/run.php", false, $context);
        Flight::response()->write($response);
    }
}