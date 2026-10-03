{{--
    Komponen editor WYSIWYG (PK-28).
    Membungkus TinyMCE 8 self-hosted dari public/vendor/tinymce/ menjadi komponen
    reusable, dengan konfigurasi identik dengan initRichEditor() di mockup
    artikel-tambah.html, dan tombol sisip gambar yang terhubung ke komponen
    komponen Livewire media-picker (PK-29).

    Wajib: halaman yang memakai <x-editor> harus juga memuat
    komponen Livewire media-picker (tag livewire:media-picker) satu kali di suatu tempat pada halaman yang sama,
    beserta @livewireStyles dan @livewireScripts.

    Pemakaian:
        <x-editor name="konten" target="konten-artikel" placeholder="Tulis isi artikel di sini..." />

    Props:
        name         (wajib)  Nilai atribut "name" pada <textarea>, dipakai saat form disubmit.
        target       (opsional) ID unik untuk <textarea> ini sekaligus "target" yang dikirim
                     ke Media Picker. Default: sama dengan $name.
        bind         (opsional) Nama properti Livewire yang diikat dua arah (mis. "content").
                     Jika diisi, isi editor otomatis disinkronkan ke properti tersebut
                     setiap kali berubah dan setiap kali form disubmit.
        placeholder  (opsional) Teks placeholder editor.
        height       (opsional) Tinggi editor dalam px. Default 420, sama dengan mockup.

    Pemakaian dengan Livewire (dua arah):
        <x-editor name="content" bind="content" placeholder="Tulis isi artikel di sini..." />

    Isi awal editor diisi lewat slot:
        <x-editor name="konten" target="konten-artikel">{{ old('konten', $artikel->konten ?? '') }}</x-editor>
--}}
@props([
    'name',
    'target' => null,
    'bind' => null,
    'value' => null,
    'placeholder' => 'Tulis konten di sini...',
    'height' => 420,
])

@php
    $target = $target ?? $name;
    $editorId = 'editor-' . $target . '-' . uniqid();
@endphp

<div wire:ignore>
    <textarea id="{{ $target }}" name="{{ $name }}" class="tinymce-editor" rows="10">{!! $value ?? $slot !!}</textarea>
</div>

@push('scripts')
@once('tinymce-core')
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
<script src="{{ asset('vendor/tinymce/langs/id.js') }}"></script>
<script>
// Global TinyMCE configuration helper
window.TINYMCE_CONTENT_STYLE = "body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif;line-height:1.4;margin:1rem}table{border-collapse:collapse}table:not([cellpadding]) td,table:not([cellpadding]) th{padding:.4rem}";

window.initRichEditor = function(selector, opts) {
    opts = opts || {};
    return tinymce.init({
        selector: selector,
        license_key: 'gpl',
        language: 'id',
        content_css: false,
        content_style: window.TINYMCE_CONTENT_STYLE,
        height: opts.height || 420,
        menubar: 'edit view insert format tools table help',
        plugins: 'accordion advlist anchor autolink autoresize charmap code codesample directionality emoticons fullscreen help image importcss insertdatetime link lists media nonbreaking pagebreak preview quickbars searchreplace table visualblocks visualchars wordcount',
        toolbar: [
            'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough subscript superscript | forecolor backcolor removeformat',
            'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | blockquote hr | link unlink image media table',
            'charmap emoticons codesample insertdatetime anchor pagebreak | ltr rtl | searchreplace visualblocks visualchars | fullscreen preview code help'
        ],
        toolbar_mode: 'sliding',
        quickbars_selection_toolbar: 'bold italic | quicklink blockquote',
        quickbars_insert_toolbar: 'image media table',
        placeholder: opts.placeholder || 'Tulis konten di sini...',
        branding: true,
        promotion: false,
        file_picker_types: 'image',
        file_picker_callback: opts.filePickerCallback || function(cb) { cb(''); },
        setup: opts.setup || function() {}
    });
};
</script>
@endonce

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var targetId = @js($target);
        var targetSelector = '#' + targetId;
        var bindName = @js($bind);
        var placeholderText = @js($placeholder);
        var heightPx = {{ (int) $height }};
        
        if (typeof tinymce === 'undefined') {
            console.error('x-editor: TinyMCE not loaded');
            return;
        }

        var rootEl = document.getElementById(targetId);
        if (!rootEl) {
            console.error('x-editor: textarea #' + targetId + ' not found');
            return;
        }

        var hostEl = rootEl.closest('[wire\\:id]');
        var wireId = hostEl ? hostEl.getAttribute('wire:id') : null;

        initRichEditor(targetSelector, {
            placeholder: placeholderText,
            height: heightPx,
            setup: function(editor) {
                // Sync to Livewire
                function syncToLivewire() {
                    if (!bindName || !wireId || typeof Livewire === 'undefined') {
                        return;
                    }
                    var component = Livewire.find(wireId);
                    if (component) {
                        component.set(bindName, editor.getContent());
                    }
                }

                editor.on('change input undo redo', syncToLivewire);
                editor.on('init', syncToLivewire);
            },
            filePickerCallback: function(cb, value, meta) {
                if (meta.filetype !== 'image' || typeof Livewire === 'undefined') {
                    return;
                }

                var picker = Livewire.getByName('media-picker')[0];
                if (!picker) {
                    console.error('x-editor: media-picker component not found');
                    return;
                }

                picker.openFor(targetId);

                var cleanup = Livewire.on('media-picker-selected', function(event) {
                    var data = Array.isArray(event) ? event[0] : event;
                    if (data.target !== targetId) {
                        return;
                    }
                    cb(data.media.url, { alt: data.media.alt_text || '' });
                    cleanup();
                });
            }
        });
    }, 200);
});
</script>
@endpush
