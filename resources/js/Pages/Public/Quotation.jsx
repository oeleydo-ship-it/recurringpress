import { useForm } from '@inertiajs/react';
import { CheckCircle2, Clock, ThumbsDown, ThumbsUp, XCircle } from 'lucide-react';
import { useState } from 'react';
import DocumentPreview from '@/Components/documents/DocumentPreview';
import PaymentPanel from '@/Components/PaymentPanel';
import PublicShell from '@/Components/PublicShell';
import { Button, Card, FormField, Modal, StatusBadge, Textarea } from '@/Components/ui';
import { formatDate, formatMoney } from '@/lib/utils';

export default function PublicQuotation({ quotation: q, company, payment, urls }) {
    const money = (v) => formatMoney(v, q.currency);
    const [dialog, setDialog] = useState(null); // 'accept' | 'reject'
    const accept = useForm({ notes: '' });
    const reject = useForm({ rejection_reason: '' });

    return (
        <PublicShell title={`Quotation ${q.number} from ${company.name}`} company={company} pdfUrl={urls.pdf}>
            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div className="order-2 lg:order-1">
                    <DocumentPreview src={urls.document} version={q.status} />
                </div>
                <div className="order-1 space-y-4 lg:order-2">
                    <Card className="overflow-hidden lg:sticky lg:top-6">
                        <div className="p-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-slate-500">Quotation {q.number}</p>
                                <StatusBadge status={q.status} />
                            </div>
                            <p className="mt-3 text-sm text-slate-500">Total</p>
                            <p className="text-4xl font-semibold tracking-tight text-slate-900 tabular-nums">{money(q.total)}</p>
                            {q.valid_until ? (
                                <p className={q.status === 'expired' ? 'mt-1 flex items-center gap-1 text-sm font-medium text-red-600' : 'mt-1 flex items-center gap-1 text-sm text-slate-500'}>
                                    <Clock className="size-4" /> {q.status === 'expired' ? 'Expired' : 'Valid until'} {formatDate(q.valid_until)}
                                </p>
                            ) : null}
                        </div>

                        <div className="border-t border-slate-100 bg-slate-50/50 p-6">
                            {q.can_respond ? (
                                <div className="grid gap-2">
                                    <Button size="lg" icon={ThumbsUp} onClick={() => setDialog('accept')}>
                                        Accept quotation
                                    </Button>
                                    <Button variant="secondary" icon={ThumbsDown} onClick={() => setDialog('reject')}>
                                        Decline
                                    </Button>
                                </div>
                            ) : q.status === 'accepted' ? (
                                <div className="space-y-5">
                                    <p className="flex items-center gap-2 text-sm font-medium text-emerald-700">
                                        <CheckCircle2 className="size-5" /> You accepted this quotation.
                                    </p>
                                    {payment ? (
                                        <div>
                                            <p className="mb-3 text-sm text-slate-600">Pay now to get started — you'll receive a paid invoice.</p>
                                            <PaymentPanel payment={payment} amount={q.total} currency={q.currency} brand={company.brand} intentUrl={urls.intent} returnUrl={urls.return} />
                                        </div>
                                    ) : null}
                                </div>
                            ) : q.status === 'converted' ? (
                                <p className="flex items-center gap-2 text-sm font-medium text-emerald-700">
                                    <CheckCircle2 className="size-5" /> Accepted — an invoice has been issued.
                                </p>
                            ) : q.status === 'rejected' ? (
                                <p className="flex items-center gap-2 text-sm font-medium text-slate-600">
                                    <XCircle className="size-5" /> You declined this quotation.
                                </p>
                            ) : (
                                <p className="text-sm text-slate-500">This quotation can no longer be accepted online. Please contact {company.name}.</p>
                            )}
                        </div>
                    </Card>
                </div>
            </div>

            <Modal
                open={dialog === 'accept'}
                onClose={() => setDialog(null)}
                title="Accept this quotation?"
                description={`${money(q.total)} · ${q.number}`}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)}>
                            Cancel
                        </Button>
                        <Button icon={ThumbsUp} loading={accept.processing} onClick={() => accept.post(urls.accept, { preserveScroll: true, onSuccess: () => setDialog(null) })}>
                            Accept
                        </Button>
                    </>
                }
            >
                <FormField label="Message (optional)" error={accept.errors.notes}>
                    <Textarea rows={3} value={accept.data.notes} onChange={(e) => accept.setData('notes', e.target.value)} placeholder="Anything we should know?" />
                </FormField>
            </Modal>

            <Modal
                open={dialog === 'reject'}
                onClose={() => setDialog(null)}
                title="Decline this quotation?"
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)}>
                            Cancel
                        </Button>
                        <Button variant="danger" loading={reject.processing} onClick={() => reject.post(urls.reject, { preserveScroll: true, onSuccess: () => setDialog(null) })}>
                            Decline
                        </Button>
                    </>
                }
            >
                <FormField label="Reason (optional)" error={reject.errors.rejection_reason} hint="Helps us improve our offer.">
                    <Textarea rows={3} value={reject.data.rejection_reason} onChange={(e) => reject.setData('rejection_reason', e.target.value)} />
                </FormField>
            </Modal>
        </PublicShell>
    );
}
