<?php

namespace LucianoTonet\GroqPHP;

/**
 * Helpers for Groq's built-in (server-side) tools and document/RAG features.
 *
 * Preferred path (current API): pass tool type objects via the Chat Completions
 * `tools` parameter on GPT-OSS models (`openai/gpt-oss-20b`, `openai/gpt-oss-120b`):
 *
 *   tools: BuiltInTools::tools([BuiltInTools::BROWSER_SEARCH, BuiltInTools::CODE_INTERPRETER])
 *
 * Legacy path: `compound_custom` / `search_settings` were used with Compound systems
 * (`groq/compound`, `compound-beta`, etc.). Those model IDs were decommissioned
 * (see https://console.groq.com/docs/deprecations). `BuiltInTools::compound()` is
 * retained only for clients that still need the payload shape.
 *
 * Note: `documents` and `citation_options` (RAG) are supported only on models
 * that enable them. The historical RAG models (`llama-3.3-70b-versatile`,
 * `llama-3.1-8b-instant`) were retired on 2026-08-16 for free/developer tiers;
 * this library forwards both parameters as-is and lets the API enforce support.
 *
 * @see https://console.groq.com/docs/tool-use/built-in-tools
 */
class BuiltInTools
{
    /** GPT-OSS built-in browser/web search tool type. */
    public const BROWSER_SEARCH = 'browser_search';

    /**
     * Legacy Compound tool id (prefer BROWSER_SEARCH on GPT-OSS).
     *
     * @deprecated Use BROWSER_SEARCH with BuiltInTools::tools() on GPT-OSS models.
     */
    public const WEB_SEARCH = 'web_search';

    /**
     * Legacy Compound tool id (not available on GPT-OSS).
     *
     * @deprecated Compound systems were decommissioned.
     */
    public const VISIT_WEBSITE = 'visit_website';

    public const CODE_INTERPRETER = 'code_interpreter';

    /**
     * Legacy Compound tool id (not available on GPT-OSS).
     *
     * @deprecated Compound systems were decommissioned.
     */
    public const WOLFRAM_ALPHA = 'wolfram_alpha';

    /**
     * Builds the Chat Completions `tools` array for GPT-OSS built-in tools.
     *
     * @param  string[]  $types  List of tool type identifiers (e.g. BuiltInTools::BROWSER_SEARCH).
     * @return array<int, array{type: string}>
     */
    public static function tools(array $types): array
    {
        return array_map(
            static fn (string $type): array => ['type' => $type],
            array_values($types)
        );
    }

    /**
     * Builds the legacy `compound_custom` parameter for Compound systems.
     *
     * @param  string[]  $enabledTools  List of BuiltInTools::* identifiers.
     * @param  string|null  $version  Optional Compound system version (e.g. "latest").
     * @return array The `compound_custom` payload.
     *
     * @deprecated Compound model IDs were decommissioned. Prefer tools() on GPT-OSS.
     */
    public static function compound(array $enabledTools, ?string $version = null): array
    {
        $custom = [
            'tools' => [
                'enabled_tools' => $enabledTools,
            ],
        ];

        if ($version !== null) {
            $custom['version'] = $version;
        }

        return $custom;
    }

    /**
     * Builds a document from raw text for use in the `documents` parameter.
     *
     * @param  string  $text  The document text.
     * @param  string|null  $id  Optional identifier used for citations.
     * @return array The document payload.
     */
    public static function document(string $text, ?string $id = null): array
    {
        $document = [
            'source' => [
                'type' => 'text',
                'text' => $text,
            ],
        ];

        if ($id !== null) {
            $document['id'] = $id;
        }

        return $document;
    }

    /**
     * Builds a document backed by an uploaded file for use in the `documents`
     * parameter.
     *
     * @param  string  $fileId  The ID of a file uploaded via the Files API.
     * @param  string|null  $id  Optional identifier used for citations.
     * @return array The document payload.
     */
    public static function documentFromFile(string $fileId, ?string $id = null): array
    {
        $document = [
            'source' => [
                'type' => 'resource',
                'file_id' => $fileId,
            ],
        ];

        if ($id !== null) {
            $document['id'] = $id;
        }

        return $document;
    }
}
