import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import AuthLayout from '@/Layouts/AuthLayout';
import MarketingLayout from '@/Layouts/MarketingLayout';
import SuperadminLayout from '@/Layouts/SuperadminLayout';
import { setZiggyConfig } from '@/lib/route';
import FlashToaster from '@/Components/FlashToaster';
import { ConfirmProvider } from '@/Components/ui/Modal';

/**
 * Default layout by page folder. A page can still override it with `Page.layout = [...]`
 * (or opt out with `Page.layout = null`).
 */
function layoutFor(name) {
    if (name.startsWith('Auth/')) return AuthLayout;
    if (name.startsWith('Marketing/')) return MarketingLayout;
    if (name.startsWith('Superadmin/Auth/')) return AuthLayout;
    if (name.startsWith('Superadmin/')) return SuperadminLayout;
    if (name.startsWith('Public/') || name.startsWith('Onboarding/') || name.startsWith('Errors/')) return null;
    return AppLayout;
}

createInertiaApp({
    title: (title) => {
        const site = typeof document !== 'undefined' ? document.body?.dataset?.site : null;
        return title ? `${title} · ${site || 'RecurringPress'}` : site || 'RecurringPress';
    },
    layout: (name) => layoutFor(name),
    progress: {
        color: '#6366f1',
        delay: 150,
    },
    withApp(app, { page }) {
        setZiggyConfig(page.props.ziggy);
        return (
            <ConfirmProvider>
                {app}
                <FlashToaster initialFlash={page.props.flash} />
            </ConfirmProvider>
        );
    },
});
