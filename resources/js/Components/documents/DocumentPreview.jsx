import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

const PAGE_WIDTH = 800;

/**
 * Embeds the server-rendered document (same Blade template as the PDF) scaled to fit the
 * container width, growing to the document's real height once it loads. Change `version`
 * (e.g. status + amount paid) to reload it after the underlying record changes.
 */
export default function DocumentPreview({ src, version, className }) {
    const box = useRef(null);
    const frame = useRef(null);
    const [scale, setScale] = useState(1);
    const [height, setHeight] = useState(1132);
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {
        const el = box.current;
        if (!el) return;
        const fit = () => el.clientWidth && setScale(Math.min(1, el.clientWidth / PAGE_WIDTH));
        fit();
        const ro = new ResizeObserver(fit);
        ro.observe(el);
        return () => ro.disconnect();
    }, []);

    const onLoad = () => {
        setLoaded(true);
        try {
            const doc = frame.current?.contentDocument;
            if (doc) setHeight(Math.max(1132, doc.documentElement.scrollHeight));
        } catch {
            /* cross-origin — keep A4 height */
        }
    };

    return (
        <div ref={box} className={cn('relative w-full overflow-hidden rounded-xl bg-white shadow-card ring-1 ring-slate-900/5', className)} style={{ height: height * scale }}>
            {!loaded ? <div className="absolute inset-0 animate-pulse bg-slate-50" /> : null}
            <iframe
                key={version}
                ref={frame}
                src={src}
                title="Document preview"
                onLoad={onLoad}
                scrolling="no"
                className="absolute top-0 left-0 origin-top-left border-0"
                style={{ width: PAGE_WIDTH, height, transform: `scale(${scale})` }}
            />
        </div>
    );
}
