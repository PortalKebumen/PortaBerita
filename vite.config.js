import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/tinymce-init.js', 'resources/js/admin.js'],
            refresh: true,
            fonts: [
                bunny('Public Sans', { weights: [400, 500, 600, 700] }),
                bunny('Fraunces', { weights: [500, 600] }),
                bunny('IBM Plex Mono', { weights: [400, 500] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});