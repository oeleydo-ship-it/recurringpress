import { Building2, User } from 'lucide-react';
import { FormField, Input, Segmented, Textarea } from '@/Components/ui';

export const emptyClient = {
    client_type: 'individual',
    name: '',
    company_name: '',
    email: '',
    phone: '',
    tax_number: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    notes: '',
};

/** Client form fields, shared by the client pages and the quick-add modal. */
export default function ClientFields({ data, setData, errors = {}, compact = false }) {
    const f = (name, label, props = {}) => (
        <FormField label={label} htmlFor={`client-${name}`} error={errors[name]} required={props.required} className={props.className}>
            <Input
                id={`client-${name}`}
                type={props.type ?? 'text'}
                value={data[name] ?? ''}
                onChange={(e) => setData(name, e.target.value)}
                invalid={Boolean(errors[name])}
                autoComplete={props.autoComplete}
                placeholder={props.placeholder}
            />
        </FormField>
    );

    const isCompany = data.client_type === 'company';

    return (
        <div className="space-y-6">
            <div>
                <p className="mb-2 text-sm font-medium text-slate-700">Client type</p>
                <Segmented
                    value={data.client_type}
                    onChange={(v) => setData('client_type', v)}
                    options={[
                        { value: 'individual', label: 'Individual', icon: User },
                        { value: 'company', label: 'Company', icon: Building2 },
                    ]}
                />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                {isCompany ? f('company_name', 'Company name', { required: true, autoComplete: 'organization' }) : null}
                {f('name', isCompany ? 'Contact person' : 'Full name', { required: true, autoComplete: 'name' })}
                {f('email', 'Email', { type: 'email', autoComplete: 'email', placeholder: 'billing@client.com' })}
                {f('phone', 'Phone', { autoComplete: 'tel' })}
                {f('tax_number', 'Tax / VAT number')}
            </div>

            {!compact ? (
                <>
                    <div className="border-t border-slate-100 pt-6">
                        <h3 className="mb-4 text-sm font-semibold text-slate-900">Billing address</h3>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {f('address', 'Street address', { className: 'sm:col-span-2', autoComplete: 'street-address' })}
                            {f('city', 'City')}
                            {f('state', 'State / region')}
                            {f('postal_code', 'Postal code')}
                            {f('country', 'Country')}
                        </div>
                    </div>
                    <FormField label="Internal notes" htmlFor="client-notes" error={errors.notes} hint="Only visible to your team.">
                        <Textarea id="client-notes" rows={3} value={data.notes ?? ''} onChange={(e) => setData('notes', e.target.value)} />
                    </FormField>
                </>
            ) : null}
        </div>
    );
}
