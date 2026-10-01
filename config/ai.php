<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'api_key' => env('AI_API_KEY'),
    'model' => env('AI_MODEL'),
    'max_history' => (int) env('AI_MAX_HISTORY', 20),
    'conversation_ttl' => (int) env('AI_CONVERSATION_TTL', 1800),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1200),
];