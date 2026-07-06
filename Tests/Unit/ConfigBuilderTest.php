<?php

namespace Modules\N8nChat\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\N8nChat\ConfigBuilder;

require_once __DIR__.'/../../ConfigBuilder.php';

class ConfigBuilderTest extends TestCase
{
    private function agent(): array
    {
        return ['id' => 7, 'name' => 'Jane Doe', 'email' => 'jane@x.com', 'role' => 'user'];
    }

    private function settings(array $overrides = []): array
    {
        return array_merge([
            'webhook_url'       => 'https://n8n.example.com/webhook/abc/chat',
            'auth_username'     => '',
            'auth_password'     => '',
            'streaming'         => false,
            'title'             => '',
            'subtitle'          => '',
            'greeting'          => '',
            'input_placeholder' => '',
        ], $overrides);
    }

    public function testSubtitleMapsToI18n(): void
    {
        $bare = ConfigBuilder::build($this->agent(), null, $this->settings());
        $this->assertArrayNotHasKey('i18n', $bare['options']);

        $c = ConfigBuilder::build($this->agent(), null, $this->settings(['subtitle' => 'Ask us anything']));
        $this->assertSame('Ask us anything', $c['options']['i18n']['en']['subtitle']);
    }

    public function testStreamingOptionOnlyWhenEnabled(): void
    {
        $off = ConfigBuilder::build($this->agent(), null, $this->settings());
        $this->assertArrayNotHasKey('enableStreaming', $off['options']);

        $on = ConfigBuilder::build($this->agent(), null, $this->settings(['streaming' => true]));
        $this->assertTrue($on['options']['enableStreaming']);
    }

    private function conversation(): array
    {
        return [
            'id' => 123, 'number' => 456, 'subject' => 'Refund request', 'status' => 'active',
            'mailbox' => ['id' => 2, 'name' => 'Support'],
            'customer' => ['name' => 'Bob', 'email' => 'bob@x.com'],
            'assignee' => 'Jane Doe',
        ];
    }

    public function testSessionIdIsGeneralWithoutConversation(): void
    {
        $c = ConfigBuilder::build($this->agent(), null, $this->settings());
        $this->assertSame('fs-user-7-general', $c['sessionId']);
    }

    public function testSessionIdIncludesConversationWhenPresent(): void
    {
        $c = ConfigBuilder::build($this->agent(), $this->conversation(), $this->settings());
        $this->assertSame('fs-user-7-conv-123', $c['sessionId']);
    }

    public function testWebhookUrlAndBaseOptions(): void
    {
        $c = ConfigBuilder::build($this->agent(), null, $this->settings());
        $this->assertSame('https://n8n.example.com/webhook/abc/chat', $c['webhookUrl']);
        $this->assertSame('window', $c['options']['mode']);
        $this->assertTrue($c['options']['loadPreviousSession']);
        $this->assertSame('sessionId', $c['options']['chatSessionKey']);
    }

    public function testNoHeadersWhenNoUsername(): void
    {
        $c = ConfigBuilder::build($this->agent(), null, $this->settings());
        $this->assertSame([], $c['headers']);

        // Password without a username sends nothing.
        $c = ConfigBuilder::build($this->agent(), null, $this->settings(['auth_password' => 'p']));
        $this->assertSame([], $c['headers']);
    }

    public function testBasicAuthHeaderWhenCredentialsSet(): void
    {
        $c = ConfigBuilder::build($this->agent(), null, $this->settings([
            'auth_username' => 'agent', 'auth_password' => 's3cret',
        ]));
        $this->assertSame(
            ['Authorization' => 'Basic '.base64_encode('agent:s3cret')],
            $c['headers']
        );
    }

    public function testMetadataContainsAgentAndConversation(): void
    {
        $c = ConfigBuilder::build($this->agent(), $this->conversation(), $this->settings());
        $this->assertSame(7, $c['metadata']['agent']['id']);
        $this->assertSame(123, $c['metadata']['conversation']['id']);
        $this->assertSame('Refund request', $c['metadata']['conversation']['subject']);
    }

    public function testMetadataHasNoConversationKeyWhenAbsent(): void
    {
        $c = ConfigBuilder::build($this->agent(), null, $this->settings());
        $this->assertArrayNotHasKey('conversation', $c['metadata']);
    }

    public function testBasicAuthAllowsEmptyPassword(): void
    {
        $c = ConfigBuilder::build($this->agent(), null, $this->settings([
            'auth_username' => 'agent',
        ]));
        $this->assertSame(
            ['Authorization' => 'Basic '.base64_encode('agent:')],
            $c['headers']
        );
    }

    public function testBrandingOptionsOnlyWhenProvided(): void
    {
        $bare = ConfigBuilder::build($this->agent(), null, $this->settings());
        $this->assertArrayNotHasKey('initialMessages', $bare['options']);
        $this->assertArrayNotHasKey('i18n', $bare['options']);

        $branded = ConfigBuilder::build($this->agent(), null, $this->settings([
            'title' => 'Support AI', 'greeting' => 'Hi!', 'input_placeholder' => 'Ask…',
        ]));
        $this->assertSame(['Hi!'], $branded['options']['initialMessages']);
        $this->assertSame('Support AI', $branded['options']['i18n']['en']['title']);
        $this->assertSame('Ask…', $branded['options']['i18n']['en']['inputPlaceholder']);
    }
}
