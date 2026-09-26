import { Menu, MenuButton, MenuItem, MenuItems, MenuSeparator } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Dropdown menu. `items`: [{ label, icon, href, method, onClick, danger, external, hidden, separator }]
 */
export default function Dropdown({ trigger, items = [], align = 'right', width = 'w-52', className }) {
    const visible = items.filter((i) => i && !i.hidden);
    return (
        <Menu as="div" className={cn('relative inline-block text-left', className)}>
            {trigger ? (
                <MenuButton as="div">{trigger}</MenuButton>
            ) : (
                <MenuButton className="flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-brand-600" aria-label="More actions">
                    <MoreHorizontal className="size-5" />
                </MenuButton>
            )}
            <MenuItems
                transition
                anchor={align === 'right' ? 'bottom end' : 'bottom start'}
                className={cn(
                    'z-50 mt-1 origin-top rounded-xl bg-white p-1 shadow-pop ring-1 ring-slate-900/5 transition duration-100 ease-out [--anchor-gap:4px] focus:outline-none data-closed:scale-95 data-closed:opacity-0',
                    width,
                )}
            >
                {visible.map((item, idx) =>
                    item.separator ? (
                        <MenuSeparator key={`sep-${idx}`} className="my-1 h-px bg-slate-100" />
                    ) : (
                        <MenuItem key={item.label + idx}>
                            {({ focus }) => {
                                const Icon = item.icon;
                                const cls = cn(
                                    'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm',
                                    item.danger ? 'text-red-600' : 'text-slate-700',
                                    focus && (item.danger ? 'bg-red-50' : 'bg-slate-100 text-slate-900'),
                                );
                                const inner = (
                                    <>
                                        {Icon ? <Icon className={cn('size-4', item.danger ? 'text-red-500' : 'text-slate-400')} /> : null}
                                        {item.label}
                                    </>
                                );
                                if (item.href && (item.external || item.download)) {
                                    return (
                                        <a href={item.href} className={cls} target={item.external ? '_blank' : undefined} rel="noreferrer" download={item.download}>
                                            {inner}
                                        </a>
                                    );
                                }
                                if (item.href) {
                                    return (
                                        <Link href={item.href} method={item.method} as={item.method && item.method !== 'get' ? 'button' : 'a'} className={cls}>
                                            {inner}
                                        </Link>
                                    );
                                }
                                return (
                                    <button type="button" onClick={item.onClick} className={cls}>
                                        {inner}
                                    </button>
                                );
                            }}
                        </MenuItem>
                    ),
                )}
            </MenuItems>
        </Menu>
    );
}
