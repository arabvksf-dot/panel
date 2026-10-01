<?php

return [
    'discord_signature_ttl' => max(30, min(300, (int) env('DISCORD_SIGNATURE_TTL', 60))),
    'ai_chat_per_minute' => (int) env('AI_CHAT_RATE_LIMIT', 10),
    'csp_report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', true),
    'csp_policy' => env(
        'SECURITY_CSP_POLICY',
        "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com https://cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: blob: https:; " .
            "font-src 'self' data: https://cdnjs.cloudflare.com; connect-src 'self' https: wss:;"
    ),
];