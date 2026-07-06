<?php

namespace Modules\N8nChat;

class ConfigBuilder
{
    /**
     * Build the JS widget config object consumed by Public/js/loader.js.
     */
    public static function build(array $agent, ?array $conversation, array $settings): array
    {
        $session_id = 'fs-user-'.$agent['id'].'-'.
            ($conversation ? 'conv-'.$conversation['id'] : 'general');

        $headers = [];
        if (!empty($settings['shared_secret'])) {
            $header_name = $settings['secret_header'] ?: 'X-Freescout-Secret';
            $headers[$header_name] = $settings['shared_secret'];
        }

        $metadata = ['agent' => $agent];
        if ($conversation) {
            $metadata['conversation'] = $conversation;
        }

        $options = [
            'mode'                => 'window',
            'loadPreviousSession' => true,
            'chatSessionKey'      => 'sessionId',
        ];
        if (!empty($settings['streaming'])) {
            $options['enableStreaming'] = true;
        }
        if (!empty($settings['greeting'])) {
            $options['initialMessages'] = [$settings['greeting']];
        }
        $en = [];
        if (!empty($settings['title'])) {
            $en['title'] = $settings['title'];
        }
        if (!empty($settings['subtitle'])) {
            $en['subtitle'] = $settings['subtitle'];
        }
        if (!empty($settings['input_placeholder'])) {
            $en['inputPlaceholder'] = $settings['input_placeholder'];
        }
        if ($en) {
            $options['i18n'] = ['en' => $en];
        }

        return [
            'webhookUrl' => $settings['webhook_url'],
            'headers'    => $headers,
            'sessionId'  => $session_id,
            'metadata'   => $metadata,
            'options'    => $options,
        ];
    }
}
