<?php

Flight::group('/api', function () {
    Flight::route('/', function () {
        Flight::redirect('/');
    });

    Flight::group('/auth', function () {
        Flight::route('POST /login', ['AuthController', 'login']);
        Flight::route('POST /register', ['AuthController', 'register']);
    });

    Flight::group('', function () {
        Flight::route('POST /code/@command', ['CodeController', 'runCode']);
        Flight::route('POST /ask-ai/@model', ['AIController', 'askAI'])->streamWithHeaders([
            'X-Accel-Buffering' => 'no',
        ]);

        Flight::group('/progress/@courseName', function () {
            Flight::route('GET /', ['ProgressController', 'get']);
            Flight::route('POST /', ['ProgressController', 'set']);
        });
    }, ['AuthMiddleware']);
});
