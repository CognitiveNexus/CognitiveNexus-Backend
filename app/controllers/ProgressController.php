<?php

class ProgressController {
    public function get($courseName) {
        $userId = Flight::get('userId');
        $progressInfo = Flight::db()->fetchRow(
            'SELECT progress FROM course_progress WHERE user_id = :user_id AND course_name = :course_name',
            ['user_id' => $userId, 'course_name' => $courseName],
        );
        $progress = $progressInfo->count() ? $progressInfo->progress : 0;
        Flight::json(['success' => true, 'progress' => $progress]);
    }
    public function set($courseName) {
        $userId = Flight::get('userId');
        $progress = Flight::request()->data->progress;
        Flight::db()->runQuery(
            <<<SQL
                INSERT INTO course_progress (user_id, course_name, progress)
                VALUES (:user_id, :course_name, :progress)
                ON CONFLICT (user_id, course_name) 
                DO UPDATE SET progress = EXCLUDED.progress;
            SQL,
            ['user_id' => $userId, 'course_name' => $courseName, 'progress' => $progress],
        );
        Flight::json(['success' => true]);
    }
}