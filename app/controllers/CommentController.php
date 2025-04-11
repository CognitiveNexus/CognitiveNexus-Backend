<?php

class CommentController {
    public function getComments($courseId) {
        $userId = Flight::get('userId');
        echo "Fetching comments for post ID: $courseId";
    }
    public function addComment($courseId) {
        $userId = Flight::get('userId');
        echo "Comment added to post ID: $courseId";
    }
    public function likeComment($courseId, $commentId) {
        $userId = Flight::get('userId');
        echo "Comment ID: $commentId liked for post ID: $courseId";
    }
}