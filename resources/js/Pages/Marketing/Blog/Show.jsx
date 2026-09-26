import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { CtaBand } from '@/Components/marketing/shared';
import { route } from '@/lib/route';
import { formatDate } from '@/lib/utils';
import { PostCard } from './Index';

export default function BlogShow({ post, related }) {
    return (
        <>
            <Head title={post.meta_title}>
                <meta name="description" content={post.meta_description} head-key="description" />
                <meta property="og:type" content="article" />
                <meta property="og:title" content={post.meta_title} />
                {post.image ? <meta property="og:image" content={post.image} head-key="og:image" /> : null}
                <meta property="article:published_time" content={post.published_at} />
            </Head>
            <article className="mx-auto max-w-3xl px-4 pt-12 pb-16 sm:px-6">
                <Link href={route('blog.index')} className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
                    <ArrowLeft className="size-4" /> All posts
                </Link>
                <p className="mt-8 text-sm text-slate-500">
                    {formatDate(post.published_at)} · {post.reading_minutes} min read{post.author ? ` · ${post.author}` : ''}
                </p>
                <h1 className="mt-3 text-4xl leading-tight font-semibold tracking-tight text-balance text-slate-900 sm:text-5xl">{post.title}</h1>
                {post.image ? <img src={post.image} alt="" className="mt-10 aspect-[16/9] w-full rounded-2xl object-cover" /> : null}
                <div className="prose-content mt-10 text-lg" dangerouslySetInnerHTML={{ __html: post.content ?? '' }} />
            </article>
            {related.length ? (
                <section className="border-t border-slate-100 bg-slate-50 py-16">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <h2 className="text-xl font-semibold text-slate-900">Keep reading</h2>
                        <div className="mt-8 grid gap-8 md:grid-cols-3">
                            {related.map((p) => (
                                <PostCard key={p.slug} post={p} />
                            ))}
                        </div>
                    </div>
                </section>
            ) : null}
            <CtaBand />
        </>
    );
}
