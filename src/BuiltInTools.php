<?php

namespace LucianoTonet\GroqPHP;

/**
 * Helpers for Groq's built-in (server-side) tools, available on the Compound
 * systems (`compound-beta`, `compound-beta-mini`) via the `compound_custom`
 * request parameter. See https://console.groq.com/docs/compound/built-in-tools.
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
     * @param  string[]  $enabledTools  List of BuiltInTools::* identifiers.
     * @param  string|null  $version  Optional Compound system version (e.g. "latest").
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
}
