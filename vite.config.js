import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/menu.js',
                'resources/js/search.js',
                'resources/js/passkeys.js',
                'resources/js/editor.js',
            ],
            refresh: true,
            fonts: [
                // Headlines and body copy: a sturdy news serif with Greek.
                bunny('Source Serif 4', {
                    weights: [400, 600, 700, 800],
                    styles: ['normal', 'italic'],
                    subsets: ['latin', 'greek'],
                }),
                // Furniture: navigation, labels, bylines — a tight grotesque.
                bunny('Inter Tight', {
                    weights: [400, 500, 600, 700, 800],
                    subsets: ['latin', 'greek'],
                }),
                // Scores, tables and timestamps: condensed figures that stay legible at small sizes.
                bunny('Fira Sans Condensed', {
                    weights: [500, 700],
                    subsets: ['latin', 'greek'],
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
