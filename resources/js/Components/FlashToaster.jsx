import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { Toaster, toast } from 'sonner';

/**
 * Turns Laravel session flashes (->with('success', ...)) into toasts.
 * Shows each flash once, including on the initial page load. Rendered by app.jsx outside
 * the page tree, so it reads flashes from router events rather than usePage().
 */
export default function FlashToaster({ initialFlash }) {
    const lastShown = useRef(null);

    useEffect(() => {
        const show = (f) => {
            const signature = JSON.stringify(f ?? {});
            if (signature === '{}' || signature === '[]') {
                lastShown.current = null; // an empty flash separates visits; allow repeats after it
                return;
            }
            if (signature === lastShown.current) return; // same visit reported twice (e.g. partial reload)
            lastShown.current = signature;
            if (f.success) toast.success(f.success);
            if (f.status && !f.success) toast.success(f.status === 'verification-link-sent' ? 'A new verification link has been sent to your email.' : f.status);
            if (f.error) toast.error(f.error);
            if (f.warning) toast.warning(f.warning);
            if (f.info) toast.info(f.info);
        };

        show(initialFlash);
        return router.on('success', (event) => show(event.detail.page.props.flash));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return <Toaster position="top-right" richColors closeButton toastOptions={{ className: 'font-sans' }} />;
}
