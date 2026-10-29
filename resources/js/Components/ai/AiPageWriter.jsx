import { AlertTriangle, ChevronDown, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Button, Card, CardBody, CardHeader, FormField, Input, Select, Textarea, useConfirm } from '@/Components/ui';
import { postJson } from '@/lib/http';
import { route } from '@/lib/route';

const TONES = {
    'friendly and clear': 'Friendly and clear',
    'professional and confident': 'Professional and confident',
    'warm and personal': 'Warm and personal',
    'concise and direct': 'Concise and direct',
};

/**
 * "Write with AI" for superadmin pages: describe the page, get a draft into the form.
 * Nothing is saved until the editor saves the page.
 */
export default function AiPageWriter({ ai, current, onApply }) {
    const confirm = useConfirm();
    const [open, setOpen] = useState(!current.title && !current.content);
    const [brief, setBrief] = useState('');
    const [type, setType] = useState('about');
    const [tone, setTone] = useState('friendly and clear');
    const [language, setLanguage] = useState('English');
    const [length, setLength] = useState('medium');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [issues, setIssues] = useState([]);

    if (!ai?.available) {
        return null;
    }

    const generate = async () => {
        const hasContent = current.title.trim() || current.content.replace(/<[^>]*>/g, '').trim();
        if (hasContent && !(await confirm({ title: 'Replace the current page?', message: 'The title, content and SEO fields will be replaced by the AI draft. Nothing is saved until you click Save.', confirmText: 'Replace' }))) {
            return;
        }
        setLoading(true);
        setError(null);
        setIssues([]);
        try {
            const res = await postJson(route('superadmin.pages.ai-generate'), { brief, type, tone, language, length });
            onApply(res);
            setIssues(res.style_issues ?? []);
        } catch (e) {
            setError(Object.values(e.errors ?? {})[0] ?? e.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <Card>
            <CardHeader
                icon={Sparkles}
                title="Write with AI"
                description="Describe the page; the draft goes into the editor for you to review and save."
                actions={
                    <button type="button" onClick={() => setOpen((v) => !v)} className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label={open ? 'Collapse' : 'Expand'}>
                        <ChevronDown className={`size-5 transition ${open ? 'rotate-180' : ''}`} />
                    </button>
                }
            />
            {open ? (
                <CardBody className="space-y-4">
                    <FormField label="What should this page say?" hint="Key points, audience, and any facts to include (prices, contact details, policies). The AI won't invent facts; missing details become [placeholders].">
                        <Textarea rows={4} value={brief} onChange={(e) => setBrief(e.target.value)} maxLength={3000} placeholder="e.g. About page for RecurringPress: we help freelancers get paid faster with recurring invoices and online payments. Founded in 2024, based in Dubai. Mention free plan and support by email." />
                    </FormField>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <FormField label="Page type">
                            <Select value={type} onChange={(e) => setType(e.target.value)} options={ai.types} />
                        </FormField>
                        <FormField label="Tone">
                            <Select value={tone} onChange={(e) => setTone(e.target.value)} options={TONES} />
                        </FormField>
                        <FormField label="Language">
                            <Input value={language} onChange={(e) => setLanguage(e.target.value)} maxLength={50} />
                        </FormField>
                        <FormField label="Length">
                            <Select value={length} onChange={(e) => setLength(e.target.value)} options={ai.lengths} />
                        </FormField>
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-xs text-slate-500">
                            Uses the blog's writing controls: {ai.human_style ? 'human writing style on, ' : ''}
                            {ai.banned_count} banned words/phrases (edit them in Blog posts → AI settings).
                        </p>
                        <Button icon={Sparkles} onClick={generate} loading={loading} disabled={brief.trim().length < 10}>
                            {loading ? 'Writing…' : 'Generate draft'}
                        </Button>
                    </div>
                    {error ? (
                        <div className="flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                            <AlertTriangle className="mt-0.5 size-4 shrink-0" /> {error}
                        </div>
                    ) : null}
                    {issues.length ? (
                        <div className="flex gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                            <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                            <span>
                                Draft added, but it still uses banned wording: <strong>{issues.join(', ')}</strong>. Please edit those parts before publishing.
                            </span>
                        </div>
                    ) : null}
                </CardBody>
            ) : null}
        </Card>
    );
}
