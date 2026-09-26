/**
 * Minimal JSON client for the few endpoints that return JSON instead of an Inertia page
 * (e.g. quick-add client). Sends Laravel's XSRF token from the cookie.
 */
function xsrfToken() {
    if (typeof document === 'undefined') return '';
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

export async function requestJson(method, url, data) {
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: data === undefined ? undefined : JSON.stringify(data),
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok) {
        const error = new Error(body.message || `Request failed (${res.status})`);
        error.status = res.status;
        error.errors = Object.fromEntries(Object.entries(body.errors ?? {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
        throw error;
    }
    return body;
}

export const postJson = (url, data) => requestJson('POST', url, data);

/** Multipart upload (e.g. superadmin media). Resolves to the parsed JSON response. */
export async function uploadFile(url, file, field = 'image') {
    const body = new FormData();
    body.append(field, file);
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrfToken() },
        body,
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok || json.success === false) {
        throw new Error(json.message || Object.values(json.errors ?? {})[0]?.[0] || `Upload failed (${res.status})`);
    }
    return json;
}
