import { useForm } from '@inertiajs/react';
import { Camera, KeyRound, Save } from 'lucide-react';
import { useState } from 'react';
import { Avatar, Badge, Button, Card, CardBody, CardFooter, CardHeader, FieldError, FormField, Input, PageHeader, PasswordInput } from '@/Components/ui';
import { route } from '@/lib/route';
import { titleCase } from '@/lib/utils';

export default function Profile({ profile }) {
    const [preview, setPreview] = useState(profile.avatar_url);
    const details = useForm({ name: profile.name, email: profile.email, phone: profile.phone ?? '', avatar: null, current_password: '' });
    const password = useForm({ current_password: '', password: '', password_confirmation: '' });
    const emailChanged = details.data.email.trim().toLowerCase() !== profile.email.toLowerCase();

    const saveDetails = (e) => {
        e.preventDefault();
        details.transform((data) => ({ ...data, _method: 'put' }));
        details.post(route('profile.update'), { forceFormData: true, preserveScroll: true, onSuccess: () => details.setData('current_password', '') });
    };

    const savePassword = (e) => {
        e.preventDefault();
        password.put(route('profile.password'), { preserveScroll: true, onSuccess: () => password.reset() });
    };

    return (
        <>
            <PageHeader title="Your profile" meta={<Badge tone="brand">{titleCase(profile.role)}</Badge>} />
            <div className="grid max-w-5xl gap-6 lg:grid-cols-2">
                <form onSubmit={saveDetails} noValidate>
                    <Card>
                        <CardHeader title="Personal details" />
                        <CardBody className="space-y-5">
                            <label className="flex cursor-pointer items-center gap-4">
                                <span className="relative">
                                    <Avatar src={preview} name={details.data.name} size="xl" />
                                    <span className="absolute -right-1 -bottom-1 flex size-7 items-center justify-center rounded-full bg-white text-slate-600 shadow ring-1 ring-slate-200">
                                        <Camera className="size-3.5" />
                                    </span>
                                </span>
                                <span className="text-sm">
                                    <span className="font-medium text-slate-800">Change photo</span>
                                    <span className="block text-slate-500">JPG, PNG or WebP · max 2 MB</span>
                                </span>
                                <input
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    className="sr-only"
                                    onChange={(e) => {
                                        const f = e.target.files?.[0];
                                        if (f) {
                                            details.setData('avatar', f);
                                            setPreview(URL.createObjectURL(f));
                                        }
                                    }}
                                />
                            </label>
                            <FieldError>{details.errors.avatar}</FieldError>
                            <FormField label="Name" required error={details.errors.name}>
                                <Input value={details.data.name} onChange={(e) => details.setData('name', e.target.value)} invalid={Boolean(details.errors.name)} />
                            </FormField>
                            <FormField label="Email" required error={details.errors.email}>
                                <Input type="email" value={details.data.email} onChange={(e) => details.setData('email', e.target.value)} invalid={Boolean(details.errors.email)} />
                            </FormField>
                            {emailChanged ? (
                                <FormField label="Current password" required error={details.errors.current_password} hint="Required to change your sign-in email.">
                                    <PasswordInput autoComplete="current-password" value={details.data.current_password} onChange={(e) => details.setData('current_password', e.target.value)} invalid={Boolean(details.errors.current_password)} />
                                </FormField>
                            ) : null}
                            <FormField label="Phone" error={details.errors.phone}>
                                <Input value={details.data.phone} onChange={(e) => details.setData('phone', e.target.value)} />
                            </FormField>
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
