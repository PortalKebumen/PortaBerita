<div
    x-data="{ show: false, type: 'success', text: '' }"
    x-on:flash-message.window="
        type = $event.detail.type;
        text = $event.detail.text;
        show = true;
        clearTimeout(window.__flashTimeout);
        window.__flashTimeout = setTimeout(() => show = false, 4000);
    "
    x-show="show"
    x-transition.opacity.duration.300ms
    :class="type === 'success' ? 'alert-success' : 'alert-danger'"
    class="mb-5"
    style="display: none;"
>
    <span x-text="text"></span>
</div>