@php
    $googleAnalyticsScript = \App\Models\SystemSetting::get('google_analytics_script');
    $livechatScript = \App\Models\SystemSetting::get('livechat_script');
@endphp

@if(!empty($googleAnalyticsScript))
    {!! $googleAnalyticsScript !!}
@endif

@if(!empty($livechatScript))
    {!! $livechatScript !!}
@endif
