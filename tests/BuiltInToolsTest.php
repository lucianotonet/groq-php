<?php

namespace LucianoTonet\GroqPHP\Tests;

use LucianoTonet\GroqPHP\BuiltInTools;

class BuiltInToolsTest extends TestCase
{
    /**
     * Ensures the built-in tool identifiers are exposed as constants.
     */
    public function test_tool_identifiers(): void
    {
        $this->assertSame('web_search', BuiltInTools::WEB_SEARCH);
        $this->assertSame('visit_website', BuiltInTools::VISIT_WEBSITE);
        $this->assertSame('code_interpreter', BuiltInTools::CODE_INTERPRETER);
        $this->assertSame('wolfram_alpha', BuiltInTools::WOLFRAM_ALPHA);
    }

    /**
     * Ensures compound() builds the compound_custom payload correctly.
     */
    public function test_compound_custom_builder(): void
    {
        $payload = BuiltInTools::compound([BuiltInTools::WEB_SEARCH, BuiltInTools::CODE_INTERPRETER]);

        $this->assertSame(
            ['tools' => ['enabled_tools' => ['web_search', 'code_interpreter']]],
            $payload
        );

        $withVersion = BuiltInTools::compound([BuiltInTools::WEB_SEARCH], 'latest');
        $this->assertSame('latest', $withVersion['version']);
    }

    /**
     * Ensures compound_custom is forwarded to the API request.
     *
     * The Compound systems are exposed on the `compound-beta` / `compound-beta-mini`
     * models. In mock mode we assert the captured request body; in live mode we
     * assert the API returns a well-formed completion.
     */
    public function test_compound_custom_is_sent(): void
    {
        $params = [
            'model' => 'compound-beta',
            'messages' => [
                ['role' => 'user', 'content' => 'Search for recent AI news'],
            ],
            'compound_custom' => BuiltInTools::compound([BuiltInTools::WEB_SEARCH]),
        ];

        if ($this->live) {
            $response = $this->groq->chat()->completions()->create($params);
            $this->assertArrayHasKey('choices', $response);

            return;
        }

        $this->groq->chat()->completions()->create($params);
        $request = MockRouter::lastChatRequest();
        $this->assertArrayHasKey('compound_custom', $request);
        $this->assertSame(
            ['tools' => ['enabled_tools' => ['web_search']]],
            $request['compound_custom']
        );
    }

    /**
     * Ensures search_settings is forwarded to the API request.
     */
    public function test_search_settings_is_sent(): void
    {
        $params = [
            'model' => 'compound-beta',
            'messages' => [
                ['role' => 'user', 'content' => 'Search for recent AI news'],
            ],
            'compound_custom' => BuiltInTools::compound([BuiltInTools::WEB_SEARCH]),
            'search_settings' => ['exclude_domains' => ['wikipedia.org']],
        ];

        if ($this->live) {
            $response = $this->groq->chat()->completions()->create($params);
            $this->assertArrayHasKey('choices', $response);

            return;
        }

        $this->groq->chat()->completions()->create($params);
        $request = MockRouter::lastChatRequest();
        $this->assertArrayHasKey('search_settings', $request);
        $this->assertSame(['wikipedia.org'], $request['search_settings']['exclude_domains']);
    }
}
