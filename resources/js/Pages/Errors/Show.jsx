import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Home } from 'lucide-react';
import { route } from '@/lib/route';

const COPY = {
    403: ['Access denied', "You don't have permission to view this page."],
    404: ['Page not found', "The page you're looking for doesn't exist or has moved."],
    429: ['Slow down', 'Too many requests. Please wait a moment and try again.'],
    500: ['Something went wrong', "We hit an unexpected error. It's been logged and we'll look into it."],
    503: ['Back soon', "We're performing maintenance. Please check back shortly."],
};

export default function ErrorPage({ status }) {
    const { auth, superadmin } = usePage().props;
    const [title, text] = COPY[status] ?? COPY[500];
    const home = superadmin ? route('superadmin.dashboard') : auth?.user ? route('dashboard') : route('landing');

    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-6 font-sans">
            <Head title={title} />
            <div className="pointer-events-none absolute inset-0" style={{ background: 'radial-gradient(40% 40% at 50% 30%, rgb(99 102 241 / 0.12), transparent 70%)' }} />
            <div className="relative max-w-md text-center">
                <p className="bg-gradient-to-br from-indigo-500 to-sky-500 bg-clip-text text-8xl font-bold tracking-tight text-transparent">{status}</p>
                <h1 className="mt-4 text-2xl font-semibold text-slate-900">{title}</h1>
                <p className="mt-3 text-slate-600">{text}</p>
                <div className="mt-8 flex items-center justify-center gap-3">
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className="inline-flex h-10 items-center gap-2 rounded-lg bg-white px-4 text-sm font-medium text-slate-700 shadow-xs ring-1 ring-slate-300 hover:bg-slate-50"
                    >
                        <ArrowLeft className="size-4" /> Go back
                    </button>
                    <Link href={home} className="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-medium text-white hover:bg-indigo-700">
                        <Home className="size-4" /> Home
                    </Link>
                </div>
            </div>
        </div>
    );
}
