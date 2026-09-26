import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, Building2, CreditCard, Palette, Percent, Rocket, Upload, Users } from 'lucide-react';
import { Button } from '@/Components/ui';
import { route } from '@/lib/route';
import { themeStyle } from '@/lib/utils';

const steps = [
    { icon: Upload, label: 'Add your logo' },
    { icon: Building2, label: 'Business address' },
    { icon: CreditCard, label: 'How clients pay you' },
    { icon: Percent, label: 'Tax settings' },
    { icon: Palette, label: 'Brand & invoice style' },
    { icon: Users, label: 'Invite your team' },
];

export default function Welcome({ userName, companyName, resumeStep }) {
    const { auth } = usePage().props;
    const skip = useForm({});

    return (
        <div style={themeStyle(auth?.theme)} className="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 py-12 font-sans">
            <Head title="Welcome" />
            <div
                className="pointer-events-none absolute inset-0"
                style={{ background: 'radial-gradient(50% 40% at 50% 0%, color-mix(in oklab, var(--brand) 18%, transparent), transparent 70%)' }}
            />

            <div className="relative w-full max-w-2xl animate-slide-up">
                <div className="rounded-3xl bg-white p-8 shadow-pop ring-1 ring-slate-900/5 sm:p-12">
                    <span className="flex size-14 items-center justify-center rounded-2xl bg-brand-600 text-brand-contrast shadow-lg shadow-brand-600/25">
                        <Rocket className="size-7" />
                    </span>
                    <h1 className="mt-8 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Welcome, {userName.split(' ')[0]}! 👋</h1>
                    <p className="mt-3 text-lg text-slate-600">
                        Let&apos;s get <span className="font-medium text-slate-900">{companyName}</span> ready to send its first invoice. It takes about two minutes, and you can change everything later.
                    </p>

                    <ol className="mt-8 grid gap-3 sm:grid-cols-2">
                        {steps.map((s, i) => {
                            const Icon = s.icon;
                            const done = i < resumeStep;
                            return (
                                <li key={s.label} className="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-900/5">
                                    <span className={done ? 'flex size-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700' : 'flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600'}>
                                        <Icon className="size-4" />
                                    </span>
                                    <span className="text-sm font-medium text-slate-700">{s.label}</span>
                                </li>
                            );
                        })}
                    </ol>

                    <div className="mt-10 flex flex-col-reverse items-center gap-3 sm:flex-row sm:justify-between">
                        <button type="button" onClick={() => skip.post(route('onboarding.skip'))} disabled={skip.processing} className="text-sm font-medium text-slate-500 hover:text-slate-800">
                            Skip for now
                        </button>
                        <Button href={route('onboarding.setup')} size="lg" iconRight={ArrowRight} className="w-full sm:w-auto">
                            {resumeStep > 0 ? 'Continue setup' : "Let's get started"}
                        </Button>
                    </div>
                </div>
                <p className="mt-6 text-center text-sm text-slate-500">
                    Wrong account?{' '}
                    <Link href={route('logout')} method="post" as="button" className="font-medium text-slate-700 hover:text-slate-900">
                        Sign out
                    </Link>
                </p>
            </div>
        </div>
    );
}
