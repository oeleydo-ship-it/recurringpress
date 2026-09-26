import { Link, router, usePage } from '@inertiajs/react';
import { ArrowRightLeft, CheckCircle2, Copy, Download, ExternalLink, Eye, Link2, Pencil, Send, ThumbsDown, ThumbsUp, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import DocumentPreview from '@/Components/documents/DocumentPreview';
import { Button, Card, CardHeader, Dropdown, Input, PageHeader, StatusBadge, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn, formatDate, formatDateTime, formatMoney } from '@/lib/utils';

export default function QuotationShow({ quotation: q, can }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const confirm = useConfirm();
    const status = q.is_expired ? 'expired' : q.status;
    const post = (name) => router.post(route(name, q.id), {}, { preserveScroll: true });

    const convert = async () => {
        if (await confirm({ title: 'Convert to invoice?', message: 'A draft invoice will be created with these line items. The quotation will be locked.', confirmText: 'Convert', tone: 'brand' })) {
            post('quotations.convert');
        }
    };

    const emailClient = async () => {
        if (await confirm({ title: 'Email this quotation?', message: `A PDF copy will be sent to ${q.client?.email}.`, confirmText: 'Send email', tone: 'brand' })) post('quotations.email');
    };

    const destroy = async () => {
        if (await confirm({ title: `Delete ${q.quotation_number}?`, confirmText: 'Delete quotation' })) router.delete(route('quotations.destroy', q.id));
    };

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(q.share_url);
            toast.success('Link copied');
        } catch {
            toast.error('Could not copy the link');
        }
    };

    const timeline = [
        { label: 'Created', at: q.created_at, by: q.created_by, done: true },
        { label: 'Sent', at: q.sent_at, done: Boolean(q.sent_at) },
        { label: 'Viewed by client', at: q.viewed_at, done: Boolean(q.viewed_at) },
        {
            label: q.status === 'rejected' ? 'Rejected' : q.status === 'converted' ? 'Accepted & converted' : 'Accepted',
            at: q.responded_at,
            done: ['accepted', 'rejected', 'converted'].includes(q.status),
            danger: q.status === 'rejected',
        },
    ];

    return (
        <>
            <PageHeader
                title={q.quotation_number}
                back={route('quotations.index')}
                meta={<StatusBadge status={status} />}
                description={
                    q.client ? (
                        <>
                            For{' '}
                            <Link href={route('clients.show', q.client.id)} className="font-medium text-slate-700 hover:text-brand-600">
                                {q.client.name}
                            </Link>{' '}
                            · Valid until {formatDate(q.valid_until, df)}
                        </>
                    ) : null
                }
                actions={
                    <>
                        {can.convert ? (
                            <Button icon={ArrowRightLeft} onClick={convert}>
                                Convert to invoice
                            </Button>
                        ) : null}
                        {q.converted_invoice_id ? (
                            <Button href={route('invoices.show', q.converted_invoice_id)} icon={ExternalLink}>
                                View invoice
                            </Button>
                        ) : null}
                        <Button variant="secondary" icon={Send} onClick={emailClient} disabled={!q.client?.email}>
                            Email
                        </Button>
                        <Button variant="secondary" icon={Download} href={route('quotations.download', q.id)} external>
                            PDF
                        </Button>
                        <Dropdown
                            items={[
                                { label: 'Edit', icon: Pencil, href: route('quotations.edit', q.id), hidden: !can.edit },
                                { label: 'Mark as sent', icon: CheckCircle2, onClick: () => post('quotations.send'), hidden: !can.send || q.status === 'sent' },
                                { label: 'Mark accepted', icon: ThumbsUp, onClick: () => post('quotations.accept'), hidden: !can.accept },
                                { label: 'Mark rejected', icon: ThumbsDown, onClick: () => post('quotations.reject'), hidden: !can.reject },
                                { label: 'Duplicate', icon: Copy, href: route('quotations.duplicate', q.id), method: 'post' },
                                { label: 'Open client view', icon: Eye, href: q.share_url, external: true },
                                { separator: true, hidden: !can.edit },
                                { label: 'Delete', icon: Trash2, danger: true, onClick: destroy, hidden: !can.edit },
                            ]}
                        />
                    </>
                }
            />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <DocumentPreview src={route('quotations.preview', q.id)} version={`${q.status}-${q.total}`} />

                <div className="space-y-6">
                    <Card>
                        <div className="p-5">
                            <p className="text-sm text-slate-500">Quotation total</p>
                            <p className="mt-1 text-3xl font-semibold tracking-tight text-slate-900 tabular-nums">{formatMoney(q.total, currency)}</p>
                        </div>
                        <ol className="space-y-4 border-t border-slate-100 p-5">
                            {timeline.map((t) => (
                                <li key={t.label} className="flex gap-3">
                                    <span
                                        className={cn(
                                            'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full ring-4 ring-white',
                                            t.done ? (t.danger ? 'bg-red-500' : 'bg-emerald-500') : 'bg-slate-200',
                                        )}
                                    >
                                        {t.done ? <CheckCircle2 className="size-3 text-white" /> : null}
                                    </span>
                                    <div className="text-sm">
                                        <p className={t.done ? 'font-medium text-slate-900' : 'text-slate-400'}>{t.label}</p>
                                        {t.at ? (
                                            <p className="text-xs text-slate-500">
                                                {formatDateTime(t.at)}
                                                {t.by ? ` · ${t.by}` : ''}
                                            </p>
                                        ) : null}
                                    </div>
                                </li>
                            ))}
                        </ol>
                        {q.response_notes ? (
                            <div className="border-t border-slate-100 p-5">
                                <p className="text-xs font-medium tracking-wide text-slate-500 uppercase">Client note</p>
                                <p className="mt-1 text-sm whitespace-pre-line text-slate-700">{q.response_notes}</p>
                            </div>
                        ) : null}
                    </Card>

                    <Card>
                        <CardHeader title="Client link" description="Clients can review, accept or reject online." icon={Link2} />
                        <div className="flex gap-2 p-5">
                            <Input readOnly value={q.share_url} onFocus={(e) => e.target.select()} className="font-mono text-xs" />
                            <Button variant="secondary" size="icon" onClick={copyLink} aria-label="Copy link">
                                <Copy className="size-4" />
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}
