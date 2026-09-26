import { router, useForm } from '@inertiajs/react';
import { Archive, CheckCircle2, CloudDownload, RefreshCw, RotateCcw, Save, Trash2, Upload } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Badge, Button, Card, CardBody, CardFooter, CardHeader, EmptyState, FormField, Input, PageHeader, Select, Switch, useConfirm } from '@/Components/ui';
import { postJson, requestJson } from '@/lib/http';
import { route } from '@/lib/route';
import { formatDateTime } from '@/lib/utils';

export default function Updates({ currentVersion, autoUpdateEnabled, updateMethod, lastUpdateCheck, licenseHint, updateServerUrl, urls }) {
    const confirm = useConfirm();
    const [check, setCheck] = useState({ loading: false, result: null });
    const [installing, setInstalling] = useState(false);
    const [backups, setBackups] = useState({ loading: true, list: [] });

    const settings = useForm({ auto_update_enabled: autoUpdateEnabled, update_method: updateMethod, license_key: '', update_server_url: updateServerUrl ?? '' });
    const upload = useForm({ update_file: null, version: '' });

    const loadBackups = () =>
        requestJson('GET', urls.backups)
            .then((r) => setBackups({ loading: false, list: r.backups ?? [] }))
            .catch(() => setBackups({ loading: false, list: [] }));
    useEffect(() => {
        loadBackups();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const runCheck = async () => {
        setCheck({ loading: true, result: null });
        try {
            setCheck({ loading: false, result: await postJson(urls.check, {}) });
        } catch (e) {
            setCheck({ loading: false, result: { success: false, message: e.message } });
        }
    };

    const install = async () => {
        const r = check.result;
        if (!(await confirm({ title: `Install version ${r.latest_version}?`, message: 'A backup is created first. The site may be briefly unavailable.', confirmText: 'Install update', tone: 'brand' }))) return;
        setInstalling(true);
        try {
            const res = await postJson(urls.download, { download_url: r.download_url, version: r.latest_version });
            toast.success(res.message ?? 'Update installed');
            router.reload();
        } catch (e) {
            toast.error(e.message);
        } finally {
            setInstalling(false);
        }
    };

    const restore = async (name) => {
        if (await confirm({ title: 'Restore this backup?', message: `Files and database will be restored from ${name}. Changes since then are lost.`, confirmText: 'Restore' })) {
            router.post(route('superadmin.updates.restore'), { backup_file: name }, { preserveScroll: true });
        }
    };
    const remove = async (name) => {
        if (await confirm({ title: 'Delete this backup?', confirmText: 'Delete' })) {
            router.delete(route('superadmin.updates.backup.delete'), { data: { backup_file: name }, preserveScroll: true, onSuccess: loadBackups });
        }
    };

    return (
        <>
            <PageHeader title="System updates" meta={<Badge tone="brand">v{currentVersion}</Badge>} description={lastUpdateCheck ? `Last checked ${formatDateTime(lastUpdateCheck)}` : 'Never checked'} />
            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Check for updates" icon={CloudDownload} />
                    <CardBody className="space-y-4">
                        <Button icon={RefreshCw} loading={check.loading} onClick={runCheck} variant="secondary">
                            Check now
                        </Button>
                        {check.result ? (
                            check.result.success ? (
                                check.result.update_available ? (
                                    <div className="rounded-xl bg-indigo-50 p-4 ring-1 ring-indigo-200">
                                        <p className="font-semibold text-indigo-900">Version {check.result.latest_version} is available</p>
                                        {check.result.changelog ? <p className="mt-2 text-sm whitespace-pre-line text-indigo-800">{check.result.changelog}</p> : null}
                                        <Button className="mt-4" icon={CloudDownload} loading={installing} onClick={install}>
                                            Download & install
                                        </Button>
                                    </div>
                                ) : (
                                    <p className="flex items-center gap-2 text-sm font-medium text-emerald-700">
                                        <CheckCircle2 className="size-5" /> {check.result.message ?? 'You are up to date.'}
                                    </p>
                                )
                            ) : (
                                <p className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{check.result.message}</p>
                            )
                        ) : null}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Upload an update package" icon={Upload} />
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            upload.post(route('superadmin.updates.upload'), { forceFormData: true, preserveScroll: true, onSuccess: () => upload.reset() });
                        }}
                        noValidate
                    >
                        <CardBody className="space-y-4">
                            <FormField label="Package (.zip)" error={upload.errors.update_file}>
                                <input type="file" accept=".zip" onChange={(e) => upload.setData('update_file', e.target.files?.[0] ?? null)} className="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium" />
                            </FormField>
                            <FormField label="Version" error={upload.errors.version}>
                                <Input value={upload.data.version} onChange={(e) => upload.setData('version', e.target.value)} placeholder="e.g. 2.4.0" />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Button type="submit" icon={Upload} loading={upload.processing} disabled={!upload.data.update_file}>
                                Install package
                            </Button>
                        </CardFooter>
                    </form>
                </Card>

                <Card>
                    <CardHeader title="Update settings" />
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            settings.put(route('superadmin.updates.settings'), { preserveScroll: true, onSuccess: () => settings.setData('license_key', '') });
                        }}
                        noValidate
                    >
                        <CardBody className="space-y-4">
                            <Switch label="Automatic update checks" checked={settings.data.auto_update_enabled} onChange={(v) => settings.setData('auto_update_enabled', v)} />
                            <FormField label="Update method" error={settings.errors.update_method}>
                                <Select value={settings.data.update_method} onChange={(e) => settings.setData('update_method', e.target.value)} options={{ manual: 'Manual', server: 'Update server', upload: 'Upload package' }} />
                            </FormField>
                            <FormField label="Update server URL" error={settings.errors.update_server_url}>
                                <Input value={settings.data.update_server_url} onChange={(e) => settings.setData('update_server_url', e.target.value)} placeholder="https://updates.example.com" />
                            </FormField>
                            <FormField label="Licence key" error={settings.errors.license_key} hint={licenseHint ? `Saved (${licenseHint}) — leave blank to keep it.` : undefined}>
                                <Input type="password" autoComplete="off" value={settings.data.license_key} onChange={(e) => settings.setData('license_key', e.target.value)} />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Button type="submit" icon={Save} loading={settings.processing}>
                                Save settings
                            </Button>
                        </CardFooter>
                    </form>
                </Card>

                <Card>
                    <CardHeader title="Backups" description="Created automatically before each update." icon={Archive} />
                    {backups.list.length ? (
                        <ul className="divide-y divide-slate-100">
                            {backups.list.map((b) => (
                                <li key={b.name} className="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                    <span className="min-w-0">
                                        <span className="block truncate font-mono text-xs text-slate-800">{b.name}</span>
                                        <span className="text-xs text-slate-500">
                                            {b.date} · {b.size}
                                        </span>
                                    </span>
                                    <span className="flex shrink-0 gap-1">
                                        <Button size="xs" variant="secondary" icon={RotateCcw} onClick={() => restore(b.name)}>
                                            Restore
                                        </Button>
                                        <button type="button" onClick={() => remove(b.name)} className="rounded p-1.5 text-slate-400 hover:text-red-600" aria-label="Delete backup">
                                            <Trash2 className="size-4" />
                                        </button>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState icon={Archive} title={backups.loading ? 'Loading…' : 'No backups yet'} className="py-8" />
                    )}
                </Card>
            </div>
        </>
    );
}
