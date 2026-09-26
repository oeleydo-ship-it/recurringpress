import { Head } from '@inertiajs/react';

/** CMS page authored in the superadmin area. */
export default function CmsPage({ page }) {
    return (
        <>
            <Head title={page.meta_title}>
                <meta name="description" content={page.meta_description} head-key="description" />
            </Head>
            <article className="mx-auto max-w-3xl px-4 pt-16 pb-24 sm:px-6">
                {page.show_title ? <h1 className="mb-10 text-4xl font-semibold tracking-tight text-balance text-slate-900">{page.title}</h1> : null}
                <div className="prose-content text-lg" dangerouslySetInnerHTML={{ __html: page.content ?? '' }} />
            </article>
        </>
    );
}
