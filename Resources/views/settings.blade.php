<form class="form-horizontal margin-top" method="POST" action="">
    {{ csrf_field() }}

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Enabled') }}</label>
    <div class="col-sm-6">
        <div class="controls">
            <div class="onoffswitch-wrap">
                <div class="onoffswitch">
                    <input type="checkbox" name="settings[n8nchat.enabled]" value="1" id="n8nchat_enabled"
                        class="onoffswitch-checkbox" @if (old('settings.n8nchat.enabled', $settings['n8nchat.enabled'])) checked @endif>
                    <label class="onoffswitch-label" for="n8nchat_enabled"></label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-group{{ $errors->has('settings.n8nchat\.webhook_url') ? ' has-error' : '' }}">
    <label class="col-sm-2 control-label">{{ __('Webhook URL') }}</label>
    <div class="col-sm-6">
        <input type="url" class="form-control" name="settings[n8nchat.webhook_url]"
            value="{{ old('settings.n8nchat.webhook_url', $settings['n8nchat.webhook_url']) }}"
            placeholder="https://n8n.example.com/webhook/xxxx/chat">
        <p class="help-block">{{ __('The production URL of your n8n Chat Trigger node. Add your FreeScout domain to the node\'s CORS allowlist.') }}</p>
        @include('partials/field_error', ['field'=>'settings.n8nchat\.webhook_url'])
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Shared Secret') }}</label>
    <div class="col-sm-6">
        <input type="password" class="form-control" name="settings[n8nchat.shared_secret]"
            value="{{ old('settings.n8nchat.shared_secret', \Helper::safePassword($settings['n8nchat.shared_secret'])) }}" autocomplete="new-password">
        <p class="help-block">{{ __('Optional. Sent as an HTTP header so your workflow can verify requests. Visible to logged-in agents (client-side).') }}</p>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Secret Header Name') }}</label>
    <div class="col-sm-6">
        <input type="text" class="form-control" name="settings[n8nchat.secret_header]"
            value="{{ old('settings.n8nchat.secret_header', $settings['n8nchat.secret_header']) }}">
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Window Title') }}</label>
    <div class="col-sm-6">
        <input type="text" class="form-control" name="settings[n8nchat.title]"
            value="{{ old('settings.n8nchat.title', $settings['n8nchat.title']) }}">
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Greeting') }}</label>
    <div class="col-sm-6">
        <input type="text" class="form-control" name="settings[n8nchat.greeting]"
            value="{{ old('settings.n8nchat.greeting', $settings['n8nchat.greeting']) }}">
        <p class="help-block">{{ __('Optional first message shown by the assistant.') }}</p>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Input Placeholder') }}</label>
    <div class="col-sm-6">
        <input type="text" class="form-control" name="settings[n8nchat.input_placeholder]"
            value="{{ old('settings.n8nchat.input_placeholder', $settings['n8nchat.input_placeholder']) }}">
    </div>
</div>

<div class="form-group margin-top">
    <div class="col-sm-6 col-sm-offset-2">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </div>
</div>

</form>
