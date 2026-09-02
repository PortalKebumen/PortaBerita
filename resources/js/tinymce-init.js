import tinymce from 'tinymce/tinymce';

// Core
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom/model';

// Skin & content CSS (self-hosted, tanpa CDN)
import 'tinymce/skins/ui/oxide/skin.css';
import 'tinymce/skins/ui/oxide/content.css';
import 'tinymce/skins/content/default/content.css';

// Plugin gratis (Community Edition)
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/directionality';
import 'tinymce/plugins/emoticons';
import 'tinymce/plugins/emoticons/js/emojis';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/help';
import 'tinymce/plugins/image';
import 'tinymce/plugins/importcss';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/media';
import 'tinymce/plugins/nonbreaking';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/quickbars';
import 'tinymce/plugins/save';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/accordion';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/autosave';

// Bahasa Indonesia
import 'tinymce-i18n/langs8/id';

// Pre-seed data help-plugin (hindari fetch runtime yang gagal karena offline)
import 'tinymce/plugins/help/js/i18n/keynav/id';
import 'tinymce/plugins/help/js/i18n/keynav/en';

const PLUGINS = [
    'advlist', 'anchor', 'autolink', 'charmap', 'code', 'codesample',
    'directionality', 'emoticons', 'fullscreen', 'help', 'image',
    'importcss', 'insertdatetime', 'link', 'lists', 'media',
    'nonbreaking', 'pagebreak', 'preview', 'quickbars', 'save',
    'searchreplace', 'table', 'visualblocks', 'wordcount',
    'accordion', 'autoresize', 'autosave',
].join(' ');

const TOOLBAR = [
    'undo redo | blocks | bold italic underline | forecolor backcolor',
    'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
    'link image media table | code fullscreen preview | help',
].join(' | ');

export function initRichEditor(selector, opts = {}) {
    return tinymce.init({
        selector,
        license_key: 'gpl',
        language: 'id',
        skin: false,
        content_css: false,
        plugins: PLUGINS,
        toolbar: TOOLBAR,
        menubar: 'edit view insert format table tools help',
        height: 480,
        placeholder: opts.placeholder || '',
        // file_picker_callback dipasang belakangan saat komponen Media Picker
        // sudah dibuat (ticket Artikel/Media), supaya tombol sisip gambar
        // TinyMCE bisa membuka modal Media Picker milik aplikasi.
        ...opts,
    });
}

window.initRichEditor = initRichEditor;