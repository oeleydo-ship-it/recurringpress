import { useForm } from '@inertiajs/react';
import { KeyRound, Save } from 'lucide-react';
import { Avatar, Badge, Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, PasswordInput } from '@/Components/ui';
import { route } from '@/lib/route';

export default function Account({ account }) {
    const details = useForm({ name: account.name, email: account.email, current_password: '' });
    const password = useForm({ current_password: '', password: '', password_confirmation: '' });
    const emailChanged = details.data.email.trim().toLowerCase() !== account.email.toLowerCase();

    const saveDetails = (e) => {
        e.preventDefault();
        details.put(route('superadmin.account.update'), { preserveScroll: true, onSuccess: () => details.setData('current_password', '') });
    };

    const savePassword = (e) => {
        e.preventDefault();
        password.put(route('superadmin.account.password'), { preserveScroll: true, onSuccess: () => password.reset() });
    };

    return (
        <>
            <PageHeader title="Your account" description="The login you use for the superadmin area." meta={<Badge tone="brand">Superadmin</Badge>} />
            <div className="grid max-w-5xl gap-6 lg:grid-cols-2">
                <form onSubmit={saveDetails} noValidate>
                    <Card>
                        <CardHeader title="Sign-in details" />
                        <CardBody className="space-y-5">
                            <div className="flex items-center gap-4">
                                <Avatar src={account.avatar_url} name={details.data.name} size="xl" />
                                <div className="text-sm">
                                    <div className="font-medium text-slate-800">{account.name}</div>
                                    <div className="text-slate-500">{account.email}</div>
                                </div>
                            </div>
                            <FormField label="Name" required error={details.errors.name}>
                                <Input value={details.data.name} onChange={(e) => details.setData('name', e.target.value)} invalid={Boolean(details.errors.name)} />
                            </FormField>
                            <FormField label="Email" required error={details.errors.email}>
                                <Input type="email" autoComplete="username" value={details.data.email} onChange={(e) => details.setData('email', e.target.value)} invalid={Boolean(details.errors.email)} />
                            </FormField>
                            {emailChanged ? (
                                <FormField label="Current password" required error={details.errors.current_password} hint="Required to change your sign-in email.">
                                    <PasswordInput autoComplete="current-password" value={details.data.current_password} onChange={(e) => details.setData('current_password', e.target.value)} invalid={Boolean(details.errors.current_password)} />
                                </FormField>
                            ) : null}
                        </CardBody>
                        <CardFooter>
                            <Button type="submit" icon={Save} loading={details.processing}>
                                Save details
                            </Button>
                        </CardFooter>
                    </Card>
                </form>

                <form onSubmit={savePassword} noValidate>
                    <Card>
                        <CardHeader title="Password" description="Changing it signs you out of other devices." icon={KeyRound} />
                        <CardBody className="space-y-5">
                            <FormField label="Current password" required error={password.errors.current_password}>
                                <PasswordInput autoComplete="current-password" value={password.data.current_password} onChange={(e) => password.setData('current_password', e.target.value)} invalid={Boolean(password.errors.current_password)} />
                            </FormField>
                            <FormField label="New password" required error={password.errors.password} hint="8+ characters with upper & lowercase letters and a number.">
                                <PasswordInput autoComplete="new-password" value={password.data.password} onChange={(e) => password.setData('password', e.target.value)} invalid={Boolean(password.errors.password)} />
                            </FormField>
                            <FormField label="Confirm new password" required error={password.errors.password_confirmation}>
                                <PasswordInput autoComplete="new-password" value={password.data.password_confirmation} onChange={(e) => password.setData('password_confirmation', e.target.value)} />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Button type="submit" icon={KeyRound} loading={password.processing}>
                                Update password
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </>
    );
}
