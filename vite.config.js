import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Three families, each with one job (see docs/DESIGN.md):
            // Public Sans for interface text, Source Serif 4 for titles and figures,
            // IBM Plex Mono for case numbers, timestamps and the audit trail.
            fonts: [
                bunny('Public Sans', {
                    weights: [400, 500, 600, 700],
                    styles: ['normal', 'italic'],
                }),
                bunny('Source Serif 4', {
                    weights: [400, 600],
                }),
                bunny('IBM Plex Mono', {
                    weights: [400, 500],
                }),
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
