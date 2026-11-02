import { useForm } from '@inertiajs/react';
import { ExternalLink, ImagePlus, Save, X } from 'lucide-react';
import { useState } from 'react';
import { Button, Card, CardBody, CardFooter, CardHeader, FieldError, FormField, Input, PageHeader, Switch, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';

export default function GeneralSettings({ settings, images, webmaster }) {
    const form = useForm({
        ...Object.fromEntries(Object.entries(settings).map(([k, v]) => [k, v ?? ''])),
        hide_site_name: settings.hide_site_name,
        email_verification_enabled: settings.email_verification_enabled,
        seo_discourage_indexing: settings.seo_discourage_indexing,
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
                <Textarea rows={props.rows ?? 3} value={d[name]} placeholder={props.placeholder} onChange={(ev) => form.setData(name, ev.target.value)} className={props.mono ? 'font-mono text-xs' : ''} />
            ) : (
                <Input type={props.type} value={d[name]} placeholder={props.placeholder} onChange={(ev) => form.setData(name, ev.target.value)} invalid={Boolean(e[name])} />
            )}
        </FormField>
    );

    const submit = (ev) => {
        ev.preventDefault();
        form.transform((x) => ({ ...x, _method: 'put' }));
        form.post(route('superadmin.settings.general.update'), {
            forceFormData: true,
            preserveScroll: true,
            // The server reduces pasted meta tags to the bare code; show what was stored.
            onSuccess: (page) =>
                form.setData((x) => ({
                    ...x,
                    seo_google_verification: page.props.settings.seo_google_verification ?? '',
                    seo_bing_verification: page.props.settings.seo_bing_verification ?? '',
                    seo_twitter_site: page.props.settings.seo_twitter_site ?? '',
                })),
        });
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
                        <div className="grid gap-4 sm:grid-cols-2">
                            <ImageInput form={form} name="site_logo" label="Logo" current={images.site_logo} />
                            <ImageInput form={form} name="site_favicon" label="Favicon" current={images.site_favicon} accept="image/png,image/x-icon,image/jpeg,image/webp" />
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
                        <div className="grid gap-4 sm:grid-cols-2">
                            <ImageInput form={form} name="seo_og_image" label="Default social share image" current={images.seo_og_image} />
                            {field('seo_twitter_site', 'X (Twitter) handle', { placeholder: '@yourbrand', hint: 'Shown on link previews.' })}
                        </div>
                    </CardBody>
                </Card>
                <Card>
                    <CardHeader title="Webmaster verification" description="Added to the public pages so you can claim this site. Paste the code or the whole meta tag." />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        {field('seo_google_verification', 'Google Search Console', { hint: 'HTML tag method, from Settings → Ownership verification.' })}
                        {field('seo_bing_verification', 'Bing Webmaster Tools', { hint: 'HTML meta tag method, from Add a site.' })}
                    </CardBody>
                </Card>
                <Card>
                    <CardHeader title="Robots & sitemap" description="What search engines may crawl. Tenant domains always block crawlers and have no sitemap." />
                    <CardBody className="grid gap-4">
                        <div className="grid gap-2 sm:grid-cols-2">
                            <CrawlerFile label="Sitemap" url={webmaster.sitemap_url} />
                            <CrawlerFile label="Robots" url={webmaster.robots_url} />
                        </div>
                        <Switch
                            label="Discourage search engines from indexing this site"
                            description="Blocks all crawling in robots.txt, empties the sitemap and adds noindex to public pages. For staging or pre-launch sites."
                            checked={d.seo_discourage_indexing}
                            onChange={(v) => form.setData('seo_discourage_indexing', v)}
                        />
                        {field('seo_robots_txt', 'Custom robots.txt', {
                            textarea: true,
                            rows: 8,
                            mono: true,
                            placeholder: webmaster.default_robots,
                            hint: d.seo_discourage_indexing ? 'Not used while indexing is discouraged.' : 'Leave empty to use the default shown here. A Sitemap line is added if yours has none.',
                        })}
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

function CrawlerFile({ label, url }) {
    return (
        <div className="min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
            <p className="text-xs font-medium text-slate-500">{label}</p>
            <a href={url} target="_blank" rel="noreferrer" className="inline-flex max-w-full items-center gap-1 text-sm font-medium text-indigo-600 hover:underline">
                <span className="truncate">{url}</span>
                <ExternalLink className="size-3.5 shrink-0" />
            </a>
        </div>
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
