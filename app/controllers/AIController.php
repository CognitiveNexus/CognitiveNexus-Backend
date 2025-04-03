<?php

class AIController {
    public static function askAI($model) {
        $messages = Flight::request()->data->messages;
        $code = Flight::request()->data->code;
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

        try {
            while (true) {
                $result = self::sendRequest($host, $key, $model, $messages);
                if ($result['finish'] != 'tool_calls') {
                    break;
                }
                $messages[] = $result;
                foreach ($result['tool_calls'] as $toolCall) {
                    if ($toolCall['function']['name'] == 'getUserCode') {
                        $toolContent = $code ?? '用户没有提供代码';
                    } else {
                        $toolContent = '未知调用';
                    }
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => $toolContent,
                    ];
                }
            }
        } catch (Exception $err) {
            Flight::jsonHalt(['error' => $err->getMessage()], 500);
        } finally {
            ob_end_flush();
        }
    }

    private static function sendRequest($host, $key, $model, $messages) {
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $_ENV['AI_PROMPT']],
                ...$messages,
            ],
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'getUserCode',
                        'description' => '获取用户提供的代码',
                    ]
                ]
            ],
            'temperature' => 0,
            'stream' => true,
        ];
        $result = [];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$host}/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$key}",
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$result) {
            return self::handleStream($data, $result);
        });

        try {
            curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
        } finally {
            curl_close($ch);
        }

        return $result;
    }

    private static function handleStream($data, &$result) {
        $lines = explode("\n\n", trim($data));
        foreach ($lines as $line) {
            if (!trim($line)) {
                continue;
            }
            [$prefix, $chunk] = explode(':', trim($line), 2);
            if (!$prefix) {
                self::responseDelta([]);
                continue;
            } else if ($prefix != 'data') {
                throw new Exception(trim($line));
            }

            if ($chunk == '[DONE]') {
                continue;
            }
            $chunk = json_decode($chunk, true);
            if ($chunk) {
                $delta = $chunk['choices'][0]['delta'];
                self::mergeDelta($result, $delta);
                self::responseDelta($delta);
                if ($chunk['choices'][0]['finish_reason']) {
                    $result['finish'] = $chunk['choices'][0]['finish_reason'];
                }
            }
        }
        return strlen($data);
    }

    private static function responseDelta($delta) {
        $reply = ['success' => true];
        foreach (['reasoning_content', 'content'] as $key) {
            if (array_key_exists($key, $delta)) {
                $reply[$key] = $delta[$key];
            }
        }
        echo json_encode($reply)."\n\n";
        ob_flush();
        flush();
    }

    private static function mergeDelta(&$origin, $delta) {
        foreach ($delta as $key => &$value) {
            if (is_array($value) && isset($origin[$key]) && is_array($origin[$key])) {
                self::mergeDelta($origin[$key], $value);
            } else if (is_string($value) && isset($origin[$key]) && is_string($origin[$key])) {
                $origin[$key] .= $value;
            } else {
                $origin[$key] = $value;
            }
        }
    }
}
