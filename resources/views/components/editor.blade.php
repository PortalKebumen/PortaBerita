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
        placeholder  (opsional) Teks placeholder editor.
        height       (opsional) Tinggi editor dalam px. Default 420, sama dengan mockup.

    Isi awal editor diisi lewat slot:
        <x-editor name="konten" target="konten-artikel">{{ old('konten', $artikel->konten ?? '') }}</x-editor>
--}}
@props([
    'name',
    'target' => null,
    'placeholder' => 'Tulis konten di sini...',
    'height' => 420,
])

@php
    $target = $target ?? $name;
@endphp

@once
    {{-- TinyMCE 8 self-hosted, dimuat sekali walau ada beberapa <x-editor> di satu halaman --}}
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
    <script src="{{ asset('vendor/tinymce/langs/id.js') }}"></script>

    <script>
        // ===== Konfigurasi baku TinyMCE, disalin dari initRichEditor() di mockup artikel-tambah.html =====
        // Community edition, self-hosted, dibundel offline (tanpa CDN saat runtime). Gratis di bawah GNU GPL v2+.
        window.TINYMCE_CONTENT_STYLE = "/* This file is bundled with the code from the following third party libraries */\n\n/**\n * http://prismjs.com/\n * Dracula Theme originally by Zeno Rocha [@@zenorocha]\n * https://draculatheme.com/\n *\n * Ported for PrismJS by Albert Vallverdu [@@byverdu]\n */\nbody{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif;line-height:1.4;margin:1rem}table{border-collapse:collapse}table:not([cellpadding]) td,table:not([cellpadding]) th{padding:.4rem}table[border]:not([border=\"0\"]):not([style*=border-width]) td,table[border]:not([border=\"0\"]):not([style*=border-width]) th{border-width:1px}table[border]:not([border=\"0\"]):not([style*=border-style]) td,table[border]:not([border=\"0\"]):not([style*=border-style]) th{border-style:solid}table[border]:not([border=\"0\"]):not([style*=border-color]) td,table[border]:not([border=\"0\"]):not([style*=border-color]) th{border-color:#ccc}figure{display:table;margin:1rem auto}figure figcaption{color:#999;display:block;margin-top:.25rem;text-align:center}hr{border-color:#ccc;border-style:solid;border-width:1px 0 0 0}code{background-color:#e8e8e8;border-radius:3px;padding:.1rem .2rem}.mce-content-body:not([dir=rtl]) blockquote{border-left:2px solid #ccc;margin-left:1.5rem;padding-left:1rem}.mce-content-body[dir=rtl] blockquote{border-right:2px solid #ccc;margin-right:1.5rem;padding-right:1rem}\nbody{font-family:'Public Sans',system-ui,sans-serif;font-size:14px;line-height:1.7;color:#171B28;padding:1rem 1.25rem;}\nh1,h2,h3,h4{font-family:'Fraunces',Georgia,serif;font-weight:600;}\nh2{font-size:20px;margin-top:16px;margin-bottom:8px;}\nh3{font-size:16px;margin-top:12px;margin-bottom:6px;}\np{margin-bottom:12px;}\nul{list-style:disc;padding-left:20px;margin-bottom:12px;}\nol{list-style:decimal;padding-left:20px;margin-bottom:12px;}\nblockquote{border-left:3px solid #FF6E11;padding-left:14px;font-style:italic;color:#3B4152;margin:12px 0;}\na{color:#1E398E;text-decoration:underline;}\nimg{border-radius:8px;margin:12px 0;max-width:100%;}\ntable{border-collapse:collapse;width:100%;margin-bottom:12px;}\ntable td,table th{border:1px solid #CDD3DF;padding:6px 10px;}\ncode{background:#F1F3F7;padding:1px 5px;border-radius:4px;font-family:'IBM Plex Mono',monospace;font-size:12.5px;}\npre{background:#0B1C37;color:#DCE2F5;padding:12px 14px;border-radius:8px;overflow-x:auto;font-family:'IBM Plex Mono',monospace;font-size:12.5px;}\nhr{border:none;border-top:1px solid #CDD3DF;margin:16px 0;}\n";

        window.initRichEditor = function (selector, opts) {
            opts = opts || {};
            var config = {
                selector: selector,
                license_key: 'gpl',
                language: 'id',
                // Beda dari mockup: skin TIDAK dimatikan (skin: false) karena di mockup
                // (prototipe statis) CSS skin ditempel manual di <style> halaman, sedangkan
                // di sini TinyMCE dimuat lewat public/vendor/tinymce/ lengkap dengan folder
                // skins/, jadi skin default (oxide) dipakai apa adanya.
                content_css: false,
                content_style: window.TINYMCE_CONTENT_STYLE + (opts.contentStyleExtra || ''),
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
                file_picker_callback: opts.filePickerCallback || function (cb) { cb(''); },
                setup: opts.setup || function () {}
            };
            if (opts.config) {
                for (var k in opts.config) { config[k] = opts.config[k]; }
            }
            return tinymce.init(config);
        };
    </script>
@endonce

<textarea id="{{ $target }}" name="{{ $name }}" rows="10">{{ $slot }}</textarea>

<script>
    document.addEventListener('livewire:init', function () {
        initRichEditor('#{{ $target }}', {
            placeholder: @js($placeholder),
            height: {{ (int) $height }},
            // Tombol gambar di toolbar TinyMCE -> buka komponen Livewire media-picker,
            // dengar event "media-picker-selected", cocokkan target, lalu isi URL-nya
            // ke TinyMCE. Pola ini disalin persis dari
            // resources/views/admin/test-media-picker.blade.php (PK-29).
            filePickerCallback: function (cb, value, meta) {
                if (meta.filetype !== 'image') {
                    return;
                }

                var picker = Livewire.getByName('media-picker')[0];
                if (!picker) {
                    console.error('x-editor: komponen media-picker (Livewire) tidak ditemukan di halaman ini.');
                    return;
                }

                var targetName = @js($target);
                picker.openFor(targetName);

                var cleanup = Livewire.on('media-picker-selected', function (event) {
                    var data = Array.isArray(event) ? event[0] : event;
                    if (data.target !== targetName) {
                        return;
                    }
                    cb(data.media.url, { alt: data.media.alt_text || '' });
                    cleanup();
                });
            }
        });
    });
</script>