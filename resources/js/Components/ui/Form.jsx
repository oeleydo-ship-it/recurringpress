import { Switch as HSwitch } from '@headlessui/react';
import { Eye, EyeOff } from 'lucide-react';
import { forwardRef, useId, useState } from 'react';
import { cn } from '@/lib/utils';

export function Label({ htmlFor, required, className, children }) {
    return (
        <label htmlFor={htmlFor} className={cn('block text-sm font-medium text-slate-700', className)}>
            {children}
            {required ? <span className="ml-0.5 text-red-500">*</span> : null}
        </label>
    );
}

export function FieldError({ children, className }) {
    if (!children) return null;
    return <p className={cn('mt-1.5 text-sm text-red-600', className)}>{children}</p>;
}

export function Hint({ children, className }) {
    if (!children) return null;
    return <p className={cn('mt-1.5 text-xs text-slate-500', className)}>{children}</p>;
}

/**
 * Label + control + hint + error. Pass the control as children; if it's one of our
 * inputs, give it the same `id` (FormField provides one via render prop too).
 */
export function FormField({ label, htmlFor, required, hint, error, className, children }) {
    const generated = useId();
    const id = htmlFor ?? generated;
    return (
        <div className={className}>
            {label ? (
                <Label htmlFor={id} required={required} className="mb-1.5">
                    {label}
                </Label>
            ) : null}
            {typeof children === 'function' ? children({ id, invalid: Boolean(error) }) : children}
            {error ? <FieldError>{error}</FieldError> : <Hint>{hint}</Hint>}
        </div>
    );
}

export const Input = forwardRef(function Input({ className, invalid, prefix, suffix, type = 'text', ...props }, ref) {
    if (prefix || suffix) {
        return (
            <div
                className={cn(
                    'flex w-full items-center rounded-lg bg-white shadow-xs ring-1 ring-slate-300 ring-inset focus-within:ring-2 focus-within:ring-brand-500',
                    invalid && 'ring-red-400 focus-within:ring-red-500',
                    props.disabled && 'bg-slate-50',
                    className,
                )}
            >
                {prefix ? <span className="pl-3 text-sm whitespace-nowrap text-slate-500 select-none">{prefix}</span> : null}
                <input
                    ref={ref}
                    type={type}
                    className="block w-full min-w-0 border-0 bg-transparent px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 disabled:cursor-not-allowed"
                    aria-invalid={invalid || undefined}
                    {...props}
                />
                {suffix ? <span className="pr-3 text-sm whitespace-nowrap text-slate-500 select-none">{suffix}</span> : null}
            </div>
        );
    }
    return <input ref={ref} type={type} className={cn('field', invalid && 'field-invalid', className)} aria-invalid={invalid || undefined} {...props} />;
});

export const Textarea = forwardRef(function Textarea({ className, invalid, rows = 3, ...props }, ref) {
    return <textarea ref={ref} rows={rows} className={cn('field', invalid && 'field-invalid', className)} aria-invalid={invalid || undefined} {...props} />;
});

/** Native select, styled. `options` = [{value,label}] or {value: label}. */
export const Select = forwardRef(function Select({ className, invalid, options, placeholder, children, ...props }, ref) {
    const list = Array.isArray(options) ? options : options ? Object.entries(options).map(([value, label]) => ({ value, label })) : null;
    return (
        <select ref={ref} className={cn('field pr-9', invalid && 'field-invalid', className)} aria-invalid={invalid || undefined} {...props}>
            {placeholder !== undefined ? <option value="">{placeholder}</option> : null}
            {list
                ? list.map((o) => (
                      <option key={o.value} value={o.value} disabled={o.disabled}>
                          {o.label}
                      </option>
                  ))
                : children}
        </select>
    );
});

export function Checkbox({ label, description, className, id, ...props }) {
    const generated = useId();
    const cid = id ?? generated;
    return (
        <div className={cn('flex gap-3', className)}>
            <div className="flex h-5 items-center">
                <input id={cid} type="checkbox" className="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" {...props} />
            </div>
            {label ? (
                <label htmlFor={cid} className="text-sm">
                    <span className="font-medium text-slate-800">{label}</span>
                    {description ? <span className="block text-slate-500">{description}</span> : null}
                </label>
            ) : null}
        </div>
    );
}

export function Switch({ checked, onChange, label, description, disabled, className }) {
    return (
        <div className={cn('flex items-center justify-between gap-4', className)}>
            {label ? (
                <span className="flex flex-col">
                    <span className="text-sm font-medium text-slate-800">{label}</span>
                    {description ? <span className="text-sm text-slate-500">{description}</span> : null}
                </span>
            ) : null}
            <HSwitch
                checked={Boolean(checked)}
                onChange={onChange}
                disabled={disabled}
                className="group relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full bg-slate-200 transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 disabled:cursor-not-allowed disabled:opacity-60 data-checked:bg-brand-600"
            >
                <span className="pointer-events-none mt-0.5 ml-0.5 inline-block size-5 rounded-full bg-white shadow ring-0 transition duration-200 group-data-checked:translate-x-5" />
            </HSwitch>
        </div>
    );
}

/** Segmented radio group — [{value,label,icon?}] */
export function Segmented({ value, onChange, options, className }) {
    return (
        <div className={cn('inline-flex rounded-lg bg-slate-100 p-1', className)} role="radiogroup">
            {options.map((o) => {
                const active = String(o.value) === String(value);
                const Icon = o.icon;
                return (
                    <button
                        key={o.value}
                        type="button"
                        role="radio"
                        aria-checked={active}
                        onClick={() => onChange(o.value)}
                        className={cn(
                            'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition',
                            active ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900',
                        )}
                    >
                        {Icon ? <Icon className="size-4" /> : null}
                        {o.label}
                    </button>
                );
            })}
        </div>
    );
}

/** Password input with a show/hide toggle. */
export const PasswordInput = forwardRef(function PasswordInput(props, ref) {
    const [show, setShow] = useState(false);
    return (
        <Input
            ref={ref}
            {...props}
            type={show ? 'text' : 'password'}
            suffix={
                <button type="button" tabIndex={-1} onClick={() => setShow((s) => !s)} className="text-slate-400 hover:text-slate-600" aria-label={show ? 'Hide password' : 'Show password'}>
                    {show ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                </button>
            }
        />
    );
});
