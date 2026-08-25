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
     * Ensures document() and documentFromFile() build valid document payloads.
     */
    public function test_document_builders(): void
    {
        $textDoc = BuiltInTools::document('Context text', 'doc-1');
        $this->assertSame('text', $textDoc['source']['type']);
        $this->assertSame('Context text', $textDoc['source']['text']);
        $this->assertSame('doc-1', $textDoc['id']);

        $fileDoc = BuiltInTools::documentFromFile('file-abc', 'doc-2');
        $this->assertSame('resource', $fileDoc['source']['type']);
        $this->assertSame('file-abc', $fileDoc['source']['file_id']);
        $this->assertSame('doc-2', $fileDoc['id']);
    }

    /**
     * Ensures compound_custom is forwarded to the API request.
     */
    public function test_compound_custom_is_sent(): void
    {
        $this->groq->chat()->completions()->create([
            'model' => 'groq/compound',
            'messages' => [
                ['role' => 'user', 'content' => 'Search for recent AI news'],
            ],
            'compound_custom' => BuiltInTools::compound([BuiltInTools::WEB_SEARCH]),
        ]);

        $request = MockRouter::lastChatRequest();
        $this->assertArrayHasKey('compound_custom', $request);
        $this->assertSame(
            ['tools' => ['enabled_tools' => ['web_search']]],
            $request['compound_custom']
        );
    }

    /**
     * Ensures documents, search_settings and citation_options are forwarded.
     */
    public function test_documents_and_search_settings_are_sent(): void
    {
        $this->groq->chat()->completions()->create([
            'model' => 'groq/compound',
            'messages' => [
                ['role' => 'user', 'content' => 'Summarize the document'],
            ],
            'documents' => [BuiltInTools::document('Important context', 'doc-1')],
            'search_settings' => ['exclude_domains' => ['wikipedia.org']],
            'citation_options' => 'enabled',
        ]);

        $request = MockRouter::lastChatRequest();
        $this->assertArrayHasKey('documents', $request);
        $this->assertSame('text', $request['documents'][0]['source']['type']);
        $this->assertSame('doc-1', $request['documents'][0]['id']);

        $this->assertArrayHasKey('search_settings', $request);
        $this->assertSame(['wikipedia.org'], $request['search_settings']['exclude_domains']);

        $this->assertArrayHasKey('citation_options', $request);
        $this->assertSame('enabled', $request['citation_options']);
    }
}
