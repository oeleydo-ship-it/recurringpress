import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

const PAGE_WIDTH = 800;
const PAGE_HEIGHT = 1132; // A4 ratio

/**
 * Live miniature of an invoice template. The iframe lays the invoice out at a fixed A4 width
 * and is scaled to the card's real width, so it never reflows or crops on narrow screens.
 */
export default function TemplateThumbnail({ src, className }) {
    const ref = useRef(null);
    const [scale, setScale] = useState(0.25);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        const fit = () => el.clientWidth && setScale(el.clientWidth / PAGE_WIDTH);
        fit();
        const ro = new ResizeObserver(fit);
        ro.observe(el);
        return () => ro.disconnect();
    }, []);

    return (
        <div ref={ref} className={cn('relative aspect-[21/29.7] w-full overflow-hidden bg-white', className)}>
            <iframe
                src={src}
                loading="lazy"
                tabIndex={-1}
                aria-hidden="true"
                scrolling="no"
                title=""
                className="pointer-events-none absolute top-0 left-0 origin-top-left border-0 select-none"
                style={{ width: PAGE_WIDTH, height: PAGE_HEIGHT, transform: `scale(${scale})` }}
            />
        </div>
    );
}
