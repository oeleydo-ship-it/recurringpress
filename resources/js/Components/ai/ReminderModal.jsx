import { router } from '@inertiajs/react';
import { AlertTriangle, Send, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Button, FormField, Input, Modal, Segmented, Textarea } from '@/Components/ui';
import { postJson } from '@/lib/http';
import { route } from '@/lib/route';

const TONES = [
    { value: 'friendly', label: 'Friendly' },
    { value: 'firm', label: 'Firm' },
    { value: 'final', label: 'Final notice' },
];

/** Draft a payment reminder with AI, edit it, then email it (with the PDF and pay link). */
export default function ReminderModal({ open, onClose, invoice }) {
    const [tone, setTone] = useState(invoice.is_overdue ? 'firm' : 'friendly');
    const [draft, setDraft] = useState({ subject: '', body: '' });
    const [drafting, setDrafting] = useState(false);
    const [sending, setSending] = useState(false);
    const [error, setError] = useState(null);

    const generate = async () => {
        setDrafting(true);
        setError(null);
        try {
            const res = await postJson(route('invoices.reminder.draft', invoice.id), { tone });
            setDraft({ subject: res.subject, body: res.body });
        } catch (e) {
            setError(e.message);
        } finally {
            setDrafting(false);
        }
    };

    const send = () =>
        router.post(route('invoices.reminder.send', invoice.id), draft, {
            preserveScroll: true,
            onStart: () => setSending(true),
            onFinish: () => setSending(false),
            onSuccess: () => {
                setDraft({ subject: '', body: '' });
                onClose();
            },
        });

    const ready = draft.subject.trim() && draft.body.trim();

    return (
        <Modal
            open={open}
            onClose={onClose}
            size="lg"
            title={`Payment reminder for ${invoice.invoice_number}`}
            description={`To ${invoice.client?.email ?? 'client'} · the PDF and a "view and pay" link are added automatically.`}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button icon={Send} onClick={send} loading={sending} disabled={!ready}>
                        Send reminder
                    </Button>
                </>
            }
        >
            <div className="space-y-4">
                <div className="flex flex-wrap items-center gap-3">
                    <Segmented value={tone} onChange={setTone} options={TONES} />
                    <Button variant="secondary" icon={Sparkles} onClick={generate} loading={drafting}>
                        {draft.body ? 'Rewrite with AI' : 'Draft with AI'}
                    </Button>
                </div>
                {error ? (
                    <div className="flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" /> {error}
                    </div>
                ) : null}
                <FormField label="Subject">
                    <Input value={draft.subject} onChange={(e) => setDraft((d) => ({ ...d, subject: e.target.value }))} maxLength={150} placeholder="Draft with AI, or write your own" />
                </FormField>
                <FormField label="Message" hint="Review before sending: you can edit everything.">
                    <Textarea rows={10} value={draft.body} onChange={(e) => setDraft((d) => ({ ...d, body: e.target.value }))} maxLength={5000} />
                </FormField>
            </div>
        </Modal>
    );
}
