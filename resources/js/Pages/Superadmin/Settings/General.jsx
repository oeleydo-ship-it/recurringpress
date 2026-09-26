import { useForm } from '@inertiajs/react';
import { ImagePlus, Save, X } from 'lucide-react';
import { useState } from 'react';
import { Button, Card, CardBody, CardFooter, CardHeader, FieldError, FormField, Input, PageHeader, Switch, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';

export default function GeneralSettings({ settings, images }) {
    const form = useForm({
        ...Object.fromEntries(Object.entries(settings).map(([k, v]) => [k, v ?? ''])),
        hide_site_name: settings.hide_site_name,
        email_verification_enabled: settings.email_verification_enabled,
        site_logo: null,
        site_favicon: null,
        seo_og_image: null,
        remove_site_logo: false,
        remove_site_favicon: false,
        remove_seo_og_image: false,
    });
    const d = form.data;
    const e = form.errors;
    const field = (name, label, props = {}) => (
        <FormField label={label} error={e[name]} hint={props.hint} className={props.className} required={props.required}>
            {props.textarea ? (
                <Textarea rows={props.rows ?? 3} value={d[name]} onChange={(ev) => form.setData(name, ev.target.value)} className={props.mono ? 'font-mono text-xs' : ''} />
            ) : (
                <Input type={props.type} value={d[name]} onChange={(ev) => form.setData(name, ev.target.value)} invalid={Boolean(e[name])} />
            )}
        </FormField>
    );

    const submit = (ev) => {
        ev.preventDefault();
        form.transform((x) => ({ ...x, _method: 'put' }));
        form.post(route('superadmin.settings.general.update'), { forceFormData: true, preserveScroll: true });
    };

    return (
        <>
            <PageHeader title="General settings" />
            <form onSubmit={submit} noValidate className="max-w-4xl space-y-6">
                <Card>
                    <CardHeader title="Brand" />
                    <CardBody className="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {field('site_name', 'Site name', { required: true })}
                            {field('site_tagline', 'Tagline')}
                            {field('support_email', 'Support email', { type: 'email' })}
                            {field('contact_email', 'Contact form recipient', { type: 'email', hint: 'Where /contact messages are sent.' })}
                            {field('footer_text', 'Footer text', { className: 'sm:col-span-2' })}
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <ImageInput form={form} name="site_logo" label="Logo" current={images.site_logo} />
                            <ImageInput form={form} name="site_favicon" label="Favicon" current={images.site_favicon} accept="image/png,image/x-icon,image/jpeg,image/webp" />
                            <ImageInput form={form} name="seo_og_image" label="Social share image" current={images.seo_og_image} />
                        </div>
                        <Switch label="Hide site name next to the logo" checked={d.hide_site_name} onChange={(v) => form.setData('hide_site_name', v)} />
                    </CardBody>
                </Card>
                <Card>
                    <CardHeader title="Accounts" />
                    <CardBody>
                        <Switch label="Require email verification" description="New users must confirm their email before using the app." checked={d.email_verification_enabled} onChange={(v) => form.setData('email_verification_enabled', v)} />
                    </CardBody>
                </Card>
                <Card>
                    <CardHeader title="SEO" />
                    <CardBody className="grid gap-4">
                        {field('seo_description', 'Default meta description', { textarea: true, rows: 2, hint: `${String(d.seo_description ?? '').length}/160` })}
                        {field('seo_keywords', 'Keywords')}
                    </CardBody>
                </Card>
                <Card>
                    <CardHeader title="Tracking & chat" description="Raw HTML/JS added to every page. Only paste code from providers you trust." />
                    <CardBody className="grid gap-4">
                        {field('google_analytics_script', 'Analytics snippet', { textarea: true, rows: 4, mono: true })}
                        {field('livechat_script', 'Live chat snippet', { textarea: true, rows: 4, mono: true })}
                    </CardBody>
                    <CardFooter>
                        <Button type="submit" icon={Save} loading={form.processing}>
                            Save settings
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}

function ImageInput({ form, name, label, current, accept = 'image/png,image/jpeg,image/webp' }) {
    const [preview, setPreview] = useState(current);
    return (
        <div>
            <p className="mb-1.5 text-sm font-medium text-slate-700">{label}</p>
            <label className="relative flex h-24 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 hover:border-indigo-400">
                {preview ? <img src={preview} alt="" className="max-h-20 max-w-[80%] object-contain" /> : <ImagePlus className="size-6 text-slate-400" />}
                <input
                    type="file"
                    accept={accept}
                    className="hidden"
                    onChange={(ev) => {
                        const f = ev.target.files?.[0];
                        if (f) {
                            form.setData((x) => ({ ...x, [name]: f, [`remove_${name}`]: false }));
                            setPreview(URL.createObjectURL(f));
                        }
                    }}
                />
            </label>
            {preview ? (
                <button
                    type="button"
                    onClick={() => {
                        setPreview(null);
                        form.setData((x) => ({ ...x, [name]: null, [`remove_${name}`]: true }));
                    }}
                    className="mt-1 inline-flex items-center gap-1 text-xs font-medium text-red-600"
                >
                    <X className="size-3" /> Remove
                </button>
            ) : null}
            <FieldError>{form.errors[name]}</FieldError>
        </div>
    );
}
