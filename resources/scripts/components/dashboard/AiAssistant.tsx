import React, { FormEvent, useEffect, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCommentDots, faHandSparkles, faTimes } from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { useTranslation } from 'react-i18next';
import chatWithAssistant, { AssistantMessage } from '@/api/admin/chatWithAssistant';
import http from '@/api/http';
import { httpErrorToHuman } from '@/api/http';

interface SelectionContext {
    text: string;
    top: number;
    left: number;
}

export default () => {
    const user = useStoreState((state: ApplicationStore) => state.user.data);
    const enabled = useStoreState((state: ApplicationStore) => state.settings.data!.aiAssistantEnabled);
    const errorAnalysisEnabled = useStoreState(
        (state: ApplicationStore) => state.settings.data!.aiErrorAnalysisEnabled
    );
    const maxHistory = useStoreState((state: ApplicationStore) => state.settings.data!.aiMaxHistory);
    const { t } = useTranslation('appearance');
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState<AssistantMessage[]>([]);
    const [conversationId, setConversationId] = useState(() => window.crypto.randomUUID());
    const [draft, setDraft] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [selection, setSelection] = useState<SelectionContext | null>(null);
    const [diagnostics, setDiagnostics] = useState<string[]>([]);
    const [purpose, setPurpose] = useState<'assistant' | 'error_analysis'>('assistant');

    useEffect(() => {
        if (!user?.rootAdmin || !errorAnalysisEnabled) return;

        const remember = (diagnostic: string) => {
            setDiagnostics((current) => [...current, diagnostic.slice(0, 1200)].slice(-5));
        };
        const onError = (event: ErrorEvent) => {
            const location = event.filename ? ` ${event.filename.split('/').pop()}:${event.lineno || 0}` : '';
            remember(`JavaScript: ${event.message}${location}`);
        };
        const onRejection = (event: PromiseRejectionEvent) => {
            const reason = event.reason instanceof Error ? event.reason.message : String(event.reason);
            remember(`Unhandled promise rejection: ${reason}`);
        };
        const onApiError = (error: unknown) => {
            const status =
                typeof error === 'object' && error && 'response' in error
                    ? (error as { response?: { status?: number } }).response?.status
                    : undefined;
            if (status && status >= 500) remember(`API error: HTTP ${status}`);
        };
        const responseInterceptor = http.interceptors.response.use(
            (response) => response,
            (requestError) => {
                onApiError(requestError);
                return Promise.reject(requestError);
            }
        );

        window.addEventListener('error', onError);
        window.addEventListener('unhandledrejection', onRejection);

        return () => {
            window.removeEventListener('error', onError);
            window.removeEventListener('unhandledrejection', onRejection);
            http.interceptors.response.eject(responseInterceptor);
        };
    }, [user?.rootAdmin, errorAnalysisEnabled]);

    useEffect(() => {
        if (!user?.rootAdmin || !enabled) return;

        const updateSelection = () => {
            const selected = window.getSelection();
            const text = selected?.toString().trim() || '';
            if (!selected || !text || text.length > 2000 || selected.rangeCount === 0) {
                setSelection(null);
                return;
            }

            const range = selected.getRangeAt(0);
            const node = range.commonAncestorContainer;
            const element = node instanceof HTMLElement ? node : node.parentElement;
            if (element?.closest('input, textarea, [contenteditable="true"]')) {
                setSelection(null);
                return;
            }

            const bounds = range.getBoundingClientRect();
            setSelection({
                text,
                top: Math.max(8, bounds.top - 44),
                left: Math.max(8, Math.min(window.innerWidth - 150, bounds.left)),
            });
        };

        document.addEventListener('mouseup', updateSelection);
        document.addEventListener('keyup', updateSelection);
        return () => {
            document.removeEventListener('mouseup', updateSelection);
            document.removeEventListener('keyup', updateSelection);
        };
    }, [user?.rootAdmin, enabled]);

    if (!user?.rootAdmin || (!enabled && !errorAnalysisEnabled)) return null;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const content = draft.trim();
        if (!content || busy) return;

        const message: AssistantMessage = {
            role: 'user',
            content: selection ? `${content}\n\nSelected text:\n${selection.text}` : content,
        };
        setMessages((current) => [...current, message].slice(-maxHistory));
        setDraft('');
        setSelection(null);
        setBusy(true);
        setError('');

        chatWithAssistant(conversationId, message.content, purpose)
            .then((response) => {
                const assistantMessage: AssistantMessage = { role: 'assistant', content: response };
                setMessages((current) => [...current, assistantMessage].slice(-maxHistory));
            })
            .catch((requestError) => setError(httpErrorToHuman(requestError)))
            .finally(() => setBusy(false));
    };

    const analyzeLatest = () => {
        const latest = diagnostics[diagnostics.length - 1];
        if (!latest) return;
        setDraft(`${t('ai_analyze_prompt')}\n\n${latest}`);
        setPurpose('error_analysis');
        setOpen(true);
        setSelection(null);
    };

    return (
        <>
            {selection && !open && (
                <button
                    className={'ai-selection-action'}
                    style={{ top: selection.top, left: selection.left }}
                    type={'button'}
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => {
                        setDraft(t('ai_selection_prompt'));
                        setPurpose('assistant');
                        setOpen(true);
                    }}
                >
                    <FontAwesomeIcon icon={faHandSparkles} />
                    {t('ai_edit_selection')}
                </button>
            )}
            <button
                className={'ai-launcher'}
                type={'button'}
                aria-label={open ? t('ai_close') : t('ai_open')}
                title={open ? t('ai_close') : t('ai_open')}
                onClick={() => setOpen(!open)}
            >
                <FontAwesomeIcon icon={open ? faTimes : faCommentDots} />
            </button>
            {open && (
                <section className={'ai-panel'} aria-label={t('ai_title')}>
                    <header className={'ai-panel-header'}>
                        <strong>{t('ai_title')}</strong>
                        <button
                            type={'button'}
                            aria-label={t('ai_clear')}
                            onClick={() => {
                                setMessages([]);
                                setConversationId(window.crypto.randomUUID());
                            }}
                        >
                            {t('ai_clear')}
                        </button>
                    </header>
                    {errorAnalysisEnabled && diagnostics.length > 0 && (
                        <button className={'ai-analyze-action'} type={'button'} onClick={analyzeLatest}>
                            {t('ai_analyze_error')}
                        </button>
                    )}
                    <div className={'ai-messages'} aria-live={'polite'}>
                        {messages.map((message, index) => (
                            <article
                                key={`${message.role}-${index}`}
                                className={`ai-message ai-message-${message.role}`}
                            >
                                <strong>{message.role === 'user' ? t('ai_you') : t('ai_title')}</strong>
                                <pre>{message.content}</pre>
                            </article>
                        ))}
                        {busy && <div className={'status-skeleton'} role={'status'} aria-label={t('ai_loading')} />}
                        {error && <p role={'alert'}>{error}</p>}
                    </div>
                    {enabled && (
                        <form className={'ai-composer'} onSubmit={submit}>
                            <textarea
                                value={draft}
                                onChange={(event) => setDraft(event.target.value)}
                                placeholder={t('ai_placeholder')}
                                rows={3}
                                maxLength={6000}
                                aria-label={t('ai_placeholder')}
                            />
                            <button className={'theme-action'} type={'submit'} disabled={busy || !draft.trim()}>
                                {busy ? '…' : t('ai_send')}
                            </button>
                        </form>
                    )}
                </section>
            )}
        </>
    );
};
