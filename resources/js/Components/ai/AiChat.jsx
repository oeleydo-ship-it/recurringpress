import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@headlessui/react';
import { Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowUp, Check, ExternalLink, RotateCcw, Sparkles, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/Components/ui';
import { postJson } from '@/lib/http';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';

const STARTERS = [
    'Show my unpaid invoices',
    'Who are my top clients?',
    'Expenses this month',
    'How is my business doing this month?',
];

// The API takes the last few turns only, to keep requests small.
const HISTORY = 12;

/** Top-bar "Ask AI" button with a chat panel about the business and the app. */
export default function AiChat() {
    const { auth } = usePage().props;
    const storageKey = `ai-chat:${auth.company?.id}`;
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState([]);
    const [suggestions, setSuggestions] = useState(STARTERS);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [remaining, setRemaining] = useState(auth.company?.ai?.remaining ?? 0);
    const endRef = useRef(null);
    const inputRef = useRef(null);

    // Keep the conversation for this browser tab across page visits.
    useEffect(() => {
        try {
            const saved = JSON.parse(sessionStorage.getItem(storageKey) ?? 'null');
            if (saved?.messages) {
                setMessages(saved.messages);
                setSuggestions(saved.suggestions?.length ? saved.suggestions : STARTERS);
            }
        } catch {
            // Storage unavailable: start empty.
        }
    }, [storageKey]);

    useEffect(() => {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify({ messages, suggestions }));
        } catch {
            // Ignore.
        }
    }, [storageKey, messages, suggestions]);

    useEffect(() => {
        if (open) endRef.current?.scrollIntoView({ block: 'end' });
    }, [open, messages, loading]);

    if (!auth.company?.ai) return null;

    const send = async (text) => {
        const content = text.trim();
        if (!content || loading) return;
        const next = [...messages, { role: 'user', content }];
        setMessages(next);
        setInput('');
        setError(null);
        setLoading(true);
        try {
            const res = await postJson(route('ai.chat'), {
                messages: next.slice(-HISTORY).map(({ role, content }) => ({ role, content })),
            });
            setMessages([
                ...next,
                {
                    role: 'assistant',
                    content: res.reply,
                    links: res.links ?? [],
                    actions: (res.actions ?? []).map((a) => ({ ...a, state: 'pending' })),
                },
            ]);
            setSuggestions(res.suggestions ?? []);
            setRemaining(res.remaining);
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
            inputRef.current?.focus();
        }
    };

    // Update one proposed action (by token) inside the message list.
    const patchAction = (token, patch) =>
        setMessages((list) =>
            list.map((m) =>
                m.actions?.some((a) => a.token === token) ? { ...m, actions: m.actions.map((a) => (a.token === token ? { ...a, ...patch } : a)) } : m,
            ),
        );

    const confirmAction = async (action) => {
        patchAction(action.token, { state: 'saving', error: null });
        try {
            const res = await postJson(route('ai.chat.confirm'), { token: action.token });
            patchAction(action.token, { state: 'done', result: res });
            // Also part of the history, so the model knows on the next turn what was saved.
            setMessages((list) => [...list, { role: 'assistant', content: res.message, links: res.url ? [{ label: res.label, url: res.url }] : [] }]);
        } catch (e) {
            patchAction(action.token, { state: e.status === 422 ? 'failed' : 'pending', error: e.message });
        }
    };

    const reset = () => {
        setMessages([]);
        setSuggestions(STARTERS);
        setError(null);
    };

    return (
        <>
            <Button variant="soft" size="sm" icon={Sparkles} onClick={() => setOpen(true)} aria-label="Chat with AI">
                <span className="hidden sm:inline">Ask AI</span>
            </Button>

            <Dialog open={open} onClose={setOpen} className="relative z-50">
                <DialogBackdrop transition className="fixed inset-0 bg-slate-900/30 transition-opacity duration-200 data-closed:opacity-0" />
                <div className="fixed inset-0 flex justify-end">
                    <DialogPanel
                        transition
                        className="flex h-full w-full max-w-md flex-col bg-white shadow-xl transition duration-200 ease-out data-closed:translate-x-full"
                    >
                        <div className="flex items-center gap-3 border-b border-slate-200 px-4 py-3">
                            <span className="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <Sparkles className="size-4" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <DialogTitle className="text-sm font-semibold text-slate-900">AI assistant</DialogTitle>
                                <p className="text-xs text-slate-500">{remaining} AI requests left this month</p>
                            </div>
                            {messages.length ? (
                                <Button variant="ghost" size="icon-sm" onClick={reset} aria-label="New chat" title="New chat">
                                    <RotateCcw className="size-4" />
                                </Button>
                            ) : null}
                            <Button variant="ghost" size="icon-sm" onClick={() => setOpen(false)} aria-label="Close">
                                <X className="size-4" />
                            </Button>
                        </div>

                        <div className="flex-1 space-y-4 overflow-y-auto px-4 py-4">
                            {!messages.length ? (
                                <div className="pt-6 text-center">
                                    <span className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                                        <Sparkles className="size-6" />
                                    </span>
                                    <p className="mt-3 text-base font-semibold text-slate-900">How can I help?</p>
                                    <p className="mx-auto mt-1 max-w-xs text-sm text-slate-500">
                                        Ask about your invoices, clients, expenses and cash flow, or have me draft an invoice, quotation or expense for you to confirm.
                                    </p>
                                </div>
                            ) : null}

                            {messages.map((m, i) => (
                                <div key={i} className={cn('flex flex-col gap-2', m.role === 'user' ? 'items-end' : 'items-start')}>
                                    <div
                                        className={cn(
                                            'max-w-[85%] rounded-2xl px-3.5 py-2 text-sm leading-relaxed whitespace-pre-wrap break-words',
                                            m.role === 'user' ? 'rounded-br-md bg-brand-600 text-brand-contrast' : 'rounded-bl-md bg-slate-100 text-slate-800',
                                        )}
                                    >
                                        {m.content}
                                        {m.links?.length ? (
                                            <div className="mt-2 flex flex-wrap gap-1.5">
                                                {m.links.map((l) => (
                                                    <Link
                                                        key={l.url}
                                                        href={l.url}
                                                        onClick={() => setOpen(false)}
                                                        className="inline-flex items-center gap-1 rounded-md bg-white px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-slate-200 hover:bg-brand-50"
                                                    >
                                                        {l.label} <ExternalLink className="size-3" />
                                                    </Link>
                                                ))}
                                            </div>
                                        ) : null}
                                    </div>
                                    {m.actions?.length ? (
                                        <div className="w-full max-w-[85%] space-y-2">
                                            {m.actions.map((a) => (
                                                <ActionCard
                                                    key={a.token}
                                                    action={a}
                                                    onConfirm={() => confirmAction(a)}
                                                    onCancel={() => patchAction(a.token, { state: 'cancelled' })}
                                                    onOpen={() => setOpen(false)}
                                                />
                                            ))}
                                        </div>
                                    ) : null}
                                </div>
                            ))}

                            {loading ? (
                                <div className="flex justify-start">
                                    <div className="flex gap-1 rounded-2xl rounded-bl-md bg-slate-100 px-3.5 py-3">
                                        {[0, 150, 300].map((d) => (
                                            <span key={d} className="size-1.5 animate-bounce rounded-full bg-slate-400" style={{ animationDelay: `${d}ms` }} />
                                        ))}
                                    </div>
                                </div>
                            ) : null}

                            {error ? (
                                <div className="flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                                    <AlertTriangle className="mt-0.5 size-4 shrink-0" /> {error}
                                </div>
                            ) : null}
                            <div ref={endRef} />
                        </div>

                        <div className="border-t border-slate-200 px-4 pt-3 pb-4">
                            {suggestions.length && !loading ? (
                                <div className="mb-3 flex flex-wrap gap-2">
                                    {suggestions.map((s) => (
                                        <button
                                            key={s}
                                            type="button"
                                            onClick={() => send(s)}
                                            disabled={remaining <= 0}
                                            className="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:text-slate-900 disabled:opacity-50"
                                        >
                                            {s}
                                        </button>
                                    ))}
                                </div>
                            ) : null}
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    send(input);
                                }}
                                className="flex items-end gap-2 rounded-xl bg-white p-1.5 ring-1 ring-slate-300 focus-within:ring-2 focus-within:ring-brand-500"
                            >
                                <textarea
                                    ref={inputRef}
                                    rows={1}
                                    value={input}
                                    maxLength={2000}
                                    onChange={(e) => setInput(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && !e.shiftKey) {
                                            e.preventDefault();
                                            send(input);
                                        }
                                    }}
                                    placeholder={remaining > 0 ? 'Ask anything…' : 'No AI requests left this month'}
                                    disabled={remaining <= 0}
                                    className="max-h-32 min-h-9 flex-1 resize-none border-0 bg-transparent px-2 py-1.5 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 focus:outline-none"
                                />
                                <Button type="submit" size="icon-sm" disabled={!input.trim() || loading || remaining <= 0} aria-label="Send">
                                    <ArrowUp className="size-4" />
                                </Button>
                            </form>
                            <p className="mt-2 text-center text-[11px] text-slate-400">AI can make mistakes. Check important figures.</p>
                        </div>
                    </DialogPanel>
                </div>
            </Dialog>
        </>
    );
}

/** A record the assistant proposed; it is created only when the user clicks Confirm. */
function ActionCard({ action, onConfirm, onCancel, onOpen }) {
    const { state } = action;

    return (
        <div className="rounded-xl bg-white p-3 text-sm ring-1 ring-slate-200">
            <div className="flex items-baseline justify-between gap-2">
                <p className="font-semibold text-slate-900">{action.title}</p>
                {action.total ? <p className="shrink-0 font-semibold text-slate-900 tabular-nums">{action.total}</p> : null}
            </div>
            <ul className="mt-1.5 space-y-0.5 text-xs text-slate-600">
                {action.lines.map((line, i) => (
                    <li key={i} className="break-words">
                        {line}
                    </li>
                ))}
            </ul>

            {state === 'done' ? (
                <div className="mt-3 flex items-center justify-between gap-2 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs text-emerald-800">
                    <span className="flex items-center gap-1">
                        <Check className="size-3.5" /> {action.result?.message}
                    </span>
                    {action.result?.url ? (
                        <Link href={action.result.url} onClick={onOpen} className="font-semibold underline">
                            Open
                        </Link>
                    ) : null}
                </div>
            ) : state === 'cancelled' ? (
                <p className="mt-3 text-xs text-slate-400">Cancelled. Nothing was saved.</p>
            ) : state === 'failed' ? (
                <p className="mt-3 rounded-lg bg-red-50 px-2.5 py-1.5 text-xs text-red-700">{action.error}</p>
            ) : (
                <>
                    {action.error ? <p className="mt-2 text-xs text-red-600">{action.error}</p> : null}
                    <div className="mt-3 flex gap-2">
                        <Button size="sm" icon={Check} onClick={onConfirm} disabled={state === 'saving'}>
                            {state === 'saving' ? 'Saving…' : 'Confirm'}
                        </Button>
                        <Button size="sm" variant="ghost" onClick={onCancel} disabled={state === 'saving'}>
                            Cancel
                        </Button>
                    </div>
                </>
            )}
        </div>
    );
}
