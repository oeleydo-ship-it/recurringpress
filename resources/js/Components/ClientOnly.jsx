import { useEffect, useState } from 'react';
/** Recharts measures the DOM, so render charts only after hydration (keeps SSR output stable). */
export default function ClientOnly({ children }) {
    const [mounted, setMounted] = useState(false);
    useEffect(() => setMounted(true), []);
    return mounted ? children : <div className="h-full w-full animate-pulse rounded-lg bg-slate-50" />;
}
