import {
    AlertTriangle,
    Bell,
    CheckCircle2,
    CircleDollarSign,
    Eye,
    FileCheck2,
    FileX2,
    Landmark,
    Mail,
    Repeat,
    Settings,
    Sparkles,
    UserMinus,
    UserPlus,
    Wallet,
} from 'lucide-react';

/** Icon + tint per notification type (see the App\Services\TenantNotifier callers). */
const TYPES = {
    invoice_paid: { icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-600' },
    payment_partial: { icon: CircleDollarSign, tone: 'bg-emerald-50 text-emerald-600' },
    invoice_viewed: { icon: Eye, tone: 'bg-sky-50 text-sky-600' },
    invoice_sent: { icon: Mail, tone: 'bg-sky-50 text-sky-600' },
    invoice_overdue: { icon: AlertTriangle, tone: 'bg-red-50 text-red-600' },
    quotation_accepted: { icon: FileCheck2, tone: 'bg-emerald-50 text-emerald-600' },
    quotation_declined: { icon: FileX2, tone: 'bg-red-50 text-red-600' },
    quotation_viewed: { icon: Eye, tone: 'bg-sky-50 text-sky-600' },
    recurring_generated: { icon: Repeat, tone: 'bg-brand-50 text-brand-600' },
    plan_changed: { icon: Sparkles, tone: 'bg-brand-50 text-brand-600' },
    trial_ending: { icon: Sparkles, tone: 'bg-amber-50 text-amber-600' },
    payout_requested: { icon: Landmark, tone: 'bg-slate-100 text-slate-600' },
    payout_approved: { icon: Landmark, tone: 'bg-emerald-50 text-emerald-600' },
    payout_paid: { icon: Landmark, tone: 'bg-emerald-50 text-emerald-600' },
    payout_rejected: { icon: Landmark, tone: 'bg-red-50 text-red-600' },
    expense_added: { icon: Wallet, tone: 'bg-amber-50 text-amber-600' },
    settings_changed: { icon: Settings, tone: 'bg-slate-100 text-slate-600' },
    team_member_added: { icon: UserPlus, tone: 'bg-slate-100 text-slate-600' },
    team_member_removed: { icon: UserMinus, tone: 'bg-slate-100 text-slate-600' },
};

export function notificationStyle(type) {
    return TYPES[type] ?? { icon: Bell, tone: 'bg-slate-100 text-slate-600' };
}
