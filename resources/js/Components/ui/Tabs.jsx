import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Client-side tabs. `tabs`: [{ key, label, icon, content }]. Remembers selection in the URL hash.
 */
export default function Tabs({ tabs, defaultKey, vertical = false, className, onChange }) {
    const [index, setIndex] = useState(() => Math.max(0, tabs.findIndex((t) => t.key === defaultKey)));

    // Apply the URL hash after mount so server and client render the same first frame.
    useEffect(() => {
        const i = tabs.findIndex((t) => t.key === window.location.hash.slice(1));
        if (i >= 0) setIndex(i);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <TabGroup
            selectedIndex={index}
            vertical={vertical}
            onChange={(i) => {
                setIndex(i);
                history.replaceState(history.state, '', `#${tabs[i].key}`);
                onChange?.(tabs[i].key);
            }}
            className={cn(vertical ? 'grid gap-6 lg:grid-cols-[220px_1fr]' : '', className)}
        >
            <TabList
                className={cn(
                    vertical
                        ? 'flex gap-1 overflow-x-auto scrollbar-thin lg:flex-col lg:overflow-visible'
                        : 'mb-6 flex gap-1 overflow-x-auto border-b border-slate-200 scrollbar-thin',
                )}
            >
                {tabs.map((t) => {
                    const Icon = t.icon;
                    return (
                        <Tab
                            key={t.key}
                            className={cn(
                                'inline-flex items-center gap-2 text-sm font-medium whitespace-nowrap transition-colors focus:outline-none',
                                vertical
                                    ? 'rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-white hover:text-slate-900 data-selected:bg-white data-selected:text-brand-700 data-selected:shadow-card data-selected:ring-1 data-selected:ring-slate-900/5'
                                    : '-mb-px border-b-2 border-transparent px-3 py-2.5 text-slate-500 hover:text-slate-800 data-selected:border-brand-600 data-selected:text-brand-700',
                            )}
                        >
                            {Icon ? <Icon className="size-4" /> : null}
                            {t.label}
                        </Tab>
                    );
                })}
            </TabList>
            <TabPanels>
                {tabs.map((t) => (
                    <TabPanel key={t.key} unmount={false} className="focus:outline-none">
                        {t.content}
                    </TabPanel>
                ))}
            </TabPanels>
        </TabGroup>
    );
}
