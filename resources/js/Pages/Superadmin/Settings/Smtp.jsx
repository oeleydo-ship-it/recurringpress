import { useForm } from '@inertiajs/react';
import { Save, Send } from 'lucide-react';
import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select } from '@/Components/ui';
import { route } from '@/lib/route';

export default function SmtpSettings({ settings, hasPassword }) {
    const form = useForm({ ...Object.fromEntries(Object.entries(settings).map(([k, v]) => [k, v ?? ''])), mail_password: '' });
    const test = useForm({ test_email: '' });
    const d = form.data;
    const e = form.errors;
    const smtp = d.mail_mailer === 'smtp';

    return (
        <>
            <PageHeader title="Email delivery" description="Outgoing mail for invoices, invites, password resets and notifications." />
            <div className="grid max-w-5xl gap-6 lg:grid-cols-[1fr_320px]">
                <form
                    onSubmit={(ev) => {
                        ev.preventDefault();
                        form.put(route('superadmin.settings.smtp.update'), { preserveScroll: true, onSuccess: () => form.setData('mail_password', '') });
                    }}
                    noValidate
                >
                    <Card>
                        <CardBody className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Mailer" error={e.mail_mailer}>
                                <Select value={d.mail_mailer} onChange={(ev) => form.setData('mail_mailer', ev.target.value)} options={{ smtp: 'SMTP', sendmail: 'Sendmail', log: 'Log only (testing)' }} />
                            </FormField>
                            {smtp ? (
                                <>
                                    <FormField label="Encryption" error={e.mail_encryption}>
                                        <Select value={d.mail_encryption ?? 'null'} onChange={(ev) => form.setData('mail_encryption', ev.target.value)} options={{ tls: 'TLS (587)', ssl: 'SSL (465)', null: 'None' }} />
                                    </FormField>
                                    <FormField label="Host" required error={e.mail_host}>
                                        <Input value={d.mail_host} onChange={(ev) => form.setData('mail_host', ev.target.value)} placeholder="smtp.example.com" invalid={Boolean(e.mail_host)} />
                                    </FormField>
                                    <FormField label="Port" required error={e.mail_port}>
                                        <Input type="number" value={d.mail_port} onChange={(ev) => form.setData('mail_port', ev.target.value)} invalid={Boolean(e.mail_port)} />
                                    </FormField>
                                    <FormField label="Username" error={e.mail_username}>
                                        <Input value={d.mail_username} onChange={(ev) => form.setData('mail_username', ev.target.value)} autoComplete="off" />
                                    </FormField>
                                    <FormField label="Password" error={e.mail_password} hint={hasPassword ? 'Saved — leave blank to keep it.' : undefined}>
                                        <Input type="password" autoComplete="new-password" value={d.mail_password} onChange={(ev) => form.setData('mail_password', ev.target.value)} placeholder={hasPassword ? '•••••••• (saved)' : ''} />
                                    </FormField>
                                </>
                            ) : null}
                            <FormField label="From address" required error={e.mail_from_address}>
                                <Input type="email" value={d.mail_from_address} onChange={(ev) => form.setData('mail_from_address', ev.target.value)} invalid={Boolean(e.mail_from_address)} />
                            </FormField>
                            <FormField label="From name" required error={e.mail_from_name}>
                                <Input value={d.mail_from_name} onChange={(ev) => form.setData('mail_from_name', ev.target.value)} invalid={Boolean(e.mail_from_name)} />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Button type="submit" icon={Save} loading={form.processing}>
                                Save
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
                <Card className="h-fit">
                    <CardHeader title="Send a test" description="Uses the saved settings." />
                    <CardBody>
                        <form
                            onSubmit={(ev) => {
                                ev.preventDefault();
                                test.post(route('superadmin.settings.smtp.test'), { preserveScroll: true });
                            }}
                            className="space-y-3"
                            noValidate
                        >
                            <FormField error={test.errors.test_email}>
                                <Input type="email" value={test.data.test_email} onChange={(ev) => test.setData('test_email', ev.target.value)} placeholder="you@example.com" />
                            </FormField>
                            <Button type="submit" variant="secondary" icon={Send} loading={test.processing} className="w-full">
                                Send test email
                            </Button>
                        </form>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}
