import { useEffect } from 'react';
import { themeStyle } from '@/lib/utils';

/**
 * Mirror the company theme variables onto <html>, so content rendered in portals
 * (modals, dropdowns, comboboxes, toasts) outside the layout wrapper is themed too.
 */
export function useApplyTheme(theme) {
    const vars = themeStyle(theme);
    const key = JSON.stringify(vars);

    useEffect(() => {
        const root = document.documentElement;
        Object.entries(vars).forEach(([name, value]) => root.style.setProperty(name, value));
        root.dataset.fontSize = theme?.font_size ?? 'base';
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key, theme?.font_size]);

    return vars;
}
