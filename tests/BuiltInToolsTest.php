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

    /**
     * Ensures document() and documentFromFile() build the expected payloads.
     */
    public function test_document_builders(): void
    {
        $textDoc = BuiltInTools::document('Groq is a fast inference platform.', 'doc-1');
        $this->assertSame(
            ['source' => ['type' => 'text', 'text' => 'Groq is a fast inference platform.'], 'id' => 'doc-1'],
            $textDoc
        );

        $fileDoc = BuiltInTools::documentFromFile('file-abc', 'doc-2');
        $this->assertSame(
            ['source' => ['type' => 'resource', 'file_id' => 'file-abc'], 'id' => 'doc-2'],
            $fileDoc
        );
    }

    /**
     * Ensures documents and citation_options are forwarded to the request body.
     *
     * RAG via `documents` is only supported on models that enable it; on this
     * account (free/developer tier) no model supports it, so this assertion runs
     * in mock mode only.
     */
    public function test_documents_is_sent(): void
    {
        if ($this->live) {
            $this->markTestSkipped('No model on this account supports documents (Rag).');
        }

        $params = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [
                ['role' => 'user', 'content' => 'Summarize the provided document'],
            ],
            'documents' => [
                BuiltInTools::document('Groq is a fast inference platform.', 'doc-1'),
            ],
            'citation_options' => 'enabled',
        ];

        $this->groq->chat()->completions()->create($params);
        $request = MockRouter::lastChatRequest();
        $this->assertArrayHasKey('documents', $request);
        $this->assertSame('doc-1', $request['documents'][0]['id']);
        $this->assertSame('enabled', $request['citation_options']);
    }
}
