<?php

class CommentController {
    public function getComments($courseName) {
        $userId = Flight::get('userId');
        $comments = Flight::db()->fetchAll(
            <<<SQL
                SELECT id, username, content, created_at, total_likes, own_rate
                FROM course_comments_with_likes(:user_id, :course_name)
                ORDER BY total_likes DESC, own_rate DESC, created_at DESC
            SQL,
            ['user_id' => $userId, 'course_name' => $courseName],
        );
        Flight::json(['success' => true, 'comments' => $comments]);
    }
    public function addComment($courseName) {
        $userId = Flight::get('userId');
        $content = Flight::request()->data->content;
        Flight::db()->runQuery(
            'INSERT INTO course_comments (course_name, user_id, content) VALUES (:course_name, :user_id, :content)',
            ['course_name' => $courseName, 'user_id' => $userId, 'content' => $content],
        );
        Flight::json(['success' => true]);
    }
    public function likeComment($courseName, $commentId) {
        $userId = Flight::get('userId');
        $rate = Flight::request()->data->rate;
        Flight::db()->runQuery(
            <<<SQL
                INSERT INTO course_comments_likes (comment_id, user_id, rate)
                VALUES (:comment_id, :user_id, :rate)
                ON CONFLICT (comment_id, user_id) 
                DO UPDATE SET rate = EXCLUDED.rate;
            SQL,
            ['comment_id' => $commentId, 'user_id' => $userId, 'rate' => $rate],
        );
        Flight::json(['success' => true]);
    }
}