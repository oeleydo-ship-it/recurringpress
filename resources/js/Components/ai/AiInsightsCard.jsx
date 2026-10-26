import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, RefreshCw, Sparkles, TrendingDown, TrendingUp } from 'lucide-react';
import { useState } from 'react';
import { Badge, Button, Card, CardBody, CardHeader } from '@/Components/ui';
import { postJson } from '@/lib/http';
import { route } from '@/lib/route';
import { cn, formatDateTime, formatMoney } from '@/lib/utils';

const HEALTH = {
    healthy: { tone: 'green', label: 'Healthy' },
    watch: { tone: 'yellow', label: 'Keep an eye on it' },
    at_risk: { tone: 'red', label: 'At risk' },
};
const PRIORITY = { high: 'red', medium: 'yellow', low: 'gray' };

/** Dashboard card: Claude's read of receivables, recurring revenue and cash flow. */
export default function AiInsightsCard({ initial, remaining: initialRemaining }) {
    const [data, setData] = useState(initial);
    const [remaining, setRemaining] = useState(initialRemaining);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const analyse = async () => {
        setLoading(true);
        setError(null);
        try {
            const res = await postJson(route('ai.insights'));
            setData(res);
            setRemaining(res.remaining);
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
        }
    };

    const a = data?.analysis;
    const f = data?.figures;
    const money = (v) => formatMoney(v, f?.currency);

    return (
        <Card className="mt-6">
            <CardHeader
                icon={Sparkles}
                title="AI business insights"
                description={data ? `Generated ${formatDateTime(data.generated_at)} · ${remaining} AI requests left this month` : 'Claude reviews your invoices, recurring revenue and cash flow, and suggests what to do next.'}
                actions={
                    <Button variant={data ? 'secondary' : 'primary'} icon={data ? RefreshCw : Sparkles} loading={loading} onClick={analyse} disabled={remaining <= 0}>
                        {data ? 'Refresh' : 'Analyse my business'}
                    </Button>
                }
            />
            <CardBody className="space-y-5">
                {error ? (
                    <div className="flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" /> {error}
                    </div>
                ) : null}
                {loading && !data ? <p className="text-sm text-slate-500">Analysing your business. This can take up to a minute…</p> : null}
                {!a && !loading && !error ? (
                    <p className="text-sm text-slate-500">
                        Nothing yet. Only invoice numbers, client names, amounts and dates are sent for analysis; never emails, addresses or bank details.
                    </p>
                ) : null}

                {a ? (
                    <>
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0 flex-1">
                                <p className="text-base font-semibold text-slate-900">{a.headline}</p>
                                <p className="mt-1 text-sm text-slate-600">{a.summary}</p>
                            </div>
                            <Badge tone={HEALTH[a.health]?.tone ?? 'gray'} dot>
                                {HEALTH[a.health]?.label ?? a.health}
                            </Badge>
                        </div>

                        {/* Exact figures from our own calculation, not the model. */}
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <Figure label="Overdue" value={money(f.overdue_balance)} hint={`${f.overdue_count} invoice${f.overdue_count === 1 ? '' : 's'}`} />
                            <Figure label="Expected in 30 days" value={money(f.forecast.next_30_days.total)} hint={`${money(f.forecast.next_30_days.recurring_scheduled)} recurring`} />
                            <Figure label="Expected in 90 days" value={money(f.forecast.next_90_days.total)} hint={`${money(f.forecast.next_90_days.open_invoices_due)} from open invoices`} />
                            <Figure label="Monthly recurring revenue" value={money(f.mrr)} hint="Active schedules" />
                        </div>
                        {a.forecast_comment ? <p className="text-sm text-slate-600">{a.forecast_comment}</p> : null}

                        <div className="grid gap-5 lg:grid-cols-2">
                            <div>
                                <p className="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">Highlights</p>
                                <ul className="space-y-2">
                                    {a.highlights.map((h, i) => (
                                        <li key={i} className="flex gap-2 text-sm">
                                            {h.tone === 'negative' ? <TrendingDown className="mt-0.5 size-4 shrink-0 text-red-500" /> : <TrendingUp className={cn('mt-0.5 size-4 shrink-0', h.tone === 'positive' ? 'text-emerald-500' : 'text-slate-400')} />}
                                            <span>
                                                <span className="font-medium text-slate-800">{h.title}.</span> <span className="text-slate-600">{h.detail}</span>
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                                {a.risks.length ? (
                                    <>
                                        <p className="mt-4 mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">Risks</p>
                                        <ul className="space-y-2">
                                            {a.risks.map((r, i) => (
                                                <li key={i} className="flex gap-2 text-sm">
                                                    <AlertTriangle className="mt-0.5 size-4 shrink-0 text-amber-500" />
                                                    <span>
                                                        <span className="font-medium text-slate-800">{r.title}.</span> <span className="text-slate-600">{r.detail}</span>
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </>
                                ) : null}
                            </div>
                            <div>
                                <p className="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">Suggested actions</p>
                                <ul className="space-y-2">
                                    {a.actions.map((act, i) => (
                                        <li key={i} className="rounded-lg bg-slate-50 p-3 text-sm">
                                            <div className="flex items-start justify-between gap-2">
                                                <span className="font-medium text-slate-800">{act.title}</span>
                                                <Badge tone={PRIORITY[act.priority] ?? 'gray'}>{act.priority}</Badge>
                                            </div>
                                            <p className="mt-1 text-slate-600">{act.detail}</p>
                                            {act.invoices?.length ? (
                                                <div className="mt-2 flex flex-wrap gap-2">
                                                    {act.invoices.map((inv) => (
                                                        <Link key={inv.number} href={inv.url} className="inline-flex items-center gap-1 text-xs font-medium text-brand-600">
                                                            {inv.number} <ArrowRight className="size-3" />
                                                        </Link>
                                                    ))}
                                                </div>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                        <p className="text-xs text-slate-400">AI-generated analysis. Check important figures before acting on them.</p>
                    </>
                ) : null}
            </CardBody>
        </Card>
    );
}

function Figure({ label, value, hint }) {
    return (
        <div className="rounded-lg ring-1 ring-slate-200 p-3">
            <p className="text-xs text-slate-500">{label}</p>
            <p className="mt-0.5 text-lg font-semibold text-slate-900">{value}</p>
            {hint ? <p className="text-xs text-slate-500">{hint}</p> : null}
        </div>
    );
}
