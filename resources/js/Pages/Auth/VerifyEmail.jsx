import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { MailOpen, RefreshCw } from 'lucide-react';
import { AuthHeading } from '@/Layouts/AuthLayout';
import { Button } from '@/Components/ui';
import { route } from '@/lib/route';

export default function VerifyEmail() {
    const { auth } = usePage().props;
    const form = useForm({});

    return (
        <>
            <Head title="Verify your email" />
            <span className="mb-6 flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <MailOpen className="size-6" />
            </span>
            <AuthHeading
                title="Check your inbox"
                description={
                    <>
                        We sent a verification link to <span className="font-medium text-slate-800">{auth?.user?.email}</span>. Click it to activate your account.
                    </>
                }
            />
            <div className="space-y-3">
                <Button
                    size="lg"
                    icon={RefreshCw}
                    loading={form.processing}
                    onClick={() => form.post(route('verification.send'))}
                    className="w-full bg-indigo-600 text-white hover:bg-indigo-700"
                >
                    Resend verification email
                </Button>
                <Link href={route('logout')} method="post" as="button" className="w-full py-2 text-center text-sm font-medium text-slate-500 hover:text-slate-800">
                    Sign out
                </Link>
            </div>
        </>
    );
}
