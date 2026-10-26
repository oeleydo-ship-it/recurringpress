import { AlertTriangle, Plus, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { blankItem } from '@/Components/documents/totals';
import { Badge, Button, FormField, Modal, Textarea } from '@/Components/ui';
import { postJson } from '@/lib/http';
import { route } from '@/lib/route';
import { formatMoney } from '@/lib/utils';

/**
 * "Write with AI" for line items: describe the work, review Claude's suggestions, add them.
 * Catalog matches keep the product's own price; other prices are marked as estimates.
 */
export default function LineItemWriter({ items, onChange, currency, defaults = {} }) {
    const [open, setOpen] = useState(false);
    const [description, setDescription] = useState('');
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const generate = async () => {
        setLoading(true);
        setError(null);
        try {
            setResult(await postJson(route('ai.line-items'), { description }));
        } catch (e) {
            setError(e.errors?.description ?? e.message);
        } finally {
            setLoading(false);
        }
    };

    const add = () => {
        const suggested = result.items.map(({ estimated_price, tax_rate, ...it }) => ({
            ...blankItem(defaults),
            ...it,
            tax_rate: tax_rate ?? defaults.tax_rate ?? 0,
            _showDescription: Boolean(it.description),
        }));
        // Replace the empty starter row instead of keeping it above the suggestions.
        const kept = items.filter((it) => it.name?.trim() || it.product_id);
        onChange([...kept, ...suggested]);
        setOpen(false);
        setResult(null);
        setDescription('');
    };

    return (
        <>
            <Button variant="secondary" size="sm" icon={Sparkles} onClick={() => setOpen(true)}>
                Write with AI
            </Button>
            <Modal
                open={open}
                onClose={() => setOpen(false)}
                size="lg"
                title="Write line items with AI"
                description="Describe the work or goods; Claude suggests line items, using your products where they fit."
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setOpen(false)}>
                            Cancel
                        </Button>
                        {result?.items?.length ? (
                            <Button icon={Plus} onClick={add}>
                                Add {result.items.length} item{result.items.length === 1 ? '' : 's'}
                            </Button>
                        ) : null}
                    </>
                }
            >
                <div className="space-y-4">
                    <FormField label="What are you billing for?">
                        <Textarea rows={3} value={description} onChange={(e) => setDescription(e.target.value)} maxLength={1000} placeholder="e.g. Redesign of a 3-page website with logo refresh and two rounds of revisions" />
                    </FormField>
                    <Button variant="secondary" icon={Sparkles} onClick={generate} loading={loading} disabled={description.trim().length < 3}>
                        {result ? 'Suggest again' : 'Suggest line items'}
                    </Button>
                    {error ? (
                        <div className="flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                            <AlertTriangle className="mt-0.5 size-4 shrink-0" /> {error}
                        </div>
                    ) : null}
                    {result ? (
                        <div>
                            {result.items.length ? (
                                <ul className="divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                                    {result.items.map((it, i) => (
                                        <li key={i} className="flex items-start justify-between gap-3 p-3 text-sm">
                                            <div className="min-w-0">
                                                <p className="font-medium text-slate-800">{it.name}</p>
                                                {it.description ? <p className="text-slate-500">{it.description}</p> : null}
                                            </div>
                                            <div className="shrink-0 text-right">
                                                <p className="tabular-nums text-slate-800">
                                                    {it.quantity} × {formatMoney(it.price, currency)}
                                                </p>
                                                {it.estimated_price ? <Badge tone="yellow">Estimated price</Badge> : <Badge tone="green">From your products</Badge>}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-slate-500">No suggestions. Try describing the work in more detail.</p>
                            )}
                            {result.note ? <p className="mt-2 text-xs text-slate-500">{result.note}</p> : null}
                        </div>
                    ) : null}
                </div>
            </Modal>
        </>
    );
}
