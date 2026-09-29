<div
    x-data="{ show: false, type: 'success', text: '', timer: null }"
    x-on:flash-message.window="
        type = $event.detail.type || 'success';
        text = $event.detail.text;
        show = true;
        clearTimeout(timer);
        timer = setTimeout(() => show = false, 4000);
    "
    x-show="show"
    x-transition.opacity.duration.300ms
    :class="{
        'alert-success': type === 'success',
        'alert-danger': type === 'danger' || type === 'error',
        'alert-warning': type === 'warning',
        'alert-info': type === 'info'
    }"
    class="mb-5 items-center"
    style="display: none;"
>
    <template x-if="type === 'success'">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/>
        </svg>
    </template>
    <template x-if="type === 'danger' || type === 'error'">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0">
            <circle cx="12" cy="12" r="9"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9l-6 6M9 9l6 6"/>
        </svg>
    </template>
    <template x-if="type === 'warning'">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
        </svg>
    </template>
    <template x-if="type === 'info'">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0">
            <circle cx="12" cy="12" r="9"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-5M12 8h.01"/>
        </svg>
    </template>
    <div class="font-medium" x-text="text"></div>
</div>