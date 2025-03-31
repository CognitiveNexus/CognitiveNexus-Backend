<?php

class AIController {
    public static function askAI($model){
        Flight::jsonHalt(['error' => "AI 模型 {$model} 尚未准备好"], 406);
    }
}