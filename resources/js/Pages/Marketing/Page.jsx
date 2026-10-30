import { Head } from '@inertiajs/react';
import { PageHero } from '@/Components/marketing/shared';

/** CMS page authored in the superadmin area (rich text or page-builder blocks). */
export default function CmsPage({ page }) {
    return (
        <>
            <Head title={page.meta_title}>
                <meta name="description" content={page.meta_description} head-key="description" />
            </Head>
            {page.full_width ? (
                <>
                    {page.show_title ? <PageHero title={page.title} /> : null}
                    <div dangerouslySetInnerHTML={{ __html: page.content ?? '' }} />
                </>
            ) : (
                <>
                    {page.show_title ? <PageHero title={page.title} /> : null}
                    <article className={page.show_title ? 'mx-auto max-w-3xl px-4 pb-24 sm:px-6' : 'mx-auto max-w-3xl px-4 pt-16 pb-24 sm:px-6'}>
                        <div className="prose-content text-base leading-relaxed text-slate-600" dangerouslySetInnerHTML={{ __html: page.content ?? '' }} />
                    </article>
                </>
            )}
        </>
    );
}
