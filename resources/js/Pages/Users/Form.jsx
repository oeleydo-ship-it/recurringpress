import { useForm } from '@inertiajs/react';
import { Mail, Save } from 'lucide-react';
import { Button, Card, CardBody, CardFooter, FormField, Input, PageHeader, PasswordInput, Select, Switch } from '@/Components/ui';
import { route } from '@/lib/route';

export default function UserForm({ member, roles }) {
    const editing = Boolean(member);
    const form = useForm({
        name: member?.name ?? '',
        email: member?.email ?? '',
        phone: member?.phone ?? '',
        role: member?.role && member.role !== 'owner' ? member.role : Object.keys(roles)[0] ?? 'member',
        is_active: member?.is_active ?? true,
        send_invite: true,
        password: '',
        password_confirmation: '',
    });
    const d = form.data;
    const e = form.errors;

    const submit = (ev) => {
        ev.preventDefault();
        if (editing) form.put(route('users.update', member.id));
        else form.post(route('users.store'));
    };

    return (
        <>
            <PageHeader title={editing ? `Edit ${member.name}` : 'Invite a team member'} back={route('users.index')} />
            <form onSubmit={submit} noValidate className="max-w-2xl">
                <Card>
                    <CardBody className="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Full name" required error={e.name}>
                                <Input value={d.name} onChange={(ev) => form.setData('name', ev.target.value)} invalid={Boolean(e.name)} />
                            </FormField>
                            <FormField label="Email" required error={e.email}>
                                <Input type="email" value={d.email} onChange={(ev) => form.setData('email', ev.target.value)} invalid={Boolean(e.email)} />
                            </FormField>
                            <FormField label="Phone" error={e.phone}>
                                <Input value={d.phone} onChange={(ev) => form.setData('phone', ev.target.value)} />
                            </FormField>
                            <FormField label="Role" error={e.role} hint={member?.is_owner ? 'The owner role cannot be changed.' : undefined}>
                                <Select value={member?.is_owner ? 'owner' : d.role} onChange={(ev) => form.setData('role', ev.target.value)} options={member?.is_owner ? { owner: 'Owner' } : roles} disabled={member?.is_owner || member?.is_me} />
                            </FormField>
                        </div>

                        {editing ? (
                            !member.is_me && !member.is_owner ? (
                                <Switch label="Active" description="Deactivated members can't sign in." checked={d.is_active} onChange={(v) => form.setData('is_active', v)} />
                            ) : null
                        ) : (
                            <div className="rounded-xl bg-slate-50 p-4">
                                <Switch
                                    label="Email an invitation"
                                    description="They'll choose their own password from a secure link."
                                    checked={d.send_invite}
                                    onChange={(v) => form.setData('send_invite', v)}
                                />
                                {!d.send_invite ? (
                                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                        <FormField label="Password" required error={e.password}>
                                            <PasswordInput autoComplete="new-password" value={d.password} onChange={(ev) => form.setData('password', ev.target.value)} invalid={Boolean(e.password)} />
                                        </FormField>
                                        <FormField label="Confirm password" required error={e.password_confirmation}>
                                            <PasswordInput autoComplete="new-password" value={d.password_confirmation} onChange={(ev) => form.setData('password_confirmation', ev.target.value)} />
                                        </FormField>
                                    </div>
                                ) : null}
                            </div>
                        )}
                    </CardBody>
                    <CardFooter>
                        <Button variant="secondary" href={route('users.index')}>
                            Cancel
                        </Button>
                        <Button type="submit" icon={editing ? Save : d.send_invite ? Mail : Save} loading={form.processing}>
                            {editing ? 'Save changes' : d.send_invite ? 'Send invitation' : 'Add member'}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}
