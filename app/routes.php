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

        Flight::route('GET /progress/all', ['ProgressController', 'getAll']);
        Flight::group('/progress/@courseName', function () {
            Flight::route('GET /', ['ProgressController', 'get']);
            Flight::route('POST /', ['ProgressController', 'set']);
        });

        Flight::group('/comments', function () {
            Flight::group('/@courseId', function () {
                Flight::route('GET /', ['CommentController', 'getComments']);
                Flight::route('POST /', ['CommentController', 'addComment']);
            });
            Flight::route('POST /@courseId/@commentId/like', ['CommentController', 'likeComment']);
        });
    }, ['AuthMiddleware']);
});
