import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Les assets sont compilés en local puis déployés : aucun Node en production
// (décision 12). Aucune police distante n'est chargée (vie privée + CSP).
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pdf-analyse.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
});
