import { Head, Link } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';
import { CtaBand, PageHero } from '@/Components/marketing/shared';
import { EmptyState, Pagination } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate } from '@/lib/utils';

export default function BlogIndex({ posts }) {
    return (
        <>
            <Head title="Blog" />
            <PageHero eyebrow="Blog & updates" title="Insights for independent businesses" subtitle="Product updates and tips on invoicing, getting paid and running a healthier business." />
            <section className="mx-auto max-w-6xl px-4 pb-24 sm:px-6 lg:px-8">
                {posts.data.length ? (
                    <>
                        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                            {posts.data.map((p) => (
                                <PostCard key={p.slug} post={p} />
                            ))}
                        </div>
                        <Pagination paginator={posts} className="mt-10 rounded-xl border-0 bg-white ring-1 ring-slate-200" />
                    </>
                ) : (
                    <EmptyState icon={Newspaper} title="No posts yet" description="Check back soon." className="mt-12" />
                )}
            </section>
            <CtaBand />
        </>
    );
}

export function PostCard({ post }) {
    return (
        <Link href={route('blog.show', post.slug)} className="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-900/5 hover:ring-blue-200">
            <div className="aspect-[16/9] overflow-hidden bg-gradient-to-br from-blue-100 to-blue-50">
                {post.image ? <img src={post.image} alt="" loading="lazy" className="size-full object-cover transition duration-500 group-hover:scale-105" /> : null}
            </div>
            <div className="flex flex-1 flex-col p-6">
                <p className="text-xs font-medium text-blue-600">
                    {formatDate(post.published_at)} · {post.reading_minutes} min read
                </p>
                <h3 className="mt-2 text-lg font-semibold text-balance text-slate-900 group-hover:text-blue-600">{post.title}</h3>
                <p className="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-slate-600">{post.excerpt}</p>
                {post.author ? <p className="mt-4 text-xs font-medium text-slate-500">By {post.author}</p> : null}
            </div>
        </Link>
    );
}
