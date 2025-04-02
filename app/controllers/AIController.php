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
            if ($message['role'] != 'user' && $message['role'] != 'assistant' || !isset($message['content'])) {
                Flight::jsonHalt(['error' => '聊天记录不正确'], 406);
            }
        }

        if (preg_match('/^deepseek\-/', $model)) {
            $host = 'https://api.deepseek.com';
            $key = $_ENV['AI_DEEPSEEK_KEY'];
        } else {
            Flight::jsonHalt(['error' => "无法调用模型 {$model}"], 406);
        }

        function handleChunk($chunk) {
            $reply = ['success' => true];
            if (isset($chunk->choices[0]->delta)) {
                $delta = $chunk->choices[0]->delta;
                if (isset($delta->reasoning_content)) {
                    $reply['reasoning_content'] = $delta->reasoning_content;
                }
                if (isset($delta->content)) {
                    $reply['content'] = $delta->content;
                }
            }
            echo json_encode($reply)."\n\n";
            ob_flush();
            flush();
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$host}/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$key}",
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $_ENV['AI_PROMPT']],
                ...$messages,
            ],
            'temperature' => 0,
            'stream' => true,
        ]));
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
            $lines = explode("\n\n", trim($data));
            foreach ($lines as $line) {
                if (strpos($line, 'data: ') === 0) {
                    $chunk = substr($line, 6);
                    if ($chunk != '[DONE]') {
                        $chunk = json_decode($chunk);
                        if ($chunk) {
                            handleChunk($chunk);
                        }
                    }
                } else if (strlen(trim($line)) && strpos($line, ':') !== 0) {
                    throw new Exception(trim($line));
                }
            }
            return strlen($data);
        });

        try {
            curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
        } catch (Exception $err) {
            Flight::jsonHalt(['error' => $err->getMessage()], 500);
        } finally {
            curl_close($ch);
            ob_end_flush();
        }
    }
}
