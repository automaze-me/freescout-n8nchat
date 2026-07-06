<?php

namespace Modules\N8nChat\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\N8nChat\ConfigBuilder;

if (!defined('N8NCHAT_MODULE')) {
    define('N8NCHAT_MODULE', 'n8nchat');
}

class N8nChatServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
        $this->registerSettings();
        $this->registerWidget();
        $this->registerCsp();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {

    }

    /**
     * Register settings section and Eventy filters.
     */
    public function registerSettings()
    {
        // Add the section to Manage → Settings.
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections['n8nchat'] = ['title' => __('n8n Chat'), 'icon' => 'comment', 'order' => 650];
            return $sections;
        }, 20, 1);

        // Render our view for the section.
        \Eventy::addFilter('settings.view', function ($view, $section) {
            return $section === 'n8nchat' ? 'n8nchat::settings' : $view;
        }, 20, 2);

        // Provide the option values (and defaults) for the section.
        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section !== 'n8nchat') {
                return $settings;
            }
            return [
                'n8nchat.enabled'           => \Option::get('n8nchat.enabled', config('n8nchat.options.enabled.default')),
                'n8nchat.webhook_url'       => \Option::get('n8nchat.webhook_url', config('n8nchat.options.webhook_url.default')),
                'n8nchat.shared_secret'     => \Helper::decrypt(\Option::get('n8nchat.shared_secret', config('n8nchat.options.shared_secret.default'))),
                'n8nchat.secret_header'     => \Option::get('n8nchat.secret_header', config('n8nchat.options.secret_header.default')),
                'n8nchat.title'             => \Option::get('n8nchat.title', config('n8nchat.options.title.default')),
                'n8nchat.greeting'          => \Option::get('n8nchat.greeting', config('n8nchat.options.greeting.default')),
                'n8nchat.input_placeholder' => \Option::get('n8nchat.input_placeholder', config('n8nchat.options.input_placeholder.default')),
            ];
        }, 20, 2);

        // Validation: webhook_url must be a URL when provided.
        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section !== 'n8nchat') {
                return $params;
            }
            $params['validator_rules'] = [
                'settings.n8nchat\\.webhook_url' => 'nullable|url',
            ];
            $params['settings'] = [
                'n8nchat.shared_secret' => [
                    'safe_password' => true,
                    'encrypt'       => true,
                ],
            ];
            return $params;
        }, 20, 2);
    }

    /**
     * Register the widget hook.
     */
    public function registerWidget()
    {
        \Eventy::addAction('layout.body_bottom', [$this, 'renderWidget'], 20, 0);
    }

    /**
     * Allow the widget to reach the configured n8n webhook host under FreeScout's CSP.
     *
     * FreeScout's CSP defines no explicit connect-src, so XHR/fetch fall back to
     * default-src ('self'). The csp.script_src filter adds the webhook host to
     * default-src (and script-src), widening the connect fallback to include the
     * n8n origin without overriding the global connect policy.
     */
    public function registerCsp()
    {
        \Eventy::addFilter('csp.script_src', function ($sources) {
            if (!\Option::get('n8nchat.enabled', config('n8nchat.options.enabled.default'))) {
                return $sources;
            }
            $url = \Option::get('n8nchat.webhook_url', config('n8nchat.options.webhook_url.default'));
            $parts = $url ? parse_url($url) : [];
            if (!empty($parts['scheme']) && !empty($parts['host'])) {
                $sources = trim($sources.' '.$parts['scheme'].'://'.$parts['host']);
            }
            return $sources;
        }, 20, 1);
    }

    /**
     * Render the chat widget at the bottom of every authenticated page.
     */
    public function renderWidget()
    {
        if (!\Auth::check()) {
            return;
        }
        if (!\Option::get('n8nchat.enabled', config('n8nchat.options.enabled.default'))) {
            return;
        }
        $webhook_url = \Option::get('n8nchat.webhook_url', config('n8nchat.options.webhook_url.default'));
        if (empty($webhook_url)) {
            return;
        }

        $settings = [
            'webhook_url'       => $webhook_url,
            'shared_secret'     => \Helper::decrypt(\Option::get('n8nchat.shared_secret', config('n8nchat.options.shared_secret.default'))),
            'secret_header'     => \Option::get('n8nchat.secret_header', config('n8nchat.options.secret_header.default')),
            'title'             => \Option::get('n8nchat.title', config('n8nchat.options.title.default')),
            'greeting'          => \Option::get('n8nchat.greeting', config('n8nchat.options.greeting.default')),
            'input_placeholder' => \Option::get('n8nchat.input_placeholder', config('n8nchat.options.input_placeholder.default')),
        ];

        $user = \Auth::user();
        $agent = [
            'id'    => $user->id,
            'name'  => $user->getFullName(),
            'email' => $user->email,
            'role'  => $user->isAdmin() ? 'admin' : 'user',
        ];

        $conversation = null;
        if (\Route::currentRouteName() === 'conversations.view') {
            $conv = \App\Conversation::find(request()->route('id'));
            if ($conv && $user->can('viewCached', $conv)) {
                $conversation = [
                    'id'       => $conv->id,
                    'number'   => $conv->number,
                    'subject'  => $conv->subject,
                    'status'   => $conv->getStatusName(),
                    'mailbox'  => ['id' => $conv->mailbox_id, 'name' => optional($conv->mailbox)->name],
                    'customer' => [
                        'name'  => $conv->customer ? $conv->customer->getFullName(true) : '',
                        'email' => $conv->customer_email,
                    ],
                    'assignee' => optional($conv->user)->getFullName(),
                ];
            }
        }

        $config = ConfigBuilder::build($agent, $conversation, $settings);

        echo view('n8nchat::widget', [
            'config'        => $config,
            'module_public' => \Module::getPublicPath(N8NCHAT_MODULE),
        ])->render();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('n8nchat.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'n8nchat'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/n8nchat');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/n8nchat';
        }, \Config::get('view.paths')), [$sourcePath]), 'n8nchat');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
