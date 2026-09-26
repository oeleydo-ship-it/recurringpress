import { Link } from '@inertiajs/react';

/** Inertia <Link> for same-site paths, plain <a> (new tab) for external URLs / anchors. */
export default function SmartLink({ href, children, ...props }) {
    const url = href ?? '#';
    const internal = url.startsWith('/') && !url.startsWith('//');
    if (internal) {
        return (
            <Link href={url} {...props}>
                {children}
            </Link>
        );
    }
    const external = /^https?:\/\//i.test(url);
    return (
        <a href={url} target={external ? '_blank' : undefined} rel={external ? 'noopener noreferrer' : undefined} {...props}>
            {children}
        </a>
    );
}
