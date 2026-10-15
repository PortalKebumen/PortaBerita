{{-- Anti-spam tanpa CAPTCHA (lihat App\Http\Middleware\ProtectAgainstSpam). Tidak terlihat & tidak bisa difokus oleh pengguna. --}}
<div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">
    <label for="hp-{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}">Biarkan kosong</label>
    <input type="text" id="hp-{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}" name="{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Http\Middleware\ProtectAgainstSpam::TIME_FIELD }}" value="{{ encrypt(now()->getTimestamp()) }}">
