<?php

class AIController {
    public static function askAI($model) {
        $messages = Flight::request()->data->messages;
        if (!$messages) {
            Flight::jsonHalt(['error' => '未提供聊天记录'], 406);
        }
        if (!is_array($messages)) {
            Flight::jsonHalt(['error' => '聊天记录不正确'], 406);
        }
        foreach ($messages as $message) {
            if ($message['role'] != 'user' && $message['role'] != 'assistant') {
                Flight::jsonHalt(['error' => '聊天记录不正确'], 406);
            }
        }

        if (preg_match('/^deepseek\-/', $model)) {
            $host = 'https://api.deepseek.com';
            $key = $_ENV['AI_DEEPSEEK_KEY'];
        } else {
            Flight::jsonHalt(['error' => "无法调用模型 {$model}"], 406);
        }

        $client = OpenAI::factory()
            ->withBaseUri($host)
            ->withApiKey($key)
            ->make();

        $stream = $client->chat()->createStreamed([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $_ENV['AI_PROMPT']],
                ...Flight::request()->data->messages,
            ],
            'temperature' => 0,
        ]);

        foreach ($stream as $chunk) {
            if (isset($chunk['choices'][0]['delta']['content'])) {
                echo $chunk['choices'][0]['delta']['content'];
                ob_flush();
            }
        }

        ob_end_flush();
    }
}