import { usePage } from '@inertiajs/react';

/**
 * Spam trap for public forms (server side: App\Http\Middleware\ProtectAgainstSpam).
 * Returns the extra fields to merge into a useForm() payload and renders the hidden input.
 *
 *   const hp = useHoneypot();
 *   const form = useForm({ ...hp.fields, email: '' });
 *   <Honeypot form={form} />
 */
export function useHoneypot() {
    const { honeypot } = usePage().props;
    if (!honeypot) return { fields: {} };
    return {
        fields: { [honeypot.field]: '', [honeypot.timestampField]: honeypot.timestamp },
        config: honeypot,
    };
}

export default function Honeypot({ form }) {
    const { honeypot } = usePage().props;
    if (!honeypot) return null;
    return (
        <div aria-hidden="true" style={{ position: 'absolute', left: '-10000px', top: 'auto', width: 1, height: 1, overflow: 'hidden' }}>
            <label htmlFor={honeypot.field}>Leave this field empty</label>
            <input
                type="text"
                id={honeypot.field}
                name={honeypot.field}
                tabIndex={-1}
                autoComplete="off"
                value={form.data[honeypot.field] ?? ''}
                onChange={(e) => form.setData(honeypot.field, e.target.value)}
            />
        </div>
    );
}
