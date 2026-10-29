import { router, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    Bot,
    Calendar,
    CheckCircle2,
    Clock,
    ExternalLink,
    Newspaper,
    Pencil,
    Play,
    Plus,
    Radio,
    Send,
    Settings2,
    Sparkles,
    Trash2,
    Wand2,
    Zap,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    Badge,
    Button,
    Card,
    CardBody,
    CardHeader,
    Dropdown,
    EmptyState,
    FormField,
    Input,
    Modal,
    PageHeader,
    Pagination,
    Select,
    Switch,
    Table,
    TBody,
    TD,
    TH,
    THead,
    TR,
    Textarea,
    useConfirm,
} from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate } from '@/lib/utils';

export default function SuperadminPosts({ posts, aiSettings, stats, generationRuns = [] }) {
    useEffect(() => {
        if (!generationRuns.some((run) => ['queued', 'running'].includes(run.status))) return;
        const timer = setInterval(() => router.reload({ only: ['generationRuns', 'posts', 'stats', 'aiSettings'] }), 15000);
        return () => clearInterval(timer);
    }, [generationRuns]);
    const confirm = useConfirm();
    const [showSchedulerModal, setShowSchedulerModal] = useState(false);
    const [showGenerateModal, setShowGenerateModal] = useState(false);

    // Auto-scheduler settings form
    const schedulerForm = useForm({
        ai_blog_enabled: aiSettings?.enabled ?? false,
        ai_blog_timezone: aiSettings?.timezone ?? 'UTC',
        ai_blog_start_time: aiSettings?.start_time ?? '09:00',
        ai_blog_keywords: aiSettings?.keywords ?? '',
        ai_blog_posts_per_day: aiSettings?.posts_per_day ?? 1,
        ai_blog_tone: aiSettings?.tone ?? 'professional and engaging',
        ai_blog_language: aiSettings?.language ?? 'English',
        ai_blog_status: aiSettings?.status ?? 'scheduled',
        ai_blog_include_faq: aiSettings?.include_faq ?? true,
    });

    // Quick single post generator form
    const generateForm = useForm({
        keyword: '',
        tone: aiSettings?.tone ?? 'professional and engaging',
        language: aiSettings?.language ?? 'English',
        status: aiSettings?.status ?? 'scheduled',
        include_faq: aiSettings?.include_faq ?? true,
        published_at: '',
        mode: 'queue', // 'instant' redirects to editor, 'queue' sends to background job
    });

    const submitScheduler = (e) => {
        e?.preventDefault();
        schedulerForm.put(route('superadmin.posts.ai-settings'), {
            preserveScroll: true,
            onSuccess: () => setShowSchedulerModal(false),
        });
    };

    const submitGenerate = (e) => {
        e?.preventDefault();
        generateForm.transform((data) => ({ ...data, published_at: data.published_at ? new Date(data.published_at).toISOString() : null }));
        generateForm.post(route('superadmin.posts.ai-generate'), {
            preserveScroll: true,
            onSuccess: () => {
                setShowGenerateModal(false);
                generateForm.reset('keyword');
            },
        });
    };

    const runSchedulerNow = () => {
        router.post(route('superadmin.posts.ai-run-scheduler'), {}, { preserveScroll: true });
    };

    const publishNow = async (p) => {
        if (await confirm({ title: `Publish “${p.title}” live now?`, confirmText: 'Publish now', tone: 'primary' })) {
            router.post(route('superadmin.posts.publish-now', p.id), {}, { preserveScroll: true });
        }
    };

    const destroy = async (p) => {
        if (await confirm({ title: `Delete “${p.title}”?`, confirmText: 'Delete post' })) {
            router.delete(route('superadmin.posts.destroy', p.id), { preserveScroll: true });
        }
    };

    const toneOptions = [
        { value: 'professional and engaging', label: 'Professional & Engaging (Recommended)' },
        { value: 'authoritative and in-depth', label: 'Authoritative & In-depth' },
        { value: 'practical step-by-step guide', label: 'Practical Step-by-Step Guide' },
        { value: 'conversational and accessible', label: 'Conversational & Friendly' },
        { value: 'thought leadership', label: 'Thought Leadership & Analysis' },
    ];

    const statusOptions = [
        { value: 'scheduled', label: 'Scheduled (Auto-spaced release)' },
        { value: 'published', label: 'Published (Immediate live release)' },
        { value: 'draft', label: 'Draft (Requires manual approval)' },
    ];

    return (
        <>
            <PageHeader
                title="Blog posts"
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="secondary"
                            icon={Settings2}
                            onClick={() => setShowSchedulerModal(true)}
                            className="border-slate-300"
                        >
                            AI Auto-Blogger
                            {aiSettings?.enabled ? (
                                <span className="ml-1.5 inline-flex size-2 rounded-full bg-emerald-500 ring-2 ring-emerald-100" />
                            ) : null}
                        </Button>

                        <Button
                            variant="secondary"
                            icon={Sparkles}
                            onClick={() => setShowGenerateModal(true)}
                            className="border-indigo-200 bg-indigo-50/50 text-indigo-700 hover:bg-indigo-100/60"
                        >
                            Generate with AI
                        </Button>

                        <Button href={route('superadmin.posts.create')} icon={Plus}>
                            New post
                        </Button>
                    </div>
                }
            />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4">
                <div className="text-sm">
                    <p className="font-semibold">AI API configuration</p>
                    <p className="text-slate-500">{aiSettings?.ai_available ? `Configured: ${aiSettings.provider} · ${aiSettings.model}. Use Test connection in API settings to verify.` : 'Configure a provider, save its API key and model, and enable AI to generate articles.'}</p>
                </div>
                <Button href={route('superadmin.settings.ai')} variant="secondary" icon={Settings2}>Configure / test API</Button>
            </div>
            {generationRuns.length > 0 && <Card className="mb-6">
                <CardHeader title="Recent AI generation" />
                <CardBody className="space-y-3">
                    {generationRuns.map((run) => <div key={run.id} className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2 text-sm">
                        <div><p className="font-medium">{run.keyword}</p>{run.error && <p className="text-xs text-rose-700">{run.error}</p>}</div>
                        <div className="flex items-center gap-3"><Badge tone={run.status === 'completed' ? 'green' : run.status === 'failed' ? 'red' : 'indigo'}>{run.status}</Badge>
                            {run.post_id && <a className="text-indigo-600 underline" href={route('superadmin.posts.edit', run.post_id)}>Open article</a>}
                        </div>
                    </div>)}
                </CardBody>
            </Card>}
            {/* Quick Metrics Cards */}
            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-slate-500">Total Posts</span>
                        <Newspaper className="size-4 text-slate-400" />
                    </div>
                    <div className="mt-2 text-2xl font-bold text-slate-900">{stats?.total ?? posts.total ?? 0}</div>
                    <div className="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                        <span>{stats?.published ?? 0} published</span>
                        <span>•</span>
                        <span>{stats?.drafts ?? 0} drafts</span>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-slate-500">Scheduled Posts</span>
                        <Calendar className="size-4 text-indigo-500" />
                    </div>
                    <div className="mt-2 text-2xl font-bold text-indigo-600">{stats?.scheduled ?? 0}</div>
                    <div className="mt-1 text-xs text-slate-500">Waiting for automatic publishing</div>
                </div>

                <div className="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-slate-500">AI Generated</span>
                        <Sparkles className="size-4 text-purple-500" />
                    </div>
                    <div className="mt-2 text-2xl font-bold text-purple-600">{stats?.ai_generated ?? 0}</div>
                    <div className="mt-1 text-xs text-slate-500">SEO articles created with AI</div>
                </div>

                <div className="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-slate-500">Auto-Blogger Status</span>
                        <Bot className="size-4 text-emerald-500" />
                    </div>
                    <div className="mt-2 flex items-center gap-2">
                        <span className="text-2xl font-bold text-slate-900">
                            {aiSettings?.enabled ? 'Active' : 'Paused'}
                        </span>
                        <Badge tone={aiSettings?.enabled ? 'green' : 'slate'} size="sm">
                            {aiSettings?.enabled ? `${aiSettings?.posts_per_day}/day` : 'Off'}
                        </Badge>
                    </div>
                    <div className="mt-1 text-xs text-slate-500">
                        {aiSettings?.unused_keywords ?? 0} keyword(s) in queue
                    </div>
                </div>
            </div>

            {/* Posts Table */}
            <Card>
                {posts.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Post</TH>
                                <TH>Status</TH>
                                <TH className="hidden md:table-cell">Publish Date</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {posts.data.map((p) => (
                                    <TR key={p.id} href={route('superadmin.posts.edit', p.id)}>
                                        <TD>
                                            <div className="flex items-center gap-3">
                                                <span className="size-12 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-indigo-100 to-sky-100">
                                                    {p.image ? (
                                                        <img src={p.image} alt="" className="size-full object-cover" />
                                                    ) : null}
                                                </span>
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <span className="block max-w-md truncate font-medium text-slate-900">
                                                            {p.title}
                                                        </span>
                                                        {p.is_ai_generated ? (
                                                            <span className="inline-flex items-center gap-1 rounded-md bg-purple-50 px-1.5 py-0.5 text-[10px] font-semibold text-purple-700 ring-1 ring-purple-500/20">
                                                                <Sparkles className="size-2.5 text-purple-500" /> AI
                                                            </span>
                                                        ) : null}
                                                    </div>
                                                    <div className="flex items-center gap-2 text-xs text-slate-500">
                                                        {p.author ? <span>By {p.author}</span> : null}
                                                        {p.target_keyword ? (
                                                            <>
                                                                <span>•</span>
                                                                <span className="font-mono text-[11px] text-slate-600">
                                                                    Key: {p.target_keyword}
                                                                </span>
                                                            </>
                                                        ) : null}
                                                    </div>
                                                </div>
                                            </div>
                                        </TD>
                                        <TD>
                                            {p.is_published ? (
                                                <Badge tone="green">Published</Badge>
                                            ) : p.is_scheduled ? (
                                                <Badge tone="indigo" className="inline-flex items-center gap-1">
                                                    <Clock className="size-3" /> Scheduled
                                                </Badge>
                                            ) : (
                                                <Badge>Draft</Badge>
                                            )}
                                        </TD>
                                        <TD className="hidden text-slate-500 md:table-cell">
                                            {p.published_at ? (
                                                <div className="flex items-center gap-1 text-sm">
                                                    <span>{formatDate(p.published_at)}</span>
                                                    {p.is_scheduled ? (
                                                        <span className="text-xs text-indigo-600 font-medium">(upcoming)</span>
                                                    ) : null}
                                                </div>
                                            ) : (
                                                '—'
                                            )}
                                        </TD>
                                        <TD>
                                            <Dropdown
                                                items={[
                                                    { label: 'Edit', icon: Pencil, href: route('superadmin.posts.edit', p.id) },
                                                    {
                                                        label: 'Publish now',
                                                        icon: Send,
                                                        hidden: p.is_published,
                                                        onClick: () => publishNow(p),
                                                    },
                                                    {
                                                        label: 'View live',
                                                        icon: ExternalLink,
                                                        href: p.url,
                                                        external: true,
                                                        hidden: !p.is_published,
                                                    },
                                                    { separator: true },
                                                    { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(p) },
                                                ]}
                                            />
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={posts} />
                    </>
                ) : (
                    <EmptyState
                        icon={Newspaper}
                        title="No posts yet"
                        description="Start creating manually or use the AI Auto-Blogger to generate high-ranking SEO content on schedule."
                        actions={
                            <div className="flex gap-2">
                                <Button icon={Sparkles} onClick={() => setShowGenerateModal(true)}>
                                    Generate first post with AI
                                </Button>
                            </div>
                        }
                    />
                )}
            </Card>

            {/* AI Auto-Blogger Settings Modal */}
            <Modal
                open={showSchedulerModal}
                onClose={() => setShowSchedulerModal(false)}
                title="AI Auto-Blogger & Scheduler"
                description="Automatically generate SEO-optimized articles from target keywords and publish on a recurring schedule."
                size="lg"
                footer={
                    <div className="flex w-full items-center justify-between">
                        <Button
                            type="button"
                            variant="secondary"
                            icon={Zap}
                            onClick={runSchedulerNow}
                            title="Generate the next queued keyword immediately"
                        >
                            Run Next in Queue
                        </Button>
                        <div className="flex gap-2">
                            <Button type="button" variant="ghost" onClick={() => setShowSchedulerModal(false)}>
                                Cancel
                            </Button>
                            <Button loading={schedulerForm.processing} onClick={submitScheduler}>
                                Save Settings
                            </Button>
                        </div>
                    </div>
                }
            >
                <form onSubmit={submitScheduler} className="space-y-5">
                    {/* AI Provider Status Banner */}
                    {!aiSettings?.ai_available ? (
                        <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3.5 text-sm text-amber-800">
                            <AlertCircle className="size-5 shrink-0 text-amber-600" />
                            <div>
                                <span className="font-semibold">AI Provider Not Enabled: </span>
                                Please configure your API key (Anthropic, OpenAI, DeepSeek, or Kimi) in{' '}
                                <a href={route('superadmin.settings.ai')} className="font-medium underline hover:text-amber-900">
                                    Superadmin → AI
                                </a>{' '}
                                before activating the Auto-Blogger.
                            </div>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50/70 px-3.5 py-2.5 text-xs font-medium text-emerald-800">
                            <CheckCircle2 className="size-4 text-emerald-600" />
                            AI provider is configured. Test its connection in API settings before enabling automation.
                        </div>
                    )}

                    <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <div>
                            <span className="block text-sm font-semibold text-slate-900">Enable Automated Schedule</span>
                            <span className="text-xs text-slate-500">
                                When enabled, the system background scheduler will automatically pick keywords and generate articles.
                            </span>
                        </div>
                        <Switch
                            checked={schedulerForm.data.ai_blog_enabled}
                            onChange={(val) => schedulerForm.setData('ai_blog_enabled', val)}
                        />
                    </div>

                    <FormField
                        label="Target Keywords / Topics (One per line)"
                        hint="One article per topic. Automation pauses when all topics are used; add new topics to continue."
                        error={schedulerForm.errors.ai_blog_keywords}
                    >
                        <Textarea
                            rows={5}
                            placeholder="invoicing best practices for contractors&#10;how to manage overdue client invoices&#10;freelance bookkeeping checklist 2026&#10;automated payment reminders guide"
                            value={schedulerForm.data.ai_blog_keywords}
                            onChange={(e) => schedulerForm.setData('ai_blog_keywords', e.target.value)}
                            className="font-mono text-sm leading-relaxed"
                        />
                    </FormField>

                    <FormField error={schedulerForm.errors.ai_blog_enabled} />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Schedule timezone" error={schedulerForm.errors.ai_blog_timezone} hint="IANA timezone, for example Asia/Dubai or UTC.">
                            <Input value={schedulerForm.data.ai_blog_timezone} onChange={(e) => schedulerForm.setData('ai_blog_timezone', e.target.value)} />
                        </FormField>
                        <FormField label="First daily slot" error={schedulerForm.errors.ai_blog_start_time} hint="Remaining slots are evenly spaced over 24 hours.">
                            <Input type="time" value={schedulerForm.data.ai_blog_start_time} onChange={(e) => schedulerForm.setData('ai_blog_start_time', e.target.value)} />
                        </FormField>
                    </div>
                    <p className="text-xs text-slate-500">Automatic publishing requires the server scheduler every minute and an AI blog queue worker. Save settings before running the next slot.</p>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="Posts per day"
                            hint="How many posts to create and schedule every 24 hours."
                            error={schedulerForm.errors.ai_blog_posts_per_day}
                        >
                            <Select
                                value={schedulerForm.data.ai_blog_posts_per_day}
                                onChange={(e) => schedulerForm.setData('ai_blog_posts_per_day', Number(e.target.value))}
                                options={[
                                    { value: 1, label: '1 post per day (Every 24 hours)' },
                                    { value: 2, label: '2 posts per day (Every 12 hours)' },
                                    { value: 3, label: '3 posts per day (Every 8 hours)' },
                                    { value: 4, label: '4 posts per day (Every 6 hours)' },
                                    { value: 6, label: '6 posts per day (Every 4 hours)' },
                                ]}
                            />
                        </FormField>

                        <FormField
                            label="Default Post Status"
                            hint="Status assigned to generated posts."
                            error={schedulerForm.errors.ai_blog_status}
                        >
                            <Select
                                value={schedulerForm.data.ai_blog_status}
                                onChange={(e) => schedulerForm.setData('ai_blog_status', e.target.value)}
                                options={statusOptions}
                            />
                        </FormField>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="Content Tone"
                            hint="Editorial voice and structure."
                            error={schedulerForm.errors.ai_blog_tone}
                        >
                            <Select
                                value={schedulerForm.data.ai_blog_tone}
                                onChange={(e) => schedulerForm.setData('ai_blog_tone', e.target.value)}
                                options={toneOptions}
                            />
                        </FormField>

                        <FormField
                            label="Language"
                            hint="Article publication language."
                            error={schedulerForm.errors.ai_blog_language}
                        >
                            <Input
                                value={schedulerForm.data.ai_blog_language}
                                onChange={(e) => schedulerForm.setData('ai_blog_language', e.target.value)}
                                placeholder="English"
                            />
                        </FormField>
                    </div>

                    <div className="flex items-center justify-between rounded-lg border border-slate-100 bg-white p-3">
                        <span className="text-sm font-medium text-slate-700">Include Structured FAQ Section</span>
                        <Switch
                            checked={schedulerForm.data.ai_blog_include_faq}
                            onChange={(val) => schedulerForm.setData('ai_blog_include_faq', val)}
                        />
                    </div>
                </form>
            </Modal>

            {/* Quick Single AI Generator Modal */}
            <Modal
                open={showGenerateModal}
                onClose={() => setShowGenerateModal(false)}
                title="Generate SEO Blog Post with AI"
                description="Input a target keyword or title to create an optimized article complete with headings, meta tags, and content."
                size="md"
                footer={
                    <>
                        <Button type="button" variant="ghost" onClick={() => setShowGenerateModal(false)}>
                            Cancel
                        </Button>
                        <Button
                            icon={Sparkles}
                            loading={generateForm.processing}
                            onClick={submitGenerate}
                        >
                            {generateForm.data.mode === 'instant' ? 'Generate & Open Editor' : 'Queue in Background'}
                        </Button>
                    </>
                }
            >
                <form onSubmit={submitGenerate} className="space-y-4">
                    <FormField
                        label="Target Keyword or Topic"
                        required
                        error={generateForm.errors.keyword}
                        hint="e.g. 'How to deal with clients who do not pay invoices on time'"
                    >
                        <Input
                            autoFocus
                            placeholder="Enter keyword or blog subject..."
                            value={generateForm.data.keyword}
                            onChange={(e) => generateForm.setData('keyword', e.target.value)}
                        />
                    </FormField>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <FormField label="Tone">
                            <Select
                                value={generateForm.data.tone}
                                onChange={(e) => generateForm.setData('tone', e.target.value)}
                                options={toneOptions}
                            />
                        </FormField>

                        <FormField label="Status">
                            <Select
                                value={generateForm.data.status}
                                onChange={(e) => generateForm.setData('status', e.target.value)}
                                options={statusOptions}
                            />
                        </FormField>
                    </div>

                    {generateForm.data.status === 'scheduled' && <FormField label="Publish at (your local time)" error={generateForm.errors.published_at} hint="Leave blank to use the next automatic publish slot.">
                        <Input type="datetime-local" value={generateForm.data.published_at} onChange={(e) => generateForm.setData('published_at', e.target.value)} />
                    </FormField>}
                    <FormField label="Generation Mode">
                        <div className="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                onClick={() => generateForm.setData('mode', 'instant')}
                                className={`rounded-lg border p-3 text-left transition ${
                                    generateForm.data.mode === 'instant'
                                        ? 'border-indigo-600 bg-indigo-50/60 ring-2 ring-indigo-500/20'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'
                                }`}
                            >
                                <span className="block text-xs font-semibold text-slate-900">Instant</span>
                                <span className="text-[11px] text-slate-500">Wait & open in editor</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => generateForm.setData('mode', 'queue')}
                                className={`rounded-lg border p-3 text-left transition ${
                                    generateForm.data.mode === 'queue'
                                        ? 'border-indigo-600 bg-indigo-50/60 ring-2 ring-indigo-500/20'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'
                                }`}
                            >
                                <span className="block text-xs font-semibold text-slate-900">Queue (Background)</span>
                                <span className="text-[11px] text-slate-500">Runs asynchronously</span>
                            </button>
                        </div>
                    </FormField>
                </form>
            </Modal>
        </>
    );
}
