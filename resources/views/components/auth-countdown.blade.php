@props(['until', 'message'])
@php($remaining = max(0, (int) $until - now()->timestamp))
@if ($remaining > 0)
    <div class="form-error-text mb-4" role="alert" data-auth-countdown="{{ $remaining }}" style="color: #dc2626;">
        {{ $message }} Tunggu <span data-seconds>{{ $remaining }}</span> detik sebelum mencoba lagi.
    </div>
    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-auth-countdown]').forEach((notice) => {
                    const deadline = Date.now() + Number(notice.dataset.authCountdown) * 1000;
                    const seconds = notice.querySelector('[data-seconds]');
                    const timer = setInterval(() => {
                        const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                        seconds.textContent = remaining;
                        if (remaining === 0) {
                            clearInterval(timer);
                            notice.textContent = 'Waktu tunggu selesai. Silakan coba lagi.';
                        }
                    }, 250);
                });
            });
        </script>
    @endonce
@endif
