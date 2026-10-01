import http from '@/api/http';

export interface AssistantMessage {
    role: 'user' | 'assistant';
    content: string;
}

export default (conversationId: string, message: string, purpose: 'assistant' | 'error_analysis'): Promise<string> =>
    http
        .post('/admin/ai/chat', { conversation_id: conversationId, message, purpose })
        .then((response) => response.data.content);
