{{-- Bot trap: see App\Http\Middleware\ProtectAgainstSpam. Humans never see or fill this. --}}
<div aria-hidden="true" style="position:absolute !important;left:-10000px !important;top:auto;width:1px;height:1px;overflow:hidden;">
    <label for="{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}">Leave this field empty</label>
    <input type="text" name="{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}" id="{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Http\Middleware\ProtectAgainstSpam::TIMESTAMP_FIELD }}" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
