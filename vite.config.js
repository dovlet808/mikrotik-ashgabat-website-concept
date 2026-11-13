import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

/*
 | Fonts are downloaded at build time and served from /build, so the public
 | site makes no third-party font requests (important on slow connections in
 | Turkmenistan). Subsets cover Russian (cyrillic) and Turkmen (latin-ext).
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Onest', {
                    weights: [400, 500, 600],
                    subsets: ['latin', 'latin-ext', 'cyrillic'],
                    preload: [{ weight: 400 }, { weight: 600 }],
                }),
                bunny('JetBrains Mono', {
                    weights: [400, 500],
                    subsets: ['latin', 'latin-ext', 'cyrillic'],
                    preload: false,
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
