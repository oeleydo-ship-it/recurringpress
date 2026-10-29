import { useForm } from '@inertiajs/react';
import { ExternalLink, LayoutTemplate, Save, Type } from 'lucide-react';
import BlockEditor from '@/Components/BlockEditor';
import AiPageWriter from '@/Components/ai/AiPageWriter';
import RichTextEditor from '@/Components/RichTextEditor';
import { Button, Card, CardBody, CardFooter, CardHeader, FieldError, FormField, Input, PageHeader, Segmented, Switch, Textarea } from '@/Components/ui';
import { uploadMedia } from '@/lib/media';
import { route } from '@/lib/route';


export default function PageForm({ page, ai }) {
    const editing = Boolean(page);
    const form = useForm({
        title: page?.title ?? '',
        slug: page?.slug ?? '',
        editor_mode: page?.editor_mode ?? 'classic',
        content: page?.content ?? '',
        blocks: page?.blocks ?? [],
        meta_title: page?.meta_title ?? '',
        meta_description: page?.meta_description ?? '',
        is_published: page?.is_published ?? false,
        show_title: page?.show_title ?? true,
        order: page?.order ?? 0,
    });
    const d = form.data;
    const e = form.errors;

    const submit = (ev) => {
        ev.preventDefault();
        if (editing) form.put(route('superadmin.pages.update', page.id), { preserveScroll: true });
        else form.post(route('superadmin.pages.store'));
    };

    return (
        <>
            <PageHeader
                title={editing ? `Edit “${page.title}”` : 'New page'}
                back={route('superadmin.pages.index')}
                actions={
                    <>
                        {editing && page.url ? (
                            <Button href={page.url} external target="_blank" variant="secondary" icon={ExternalLink}>
                                View
                            </Button>
                        ) : null}
                        <Button icon={Save} loading={form.processing} onClick={submit}>
                            Save
                        </Button>
                    </>
                }
            />
            <form onSubmit={submit} noValidate className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div className="min-w-0 space-y-6">
                    <AiPageWriter
                        ai={ai}
                        current={{ title: d.title, content: d.content }}
                        onApply={(draft) =>
                            form.setData((x) => ({
                                ...x,
                                title: draft.title,
                                // Keep an existing page's URL; only fill the slug for new pages.
                                slug: editing ? x.slug : draft.slug,
                                meta_title: draft.meta_title,
                                meta_description: draft.meta_description,
                                content: draft.content,
                                editor_mode: 'classic',
                            }))
                        }
                    />
                    <Card>
                        <CardBody className="space-y-4">
                            <FormField label="Title" required error={e.title}>
                                <Input value={d.title} onChange={(ev) => form.setData('title', ev.target.value)} invalid={Boolean(e.title)} className="text-lg font-medium" />
                            </FormField>
                            <div className="flex items-center justify-between gap-3">
                                <Segmented
                                    value={d.editor_mode}
                                    onChange={(v) => form.setData('editor_mode', v)}
                                    options={[
                                        { value: 'classic', label: 'Rich text', icon: Type },
                                        { value: 'blocks', label: 'Page builder', icon: LayoutTemplate },
                                    ]}
                                />
                                <span className="text-xs text-slate-500">The public page shows the selected mode.</span>
                            </div>
                        </CardBody>
                    </Card>
                    {d.editor_mode === 'classic' ? (
                        <div>
                            <RichTextEditor value={d.content} onChange={(html) => form.setData('content', html)} onUploadImage={uploadMedia} minHeight={420} />
                            <FieldError>{e.content}</FieldError>
                        </div>
                    ) : (
                        <div>
                            <BlockEditor blocks={d.blocks} onChange={(blocks) => form.setData('blocks', blocks)} onUploadImage={uploadMedia} />
                            <FieldError>{e.blocks}</FieldError>
                        </div>
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader title="Publishing" />
                        <CardBody className="space-y-5">
                            <Switch label="Published" checked={d.is_published} onChange={(v) => form.setData('is_published', v)} />
                            <Switch label="Show title on page" checked={d.show_title} onChange={(v) => form.setData('show_title', v)} />
                            <FormField label="URL slug" error={e.slug} hint="Leave blank to generate from the title.">
                                <Input prefix="/page/" value={d.slug} onChange={(ev) => form.setData('slug', ev.target.value)} className="font-mono" />
                            </FormField>
                            <FormField label="Sort order" error={e.order}>
                                <Input type="number" min="0" value={d.order} onChange={(ev) => form.setData('order', ev.target.value)} />
                            </FormField>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardHeader title="SEO" />
                        <CardBody className="space-y-4">
                            <FormField label="Meta title" error={e.meta_title}>
                                <Input value={d.meta_title} onChange={(ev) => form.setData('meta_title', ev.target.value)} placeholder={d.title} />
                            </FormField>
                            <FormField label="Meta description" error={e.meta_description} hint={`${d.meta_description.length}/255`}>
                                <Textarea rows={3} maxLength={255} value={d.meta_description} onChange={(ev) => form.setData('meta_description', ev.target.value)} />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Button type="submit" icon={Save} loading={form.processing} className="w-full">
                                {editing ? 'Save page' : 'Create page'}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </form>
        </>
    );
}
