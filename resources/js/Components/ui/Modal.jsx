import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@headlessui/react';
import { AlertTriangle, X } from 'lucide-react';
import { createContext, useCallback, useContext, useRef, useState } from 'react';
import Button from './Button';
import { cn } from '@/lib/utils';

const widths = { sm: 'sm:max-w-md', md: 'sm:max-w-lg', lg: 'sm:max-w-2xl', xl: 'sm:max-w-4xl', full: 'sm:max-w-6xl' };

export default function Modal({ open, onClose, title, description, size = 'md', footer, children, className }) {
    return (
        <Dialog open={open} onClose={onClose} className="relative z-50">
            <DialogBackdrop transition className="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity duration-200 data-closed:opacity-0" />
            <div className="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div className="flex min-h-full items-end justify-center p-4 sm:items-center">
                    <DialogPanel
                        transition
                        className={cn(
                            'relative w-full transform overflow-hidden rounded-2xl bg-white text-left shadow-pop ring-1 ring-slate-900/5 transition duration-200 data-closed:translate-y-3 data-closed:opacity-0 sm:data-closed:scale-95',
                            widths[size],
                            className,
                        )}
                    >
                        {title ? (
                            <div className="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4">
                                <div>
                                    <DialogTitle className="text-base font-semibold text-slate-900">{title}</DialogTitle>
                                    {description ? <p className="mt-1 text-sm text-slate-500">{description}</p> : null}
                                </div>
                                <button type="button" onClick={onClose} className="-mr-2 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Close">
                                    <X className="size-5" />
                                </button>
                            </div>
                        ) : null}
                        <div className="px-6 py-5">{children}</div>
                        {footer ? <div className="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/60 px-6 py-3 sm:flex-row sm:justify-end">{footer}</div> : null}
                    </DialogPanel>
                </div>
            </div>
        </Dialog>
    );
}

/* ------------------------------------------------------------------ */
/* Promise-based confirmation dialog: const ok = await confirm({...}) */
/* ------------------------------------------------------------------ */

const ConfirmContext = createContext(null);

export function ConfirmProvider({ children }) {
    const [state, setState] = useState(null);
    const resolver = useRef(null);

    const confirm = useCallback((options = {}) => {
        setState({
            title: 'Are you sure?',
            message: 'This action cannot be undone.',
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            tone: 'danger',
            ...options,
        });
        return new Promise((resolve) => {
            resolver.current = resolve;
        });
    }, []);

    const close = (result) => {
        resolver.current?.(result);
        resolver.current = null;
        setState((s) => (s ? { ...s, open: false } : s));
        setTimeout(() => setState(null), 200);
    };

    return (
        <ConfirmContext.Provider value={confirm}>
            {children}
            <Dialog open={Boolean(state) && state.open !== false} onClose={() => close(false)} className="relative z-[60]">
                <DialogBackdrop transition className="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity duration-200 data-closed:opacity-0" />
                <div className="fixed inset-0 flex items-end justify-center p-4 sm:items-center">
                    <DialogPanel transition className="w-full max-w-md rounded-2xl bg-white p-6 shadow-pop transition duration-200 data-closed:translate-y-3 data-closed:opacity-0 sm:data-closed:scale-95">
                        <div className="flex gap-4">
                            <span
                                className={cn(
                                    'flex size-10 shrink-0 items-center justify-center rounded-full',
                                    state?.tone === 'danger' ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-600',
                                )}
                            >
                                <AlertTriangle className="size-5" />
                            </span>
                            <div>
                                <DialogTitle className="text-base font-semibold text-slate-900">{state?.title}</DialogTitle>
                                <p className="mt-1.5 text-sm text-slate-600">{state?.message}</p>
                            </div>
                        </div>
                        <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <Button variant="secondary" onClick={() => close(false)}>
                                {state?.cancelText}
                            </Button>
                            <Button variant={state?.tone === 'danger' ? 'danger' : 'primary'} onClick={() => close(true)} autoFocus>
                                {state?.confirmText}
                            </Button>
                        </div>
                    </DialogPanel>
                </div>
            </Dialog>
        </ConfirmContext.Provider>
    );
}

export function useConfirm() {
    const confirm = useContext(ConfirmContext);
    // Fallback when rendered outside the provider (e.g. SSR edge cases).
    return confirm ?? ((o = {}) => Promise.resolve(typeof window !== 'undefined' ? window.confirm(o.message ?? 'Are you sure?') : false));
}
