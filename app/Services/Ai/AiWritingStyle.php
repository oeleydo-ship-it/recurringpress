<?php

namespace App\Services\Ai;

use App\Models\SystemSetting;

/**
 * Writing controls for AI-generated website content (blog posts and CMS pages), set in
 * Superadmin → Blog → AI settings: a list of words and phrases that must never appear, a
 * "write like a person" style, and free-form style notes.
 *
 * The rules go into the prompt; afterwards the text is scanned for banned phrases and, if
 * any slipped through, the model gets one rewrite pass. Whatever still remains is reported
 * back so the editor can fix it by hand.
 */
class AiWritingStyle
{
    /** Phrases that make text read as machine-written. Editable in Superadmin. */
    public const DEFAULT_BANNED = [
        'delve', 'dive into', 'deep dive', 'in today\'s fast-paced world', 'in today\'s digital age', 'in the ever-evolving',
        'ever-changing landscape', 'navigate the complexities', 'it\'s important to note', 'it is important to note',
        'it\'s worth noting', 'in conclusion', 'in summary', 'unlock', 'unleash', 'game-changer', 'game changer',
        'revolutionize', 'elevate', 'seamless', 'seamlessly', 'leverage', 'harness', 'cutting-edge', 'robust',
        'tapestry', 'realm', 'embark', 'journey', 'testament to', 'look no further', 'whether you\'re',
        'furthermore', 'moreover', 'additionally', 'as an ai', 'i hope this helps', 'treasure trove', 'paramount',
        'pivotal', 'crucial role', 'plays a vital role', 'stands as', 'serves as a', 'in the realm of',
    ];

    public static function humanStyle(): bool
    {
        return filter_var(SystemSetting::get('ai_writing_human_style') ?? true, FILTER_VALIDATE_BOOL);
    }

    /** @return string[] lower-case phrases, one per line in the setting */
    public static function banned(): array
    {
        $raw = SystemSetting::get('ai_writing_banned_phrases');

        $lines = $raw === null ? self::DEFAULT_BANNED : preg_split('/[\r\n]+/', (string) $raw);

        return array_values(array_unique(array_filter(array_map(fn ($l) => mb_strtolower(trim((string) $l, " \t,")), $lines))));
    }

    public static function notes(): string
    {
        return trim((string) SystemSetting::get('ai_writing_notes', ''));
    }

    /** Text for the settings form (one phrase per line). */
    public static function bannedText(): string
    {
        return implode("\n", self::banned());
    }

    /** Rules appended to every content-writing system prompt. */
    public static function promptRules(): string
    {
        $rules = [];

        if (self::humanStyle()) {
            $rules[] = implode("\n", [
                'Write the way an experienced person on the team would write it themselves:',
                '- Plain, specific words. Short and long sentences mixed; no filler or throat-clearing openers.',
                '- Speak to the reader as "you" and the business as "we" where it fits. Contractions are fine.',
                '- Concrete examples and practical steps over general claims. Say each point once.',
                '- No hype, no grand statements about the industry, no rhetorical questions as openers.',
                '- Avoid formulaic patterns: lists of exactly three adjectives, "not just X, but Y", and wrapping up every section with a summary line.',
                '- Use em dashes sparingly; prefer commas, full stops or parentheses.',
                '- Never mention AI, that the text was generated, or that you are an assistant.',
            ]);
        }

        if ($banned = self::banned()) {
            $rules[] = 'Never use these words or phrases (or close variants of them), in any field: ' . implode('; ', array_map(fn ($p) => "\"{$p}\"", $banned)) . '.';
        }

        if ($notes = self::notes()) {
            $rules[] = "House style notes from the editor:\n{$notes}";
        }

        return implode("\n\n", $rules);
    }

    /**
     * Banned phrases found in the given text fields (HTML is stripped first).
     *
     * @param  array<string, mixed>  $data
     * @return string[]
     */
    public static function findBanned(array $data, array $fields): array
    {
        $text = mb_strtolower(implode("\n", array_map(
            fn ($f) => html_entity_decode(strip_tags(str_replace('<', ' <', (string) ($data[$f] ?? ''))), ENT_QUOTES | ENT_HTML5),
            $fields,
        )));
        // Curly apostrophes count as straight ones ("it’s" matches "it's").
        $text = str_replace(['’', '‘'], "'", $text);

        return array_values(array_filter(self::banned(), function ($phrase) use ($text) {
            $p = preg_quote($phrase, '/');

            return preg_match('/(?<![\p{L}\p{N}])' . $p . '(?![\p{L}\p{N}])/u', $text) === 1;
        }));
    }

    /**
     * If banned phrases slipped through, ask the model once to rewrite just those parts.
     * Returns the (possibly revised) data and the phrases that still remain.
     *
     * @param  array<string, mixed>  $data  decoded output matching $schema
     * @param  string[]  $fields  fields to scan and rewrite
     * @param  callable(array): array  $clean  post-processing applied to the revision (e.g. HTML cleaning)
     * @return array{0: array<string, mixed>, 1: string[]}
     */
    public static function enforce(AiClient $ai, string $feature, array $data, array $schema, array $fields, callable $clean): array
    {
        $found = self::findBanned($data, $fields);

        if (! $found) {
            return [$data, []];
        }

        try {
            $revised = $ai->json(
                company: null,
                feature: $feature,
                system: implode("\n\n", [
                    'You are an editor. Rewrite the given content so that it no longer contains the listed words or phrases, replacing them with plain, natural wording.',
                    'Keep everything else as it is: meaning, structure, HTML tags, headings, length and every field not affected.',
                    self::promptRules(),
                ]),
                prompt: "Phrases to remove: " . implode('; ', $found) . "\n\n<content>\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n</content>",
                schema: $schema,
                effort: 'low',
                maxTokens: 16000,
            );
            $revised = $clean($revised);
        } catch (AiException) {
            return [$data, $found]; // keep the first draft; the editor sees what to fix
        }

        return [$revised, self::findBanned($revised, $fields)];
    }

    public static function save(bool $humanStyle, string $banned, string $notes): void
    {
        SystemSetting::setMany([
            'ai_writing_human_style' => $humanStyle ? '1' : '0',
            'ai_writing_banned_phrases' => implode("\n", array_filter(array_map('trim', preg_split('/[\r\n]+/', $banned)))),
            'ai_writing_notes' => trim($notes),
        ]);
    }
}
