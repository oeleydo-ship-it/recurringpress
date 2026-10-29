import { useForm } from '@inertiajs/react';
import { ExternalLink, ImagePlus, Save, X } from 'lucide-react';
import { useState } from 'react';
import RichTextEditor from '@/Components/RichTextEditor';
import { Button, Card, CardBody, CardFooter, CardHeader, FieldError, FormField, Input, PageHeader, Switch, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';
import { uploadMedia } from '@/lib/media';

export default function PostForm({ post }) {
    const localDate = (value) => {
        if (!value) return '';
        const date = new Date(value);
        return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    };
    const editing = Boolean(post);
    const [preview, setPreview] = useState(post?.image ?? null);
    const form = useForm({
        title: post?.title ?? '',
        slug: post?.slug ?? '',
        content: post?.content ?? '',
        excerpt: post?.excerpt ?? '',
        meta_title: post?.meta_title ?? '',
        meta_description: post?.meta_description ?? '',
        is_published: post?.is_published ?? false,
        published_at: post?.is_published ? '' : localDate(post?.published_at),
        featured_image: null,
        remove_image: false,
    });
    const d = form.data;
    const e = form.errors;

    const submit = (ev) => {
        ev?.preventDefault();
        form.transform((data) => ({ ...data, published_at: !data.is_published && data.published_at ? new Date(data.published_at).toISOString() : null, ...(editing ? { _method: 'put' } : {}) }));
        if (editing) {
            form.post(route('superadmin.posts.update', post.id), { forceFormData: true, preserveScroll: true, onSuccess: () => form.setData('featured_image', null) });
        } else {
            form.post(route('superadmin.posts.store'), { forceFormData: true });
        }
    };

    return (
        <>
            <PageHeader
                title={editing ? 'Edit post' : 'New post'}
                back={route('superadmin.posts.index')}
                actions={
                    <>
                        {editing && post.is_published ? (
                            <Button href={post.url} external target="_blank" variant="secondary" icon={ExternalLink}>
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
                <div className="min-w-0 space-y-4">
                    <Input value={d.title} onChange={(ev) => form.setData('title', ev.target.value)} placeholder="Post title" invalid={Boolean(e.title)} className="h-12 text-xl font-semibold" />
                    <FieldError>{e.title}</FieldError>
                    <RichTextEditor value={d.content} onChange={(html) => form.setData('content', html)} onUploadImage={uploadMedia} minHeight={480} />
                    <FieldError>{e.content}</FieldError>
                </div>
                <div className="space-y-6">
                    <Card>
                        <CardHeader title="Publishing" />
                        <CardBody className="space-y-5">
                            <Switch label="Published" checked={d.is_published} onChange={(v) => form.setData('is_published', v)} />
                            {!d.is_published && <FormField label="Schedule publication" error={e.published_at} hint="Your local time. Clear to keep this article as a draft.">
                                <Input type="datetime-local" value={d.published_at} onChange={(ev) => form.setData('published_at', ev.target.value)} />
                            </FormField>}
                            <FormField label="Slug" error={e.slug} hint="Leave blank to generate.">
                                <Input prefix="/blog/" value={d.slug} onChange={(ev) => form.setData('slug', ev.target.value)} className="font-mono" />
                            </FormField>
                            <FormField label="Excerpt" error={e.excerpt} hint="Shown on blog cards.">
                                <Textarea rows={3} maxLength={500} value={d.excerpt} onChange={(ev) => form.setData('excerpt', ev.target.value)} />
                            </FormField>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardHeader title="Featured image" />
                        <CardBody>
                            {preview ? (
                                <div className="relative overflow-hidden rounded-lg">
                                    <img src={preview} alt="" className="aspect-[16/9] w-full object-cover" />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setPreview(null);
                                            form.setData((x) => ({ ...x, featured_image: null, remove_image: true }));
                                        }}
                                        className="absolute top-2 right-2 rounded-full bg-white/90 p-1 text-slate-600 shadow hover:text-red-600"
                                        aria-label="Remove image"
                                    >
                                        <X className="size-4" />
                                    </button>
                                </div>
                            ) : (
                                <label className="flex aspect-[16/9] cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-200 text-sm text-slate-500 hover:border-indigo-400">
                                    <ImagePlus className="size-6" /> Upload image
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp,image/gif"
                                        className="hidden"
                                        onChange={(ev) => {
                                            const f = ev.target.files?.[0];
                                            if (f) {
                                                form.setData((x) => ({ ...x, featured_image: f, remove_image: false }));
                                                setPreview(URL.createObjectURL(f));
                                            }
                                        }}
                                    />
                                </label>
                            )}
                            <FieldError>{e.featured_image}</FieldError>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardHeader title="SEO" />
                        <CardBody className="space-y-4">
                            <FormField label="Meta title" error={e.meta_title}>
                                <Input value={d.meta_title} onChange={(ev) => form.setData('meta_title', ev.target.value)} placeholder={d.title} />
                            </FormField>
                            <FormField label="Meta description" error={e.meta_description}>
                                <Textarea rows={3} maxLength={255} value={d.meta_description} onChange={(ev) => form.setData('meta_description', ev.target.value)} />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Button type="submit" icon={Save} loading={form.processing} className="w-full">
                                {editing ? 'Save post' : 'Create post'}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </form>
        </>
    );
}
