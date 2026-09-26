import Image from '@tiptap/extension-image';
import TextAlign from '@tiptap/extension-text-align';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    Bold,
    Code2,
    Heading2,
    Heading3,
    ImagePlus,
    Italic,
    Link2,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    Strikethrough,
    Underline as UnderlineIcon,
    Undo2,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

/**
 * WYSIWYG HTML editor (TipTap). `value` is HTML; `onChange(html)` fires on edit.
 * `onUploadImage(file) => Promise<url>` enables the image button.
 */
export default function RichTextEditor({ value, onChange, onUploadImage, placeholder = 'Start writing…', className, minHeight = 320 }) {
    const [source, setSource] = useState(false);
    const fileRef = useRef(null);

    const editor = useEditor({
        immediatelyRender: false, // SSR-safe
        extensions: [
            StarterKit.configure({ heading: { levels: [1, 2, 3, 4] }, link: { openOnClick: false, protocols: ['http', 'https', 'mailto', 'tel'] } }),
            Image,
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
        ],
        content: value || '',
        editorProps: {
            attributes: { class: 'prose-content max-w-none px-5 py-4 focus:outline-none', 'data-placeholder': placeholder },
        },
        onUpdate: ({ editor: e }) => onChange(e.getHTML()),
    });

    // Keep in sync when the value is replaced from outside (e.g. switching source mode).
    useEffect(() => {
        if (editor && !editor.isFocused && value !== editor.getHTML()) {
            editor.commands.setContent(value || '', { emitUpdate: false });
        }
    }, [value, editor]);

    const state = useEditorState({
        editor,
        selector: ({ editor: e }) =>
            e
                ? {
                      bold: e.isActive('bold'),
                      italic: e.isActive('italic'),
                      underline: e.isActive('underline'),
                      strike: e.isActive('strike'),
                      h2: e.isActive('heading', { level: 2 }),
                      h3: e.isActive('heading', { level: 3 }),
                      bullet: e.isActive('bulletList'),
                      ordered: e.isActive('orderedList'),
                      quote: e.isActive('blockquote'),
                      link: e.isActive('link'),
                      left: e.isActive({ textAlign: 'left' }),
                      center: e.isActive({ textAlign: 'center' }),
                      right: e.isActive({ textAlign: 'right' }),
                  }
                : {},
    });

    const setLink = () => {
        const prev = editor.getAttributes('link').href ?? '';
        const url = window.prompt('Link URL (https://…)', prev);
        if (url === null) return;
        if (url === '') return editor.chain().focus().extendMarkRange('link').unsetLink().run();
        if (!/^(https?:\/\/|mailto:|tel:|\/)/i.test(url)) return toast.error('Use an http(s), mailto:, tel: or relative link.');
        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    };

    const uploadImage = async (e) => {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file || !onUploadImage) return;
        try {
            const url = await onUploadImage(file);
            editor.chain().focus().setImage({ src: url }).run();
        } catch (err) {
            toast.error(err.message || 'Upload failed');
        }
    };

    const Btn = ({ on, active, icon: Icon, label, disabled }) => (
        <button
            type="button"
            onClick={on}
            disabled={disabled}
            title={label}
            aria-label={label}
            className={cn('rounded-md p-1.5 transition', active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100', disabled && 'opacity-30')}
        >
            <Icon className="size-4" />
        </button>
    );
    const Sep = () => <span className="mx-1 h-5 w-px bg-slate-200" />;

    return (
        <div className={cn('overflow-hidden rounded-xl bg-white ring-1 ring-slate-300 focus-within:ring-2 focus-within:ring-brand-500', className)}>
            <div className="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50/80 px-2 py-1.5">
                {editor && !source ? (
                    <>
                        <Btn on={() => editor.chain().focus().toggleBold().run()} active={state.bold} icon={Bold} label="Bold" />
                        <Btn on={() => editor.chain().focus().toggleItalic().run()} active={state.italic} icon={Italic} label="Italic" />
                        <Btn on={() => editor.chain().focus().toggleUnderline().run()} active={state.underline} icon={UnderlineIcon} label="Underline" />
                        <Btn on={() => editor.chain().focus().toggleStrike().run()} active={state.strike} icon={Strikethrough} label="Strikethrough" />
                        <Sep />
                        <Btn on={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} active={state.h2} icon={Heading2} label="Heading" />
                        <Btn on={() => editor.chain().focus().toggleHeading({ level: 3 }).run()} active={state.h3} icon={Heading3} label="Subheading" />
                        <Btn on={() => editor.chain().focus().toggleBulletList().run()} active={state.bullet} icon={List} label="Bullet list" />
                        <Btn on={() => editor.chain().focus().toggleOrderedList().run()} active={state.ordered} icon={ListOrdered} label="Numbered list" />
                        <Btn on={() => editor.chain().focus().toggleBlockquote().run()} active={state.quote} icon={Quote} label="Quote" />
                        <Btn on={() => editor.chain().focus().setHorizontalRule().run()} icon={Minus} label="Divider" />
                        <Sep />
                        <Btn on={() => editor.chain().focus().setTextAlign('left').run()} active={state.left} icon={AlignLeft} label="Align left" />
                        <Btn on={() => editor.chain().focus().setTextAlign('center').run()} active={state.center} icon={AlignCenter} label="Align center" />
                        <Btn on={() => editor.chain().focus().setTextAlign('right').run()} active={state.right} icon={AlignRight} label="Align right" />
                        <Sep />
                        <Btn on={setLink} active={state.link} icon={Link2} label="Link" />
                        {onUploadImage ? <Btn on={() => fileRef.current?.click()} icon={ImagePlus} label="Insert image" /> : null}
                        <Sep />
                        <Btn on={() => editor.chain().focus().undo().run()} icon={Undo2} label="Undo" />
                        <Btn on={() => editor.chain().focus().redo().run()} icon={Redo2} label="Redo" />
                    </>
                ) : null}
                <div className="flex-1" />
                <Btn on={() => setSource((s) => !s)} active={source} icon={Code2} label={source ? 'Visual editor' : 'HTML source'} />
                <input ref={fileRef} type="file" accept="image/png,image/jpeg,image/webp,image/gif" className="hidden" onChange={uploadImage} />
            </div>
            {source ? (
                <textarea value={value ?? ''} onChange={(e) => onChange(e.target.value)} spellCheck={false} className="block w-full border-0 bg-slate-950 p-4 font-mono text-xs leading-relaxed text-slate-100 focus:ring-0" style={{ minHeight }} />
            ) : (
                <div style={{ minHeight }} className="cursor-text" onClick={() => editor?.commands.focus()}>
                    {editor ? <EditorContent editor={editor} /> : <div className="p-5 text-sm text-slate-400">Loading editor…</div>}
                </div>
            )}
        </div>
    );
}
