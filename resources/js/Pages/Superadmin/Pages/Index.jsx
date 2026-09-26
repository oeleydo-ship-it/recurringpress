import { router } from '@inertiajs/react';
import { ExternalLink, FileStack, Pencil, Plus, Trash2 } from 'lucide-react';
import { Badge, Button, Card, Dropdown, EmptyState, PageHeader, Table, TBody, TD, TH, THead, TR, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate } from '@/lib/utils';

export default function SuperadminPages({ pages }) {
    const confirm = useConfirm();
    const destroy = async (p) => {
        if (await confirm({ title: `Delete “${p.title}”?`, message: 'Menu links to this page will stop working.', confirmText: 'Delete page' })) {
            router.delete(route('superadmin.pages.destroy', p.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader
                title="Pages"
                description="Legal pages, about and any custom content — served at /page/slug."
                actions={
                    <Button href={route('superadmin.pages.create')} icon={Plus}>
                        New page
                    </Button>
                }
            />
            <Card>
                {pages.length ? (
                    <Table>
                        <THead>
                            <TH>Page</TH>
                            <TH className="hidden sm:table-cell">Editor</TH>
                            <TH>Status</TH>
                            <TH className="hidden md:table-cell">Updated</TH>
                            <TH className="w-10" />
                        </THead>
                        <TBody>
                            {pages.map((p) => (
                                <TR key={p.id} href={route('superadmin.pages.edit', p.id)}>
                                    <TD>
                                        <span className="font-medium text-slate-900">{p.title}</span>
                                        <span className="block font-mono text-xs text-slate-500">/page/{p.slug}</span>
                                    </TD>
                                    <TD className="hidden sm:table-cell">
                                        <Badge tone={p.editor_mode === 'blocks' ? 'purple' : 'gray'}>{p.editor_mode === 'blocks' ? 'Page builder' : 'Rich text'}</Badge>
                                    </TD>
                                    <TD>{p.is_published ? <Badge tone="green">Published</Badge> : <Badge>Draft</Badge>}</TD>
                                    <TD className="hidden text-slate-500 md:table-cell">{formatDate(p.updated_at)}</TD>
                                    <TD>
                                        <Dropdown
                                            items={[
                                                { label: 'Edit', icon: Pencil, href: route('superadmin.pages.edit', p.id) },
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
                ) : (
                    <EmptyState icon={FileStack} title="No pages yet" />
                )}
            </Card>
        </>
    );
}
