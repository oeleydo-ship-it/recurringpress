import { Head } from '@inertiajs/react';

/** CMS page authored in the superadmin area (rich text or page-builder blocks). */
export default function CmsPage({ page }) {
    return (
        <>
            <Head title={page.meta_title}>
                <meta name="description" content={page.meta_description} head-key="description" />
            </Head>
            {page.full_width ? (
                <>
                    {page.show_title ? (
                        <div className="mx-auto max-w-4xl px-4 pt-12 sm:px-6">
                            <h1 className="text-4xl font-semibold tracking-tight text-balance text-slate-900">{page.title}</h1>
                        </div>
                    ) : null}
                    <div dangerouslySetInnerHTML={{ __html: page.content ?? '' }} />
                </>
            ) : (
                <article className="mx-auto max-w-3xl px-4 pt-16 pb-24 sm:px-6">
                    {page.show_title ? <h1 className="mb-10 text-4xl font-semibold tracking-tight text-balance text-slate-900">{page.title}</h1> : null}
                    <div className="prose-content text-lg" dangerouslySetInnerHTML={{ __html: page.content ?? '' }} />
                </article>
            )}
        </>
    );
}
