import { router } from '@inertiajs/react';
import { ExternalLink, Newspaper, Pencil, Plus, Trash2 } from 'lucide-react';
import { Badge, Button, Card, Dropdown, EmptyState, PageHeader, Pagination, Table, TBody, TD, TH, THead, TR, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate } from '@/lib/utils';

export default function SuperadminPosts({ posts }) {
    const confirm = useConfirm();
    const destroy = async (p) => {
        if (await confirm({ title: `Delete “${p.title}”?`, confirmText: 'Delete post' })) {
            router.delete(route('superadmin.posts.destroy', p.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader
                title="Blog posts"
                actions={
                    <Button href={route('superadmin.posts.create')} icon={Plus}>
                        New post
                    </Button>
                }
            />
            <Card>
                {posts.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Post</TH>
                                <TH>Status</TH>
                                <TH className="hidden md:table-cell">Published</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {posts.data.map((p) => (
                                    <TR key={p.id} href={route('superadmin.posts.edit', p.id)}>
                                        <TD>
                                            <div className="flex items-center gap-3">
                                                <span className="size-12 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-indigo-100 to-sky-100">
                                                    {p.image ? <img src={p.image} alt="" className="size-full object-cover" /> : null}
                                                </span>
                                                <div className="min-w-0">
                                                    <span className="block max-w-md truncate font-medium text-slate-900">{p.title}</span>
                                                    <span className="text-xs text-slate-500">{p.author ? `By ${p.author}` : ''}</span>
                                                </div>
                                            </div>
                                        </TD>
                                        <TD>{p.is_published ? <Badge tone="green">Published</Badge> : <Badge>Draft</Badge>}</TD>
                                        <TD className="hidden text-slate-500 md:table-cell">{p.published_at ? formatDate(p.published_at) : '—'}</TD>
                                        <TD>
                                            <Dropdown
                                                items={[
                                                    { label: 'Edit', icon: Pencil, href: route('superadmin.posts.edit', p.id) },
                                                    { label: 'View live', icon: ExternalLink, href: p.url, external: true, hidden: !p.is_published },
                                                    { separator: true },
                                                    { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(p) },
                                                ]}
                                            />
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={posts} />
                    </>
                ) : (
                    <EmptyState icon={Newspaper} title="No posts yet" />
                )}
            </Card>
        </>
    );
}
