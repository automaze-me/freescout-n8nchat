<link rel="stylesheet" href="{{ asset($module_public.'/css/style.css') }}">
<link rel="stylesheet" href="{{ asset($module_public.'/css/overrides.css') }}">
<script {!! \Helper::cspNonceAttr() !!}>window.N8nChatConfig = {!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};</script>
<script type="module" src="{{ asset($module_public.'/js/loader.js') }}" {!! \Helper::cspNonceAttr() !!}></script>
