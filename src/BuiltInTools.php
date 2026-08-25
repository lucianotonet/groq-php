<?php

namespace LucianoTonet\GroqPHP;

/**
 * Helpers for Groq's built-in (server-side) tools and document/RAG features.
 *
 * These features are exposed on the Chat Completions endpoint via the
 * `compound_custom`, `search_settings`, `citation_options` and `documents`
 * request parameters. See https://console.groq.com/docs/compound/built-in-tools
 * and the Chat Completions API reference.
 */
class BuiltInTools
{
    public const WEB_SEARCH = 'web_search';
    public const VISIT_WEBSITE = 'visit_website';
    public const CODE_INTERPRETER = 'code_interpreter';
    public const WOLFRAM_ALPHA = 'wolfram_alpha';

    /**
     * Builds the `compound_custom` parameter to restrict which built-in tools
     * a Compound system is allowed to use.
     *
     * @param string[] $enabledTools List of BuiltInTools::* identifiers.
     * @param string|null $version Optional Compound system version (e.g. "latest").
     * @return array The `compound_custom` payload.
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
     * @param string $text The document text.
     * @param string|null $id Optional identifier used for citations.
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
     * @param string $fileId The ID of a file uploaded via the Files API.
     * @param string|null $id Optional identifier used for citations.
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
