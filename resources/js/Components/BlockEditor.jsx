import {
    AlignLeft,
    ArrowDown,
    ArrowUp,
    ChevronDown,
    Columns2,
    Copy,
    Heading,
    Image as ImageIcon,
    LayoutGrid,
    Megaphone,
    Minus,
    MousePointerClick,
    MoveVertical,
    Plus,
    Quote,
    Star,
    Tags,
    Trash2,
    Upload,
    Video,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button, FormField, Input, Select, Switch, Textarea } from '@/Components/ui';
import { cn, titleCase } from '@/lib/utils';

/* Block catalogue — defaults mirror the original page builder so saved pages stay compatible. */
export const BLOCK_TYPES = {
    hero: { label: 'Hero', icon: Star, data: { title: 'Welcome to Our Site', subtitle: 'Your success story starts here', buttonText: 'Get Started', buttonUrl: '#', bgColor: '#0ea5e9', textColor: '#ffffff', spacing: { top: 16, bottom: 16 } } },
    heading: { label: 'Heading', icon: Heading, data: { text: 'Section Heading', level: 'h2', align: 'center', fontFamily: 'Inter', fontSize: '', fontWeight: 'bold', color: '#111827', letterSpacing: 0, lineHeight: '1.25', uppercase: false, italic: false, underline: false, spacing: { top: 8, bottom: 8 } } },
    text: { label: 'Text', icon: AlignLeft, data: { content: 'Enter your text content here...', align: 'left', fontFamily: 'Inter', fontSize: 16, fontWeight: 'normal', color: '#374151', lineHeight: '1.75', letterSpacing: 0, maxWidth: '', spacing: { top: 4, bottom: 4 } } },
    image: { label: 'Image', icon: ImageIcon, data: { url: '', alt: '', caption: '', align: 'center', width: '100', spacing: { top: 4, bottom: 4 } } },
    button: { label: 'Button', icon: MousePointerClick, data: { text: 'Click Here', url: '#', style: 'primary', align: 'center', spacing: { top: 4, bottom: 4 } } },
    features: {
        label: 'Features',
        icon: LayoutGrid,
        data: {
            columns: 3,
            items: [
                { icon: 'fas fa-rocket', title: 'Feature 1', description: 'Description here' },
                { icon: 'fas fa-shield-alt', title: 'Feature 2', description: 'Description here' },
                { icon: 'fas fa-heart', title: 'Feature 3', description: 'Description here' },
            ],
            spacing: { top: 12, bottom: 12 },
        },
    },
    cta: { label: 'Call to action', icon: Megaphone, data: { title: 'Ready to get started?', subtitle: 'Join thousands of happy customers', buttonText: 'Sign Up Now', buttonUrl: '#', bgColor: '#1e293b', spacing: { top: 12, bottom: 12 } } },
    testimonial: { label: 'Testimonial', icon: Quote, data: { quote: 'This product changed my life!', author: 'John Doe', role: 'CEO, Company', avatar: '', spacing: { top: 8, bottom: 8 } } },
    pricing: {
        label: 'Pricing',
        icon: Tags,
        data: {
            plans: [
                { name: 'Basic', price: '9', period: 'month', features: ['Feature 1', 'Feature 2'], buttonText: 'Choose', buttonUrl: '#' },
                { name: 'Pro', price: '29', period: 'month', features: ['All Basic features', 'Feature 3', 'Feature 4'], buttonText: 'Choose', buttonUrl: '#', popular: true },
            ],
            spacing: { top: 12, bottom: 12 },
        },
    },
    video: { label: 'Video', icon: Video, data: { url: '', type: 'youtube', spacing: { top: 8, bottom: 8 } } },
    columns: { label: 'Two columns', icon: Columns2, data: { gap: 24, left: [], right: [], spacing: { top: 8, bottom: 8 } } },
    spacer: { label: 'Spacer', icon: MoveVertical, data: { height: 40 } },
    divider: { label: 'Divider', icon: Minus, data: { style: 'solid', color: '#e5e7eb', spacing: { top: 4, bottom: 4 } } },
};

const COLUMN_ITEMS = {
    heading: { text: 'Heading', level: 'h3', align: 'left' },
    text: { content: 'Text content...', align: 'left' },
    image: { url: '', alt: '', width: '100' },
    button: { text: 'Click Here', url: '#', style: 'primary', size: 'md', fullWidth: false },
    list: { items: 'Item 1\nItem 2\nItem 3', style: 'bullet', iconColor: '#0ea5e9' },
    icon: { icon: 'fas fa-star', iconColor: '#0ea5e9', title: 'Feature Title', description: 'Description text here' },
    video: { url: '', aspectRatio: '16:9' },
    spacer: { height: 20, bgColor: 'transparent' },
    divider: {},
};

// Select options for well-known keys (context = block/item type).
function optionsFor(key, context) {
    if (key === 'align') return { left: 'Left', center: 'Center', right: 'Right' };
    if (key === 'level') return { h1: 'H1', h2: 'H2', h3: 'H3', h4: 'H4' };
    if (key === 'fontWeight') return { normal: 'Normal', medium: 'Medium', semibold: 'Semibold', bold: 'Bold' };
    if (key === 'fontFamily') return { Inter: 'Inter', Poppins: 'Poppins', Roboto: 'Roboto', 'Open Sans': 'Open Sans', Lato: 'Lato', Nunito: 'Nunito', Georgia: 'Georgia' };
    if (key === 'width') return { 25: '25%', 50: '50%', 75: '75%', 100: '100%' };
    if (key === 'period') return { month: 'Month', year: 'Year', once: 'One-time' };
    if (key === 'aspectRatio') return { '16:9': '16:9', '4:3': '4:3', '1:1': '1:1' };
    if (key === 'size') return { sm: 'Small', md: 'Medium', lg: 'Large' };
    if (key === 'type' && context === 'video') return { youtube: 'YouTube', vimeo: 'Vimeo', mp4: 'MP4 file' };
    if (key === 'style' && context === 'divider') return { solid: 'Solid', dashed: 'Dashed', dotted: 'Dotted' };
    if (key === 'style' && context === 'list') return { bullet: 'Bullets', number: 'Numbers', check: 'Checkmarks' };
    if (key === 'style') return { primary: 'Primary', secondary: 'Secondary', outline: 'Outline' };
    return null;
}

const LONG_TEXT = ['content', 'description', 'quote', 'subtitle', 'caption'];
const IMAGE_KEYS = ['url', 'avatar'];
const HIDDEN = ['id', 'uploading'];
const clone = (v) => JSON.parse(JSON.stringify(v));
let uid = Date.now();

export function newBlock(type) {
    return { id: uid++, type, data: clone(BLOCK_TYPES[type]?.data ?? {}) };
}

/* ------------------------------------------------------------------ */

export default function BlockEditor({ blocks, onChange, onUploadImage }) {
    const [open, setOpen] = useState(() => new Set(blocks.slice(0, 1).map((b) => b.id)));
    const toggle = (id) => setOpen((s) => new Set(s.has(id) ? [...s].filter((x) => x !== id) : [...s, id]));

    const update = (i, data) => onChange(blocks.map((b, idx) => (idx === i ? { ...b, data } : b)));
    const move = (i, d) => {
        const to = i + d;
        if (to < 0 || to >= blocks.length) return;
        const next = [...blocks];
        [next[i], next[to]] = [next[to], next[i]];
        onChange(next);
    };
    const add = (type) => {
        const b = newBlock(type);
        onChange([...blocks, b]);
        setOpen((s) => new Set([...s, b.id]));
    };

    return (
        <div className="space-y-3">
            {blocks.length === 0 ? <p className="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">No blocks yet — add one below.</p> : null}
            {blocks.map((block, i) => {
                const meta = BLOCK_TYPES[block.type] ?? { label: titleCase(block.type), icon: LayoutGrid };
                const Icon = meta.icon;
                const expanded = open.has(block.id);
                return (
                    <div key={block.id} className="rounded-xl bg-white ring-1 ring-slate-200">
                        <div className="flex items-center gap-2 px-3 py-2">
                            <button type="button" onClick={() => toggle(block.id)} className="flex flex-1 items-center gap-3 text-left">
                                <span className="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                    <Icon className="size-4" />
                                </span>
                                <span className="min-w-0">
                                    <span className="block text-sm font-medium text-slate-900">{meta.label}</span>
                                    <span className="block truncate text-xs text-slate-500">{summary(block)}</span>
                                </span>
                                <ChevronDown className={cn('ml-auto size-4 text-slate-400 transition', expanded && 'rotate-180')} />
                            </button>
                            <IconBtn icon={ArrowUp} label="Move up" onClick={() => move(i, -1)} disabled={i === 0} />
                            <IconBtn icon={ArrowDown} label="Move down" onClick={() => move(i, 1)} disabled={i === blocks.length - 1} />
                            <IconBtn icon={Copy} label="Duplicate" onClick={() => onChange([...blocks.slice(0, i + 1), { ...clone(block), id: uid++ }, ...blocks.slice(i + 1)])} />
                            <IconBtn icon={Trash2} label="Delete" danger onClick={() => onChange(blocks.filter((_, idx) => idx !== i))} />
                        </div>
                        {expanded ? (
                            <div className="border-t border-slate-100 p-4">
                                <DataFields data={block.data} onChange={(d) => update(i, d)} context={block.type} onUploadImage={onUploadImage} />
                            </div>
                        ) : null}
                    </div>
                );
            })}

            <div className="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
                <p className="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">Add block</p>
                <div className="flex flex-wrap gap-2">
                    {Object.entries(BLOCK_TYPES).map(([type, meta]) => {
                        const Icon = meta.icon;
                        return (
                            <button key={type} type="button" onClick={() => add(type)} className="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200 hover:ring-brand-400">
                                <Icon className="size-3.5" /> {meta.label}
                            </button>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

function summary(block) {
    const d = block.data ?? {};
    const text = d.title || d.text || d.content || d.quote || d.url || (d.items && `${d.items.length} items`) || (d.plans && `${d.plans.length} plans`) || '';
    return String(text).replace(/<[^>]+>/g, '').slice(0, 80);
}

function IconBtn({ icon: Icon, label, onClick, disabled, danger }) {
    return (
        <button type="button" onClick={onClick} disabled={disabled} aria-label={label} title={label} className={cn('rounded-md p-1.5 text-slate-400 disabled:opacity-30', danger ? 'hover:bg-red-50 hover:text-red-600' : 'hover:bg-slate-100 hover:text-slate-700')}>
            <Icon className="size-4" />
        </button>
    );
}

/* Renders an editor for every key of a data object, inferring the control from key + value. */
function DataFields({ data, onChange, context, onUploadImage }) {
    const set = (key, value) => onChange({ ...data, [key]: value });
    const entries = Object.entries(data ?? {}).filter(([k]) => !HIDDEN.includes(k));

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {entries.map(([key, value]) => (
                <Field key={key} name={key} value={value} onChange={(v) => set(key, v)} context={context} onUploadImage={onUploadImage} />
            ))}
        </div>
    );
}

function Field({ name, value, onChange, context, onUploadImage }) {
    const label = titleCase(name.replace(/([A-Z])/g, ' $1'));
    const opts = optionsFor(name, context);
    const wide = LONG_TEXT.includes(name) || Array.isArray(value) || (name === 'items' && typeof value === 'string');

    if ((name === 'left' || name === 'right') && Array.isArray(value)) {
        return <ColumnItems label={`${label} column`} items={value} onChange={onChange} onUploadImage={onUploadImage} />;
    }
    if (name === 'spacing' && value && typeof value === 'object') {
        return (
            <FormField label="Spacing (top / bottom)">
                <div className="flex gap-2">
                    <Input type="number" min="0" value={value.top ?? 0} onChange={(e) => onChange({ ...value, top: Number(e.target.value) })} aria-label="Top spacing" />
                    <Input type="number" min="0" value={value.bottom ?? 0} onChange={(e) => onChange({ ...value, bottom: Number(e.target.value) })} aria-label="Bottom spacing" />
                </div>
            </FormField>
        );
    }
    if (Array.isArray(value) && value.every((v) => typeof v === 'string')) {
        return (
            <FormField label={`${label} (one per line)`} className="sm:col-span-2">
                <Textarea rows={3} value={value.join('\n')} onChange={(e) => onChange(e.target.value.split('\n'))} />
            </FormField>
        );
    }
    if (Array.isArray(value)) {
        return <RepeatableList label={label} items={value} onChange={onChange} context={name} onUploadImage={onUploadImage} />;
    }
    if (typeof value === 'boolean') {
        return <Switch label={label} checked={value} onChange={onChange} className="self-end" />;
    }
    if (opts) {
        return (
            <FormField label={label}>
                <Select value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} options={opts} />
            </FormField>
        );
    }
    if (/color$/i.test(name)) {
        const safe = /^#[0-9a-f]{6}$/i.test(value) ? value : '#000000';
        return (
            <FormField label={label}>
                <div className="flex gap-2">
                    <label className="relative size-9 shrink-0 cursor-pointer overflow-hidden rounded-lg ring-1 ring-slate-300" style={{ background: value }}>
                        <input type="color" value={safe} onChange={(e) => onChange(e.target.value)} className="absolute inset-0 cursor-pointer opacity-0" />
                    </label>
                    <Input value={value ?? ''} onChange={(e) => onChange(e.target.value)} className="font-mono" />
                </div>
            </FormField>
        );
    }
    if (IMAGE_KEYS.includes(name) && ['image', 'testimonial', 'column-image'].includes(context)) {
        return <ImageField label={label} value={value} onChange={onChange} onUploadImage={onUploadImage} />;
    }
    if (typeof value === 'number') {
        return (
            <FormField label={label}>
                <Input type="number" value={value} onChange={(e) => onChange(e.target.value === '' ? 0 : Number(e.target.value))} />
            </FormField>
        );
    }
    if (wide) {
        return (
            <FormField label={label} className="sm:col-span-2" hint={name === 'content' ? 'HTML allowed.' : undefined}>
                <Textarea rows={name === 'content' ? 5 : 3} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />
            </FormField>
        );
    }
    return (
        <FormField label={label} hint={name === 'icon' ? 'Font Awesome class, e.g. fas fa-rocket' : undefined}>
            <Input value={value ?? ''} onChange={(e) => onChange(e.target.value)} />
        </FormField>
    );
}

function ImageField({ label, value, onChange, onUploadImage }) {
    const [busy, setBusy] = useState(false);
    return (
        <FormField label={label} className="sm:col-span-2">
            <div className="flex items-center gap-3">
                {value ? <img src={value} alt="" className="size-14 shrink-0 rounded-lg object-cover ring-1 ring-slate-200" /> : null}
                <Input value={value ?? ''} onChange={(e) => onChange(e.target.value)} placeholder="https://… or upload" />
                {onUploadImage ? (
                    <label className="inline-flex h-9 shrink-0 cursor-pointer items-center gap-1.5 rounded-lg bg-white px-3 text-sm font-medium text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">
                        <Upload className="size-4" /> {busy ? 'Uploading…' : 'Upload'}
                        <input
                            type="file"
                            accept="image/png,image/jpeg,image/webp,image/gif"
                            className="hidden"
                            onChange={async (e) => {
                                const f = e.target.files?.[0];
                                e.target.value = '';
                                if (!f) return;
                                setBusy(true);
                                try {
                                    onChange(await onUploadImage(f));
                                } catch (err) {
                                    toast.error(err.message || 'Upload failed');
                                } finally {
                                    setBusy(false);
                                }
                            }}
                        />
                    </label>
                ) : null}
            </div>
        </FormField>
    );
}

function RepeatableList({ label, items, onChange, context, onUploadImage }) {
    const add = () => onChange([...items, items.length ? clone(items[items.length - 1]) : {}]);
    return (
        <div className="space-y-3 sm:col-span-2">
            <p className="text-sm font-medium text-slate-700">{label}</p>
            {items.map((item, i) => (
                <div key={i} className="relative rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                    <button type="button" onClick={() => onChange(items.filter((_, idx) => idx !== i))} className="absolute top-2 right-2 rounded p-1 text-slate-400 hover:text-red-600" aria-label="Remove item">
                        <Trash2 className="size-4" />
                    </button>
                    <DataFields data={item} onChange={(d) => onChange(items.map((x, idx) => (idx === i ? d : x)))} context={context === 'plans' ? 'plan' : 'item'} onUploadImage={onUploadImage} />
                </div>
            ))}
            <Button variant="soft" size="xs" icon={Plus} onClick={add}>
                Add {label.toLowerCase().replace(/s$/, '')}
            </Button>
        </div>
    );
}

function ColumnItems({ label, items, onChange, onUploadImage }) {
    const [type, setType] = useState('text');
    return (
        <div className="space-y-3 rounded-xl bg-slate-50/60 p-3 ring-1 ring-slate-200">
            <p className="text-sm font-medium text-slate-700">{label}</p>
            {items.map((item, i) => (
                <div key={item.id ?? i} className="rounded-lg bg-white p-3 ring-1 ring-slate-200">
                    <div className="mb-3 flex items-center justify-between">
                        <span className="text-xs font-semibold tracking-wide text-slate-500 uppercase">{titleCase(item.type)}</span>
                        <div className="flex">
                            <IconBtn icon={ArrowUp} label="Move up" disabled={i === 0} onClick={() => onChange(swap(items, i, i - 1))} />
                            <IconBtn icon={ArrowDown} label="Move down" disabled={i === items.length - 1} onClick={() => onChange(swap(items, i, i + 1))} />
                            <IconBtn icon={Trash2} label="Remove" danger onClick={() => onChange(items.filter((_, idx) => idx !== i))} />
                        </div>
                    </div>
                    <DataFields data={item.data} onChange={(d) => onChange(items.map((x, idx) => (idx === i ? { ...x, data: d } : x)))} context={item.type === 'image' ? 'column-image' : item.type} onUploadImage={onUploadImage} />
                </div>
            ))}
            <div className="flex gap-2">
                <Select value={type} onChange={(e) => setType(e.target.value)} options={Object.fromEntries(Object.keys(COLUMN_ITEMS).map((k) => [k, titleCase(k)]))} className="h-8 py-1 text-xs" />
                <Button size="sm" variant="secondary" icon={Plus} onClick={() => onChange([...items, { id: uid++, type, data: clone(COLUMN_ITEMS[type]) }])}>
                    Add
                </Button>
            </div>
        </div>
    );
}

const swap = (arr, a, b) => {
    if (b < 0 || b >= arr.length) return arr;
    const next = [...arr];
    [next[a], next[b]] = [next[b], next[a]];
    return next;
};
