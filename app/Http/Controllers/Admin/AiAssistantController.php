<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Http\Controllers\Controller;

class AiAssistantController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        abort_unless(is_string(config('ai.api_key')) && config('ai.api_key') !== '', 503);
        abort_unless(is_string(config('ai.model')) && config('ai.model') !== '', 503);

        $validated = $request->validate([
            'purpose' => ['required', 'string', 'in:assistant,error_analysis'],
            'conversation_id' => ['required', 'uuid'],
            'message' => ['required', 'string', 'max:6000'],
        ]);

        $feature = $validated['purpose'] === 'assistant' ? 'ai_assistant' : 'ai_error_analysis';
        abort_unless(config('ai.enabled') && config('features.' . $feature), 404);

        $baseUrl = config('ai.base_url');
        abort_unless(is_string($baseUrl) && parse_url($baseUrl, PHP_URL_SCHEME) === 'https', 503);

        $conversationKey = implode(':', [
            'ai-conversation',
            $request->user()->uuid,
            $validated['purpose'],
            $validated['conversation_id'],
        ]);
        $history = Cache::get($conversationKey, []);
        $history[] = ['role' => 'user', 'content' => $validated['message']];
        $history = array_slice($history, -max(1, (int) config('ai.max_history')));

        $messages = array_merge([
            [
                'role' => 'system',
                'content' => 'أنت مساعد دعم تقني داخل لوحة استضافة. أجب بالعربية عند استخدام العربية، وكن واضحًا ومختصرًا. لا تدّعِ أنك فتحت ملفًا أو نفذت أمرًا أو غيّرت أي ملف. لا تخترع أسماء ملفات أو أرقام أسطر؛ اطلب السجل أو المصدر الناقص عند الحاجة. إذا اقترحت تغييرًا، اعرض السبب ومقتطفًا قبل/بعد كنص فقط واطلب موافقة صريحة. لا تنفذ شيفرة أو أدوات ولا تطلب أسرارًا أو كلمات مرور أو مفاتيح API.',
            ],
        ], $history);

        try {
            $response = Http::withToken(config('ai.api_key'))
                ->acceptJson()
                ->timeout(config('ai.timeout'))
                ->withOptions(['allow_redirects' => false])
                ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                    'model' => config('ai.model'),
                    'messages' => $messages,
                    'max_tokens' => config('ai.max_tokens'),
                ]);
        } catch (Throwable) {
            return new JsonResponse(['message' => 'The AI service is temporarily unavailable.'], 502);
        }

        $content = $response->json('choices.0.message.content');
        if (!$response->successful() || !is_string($content) || $content === '') {
            return new JsonResponse(['message' => 'The AI service could not complete this request.'], 502);
        }

        $history[] = ['role' => 'assistant', 'content' => $content];
        Cache::put(
            $conversationKey,
            array_slice($history, -max(1, (int) config('ai.max_history'))),
            now()->addSeconds(max(60, (int) config('ai.conversation_ttl')))
        );

        return new JsonResponse(['content' => $content]);
    }
}