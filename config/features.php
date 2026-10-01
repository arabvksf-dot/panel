<?php

return [
    'appearance' => (bool) env('FEATURE_APPEARANCE', true),
    'ai_error_analysis' => (bool) env('AI_ERROR_ANALYSIS_ENABLED', false),
    'ai_assistant' => (bool) env('AI_ASSISTANT_ENABLED', false),
];