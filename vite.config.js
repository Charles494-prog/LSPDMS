import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const viteHost = env.VITE_DEV_SERVER_HOST || 'localhost';
    const appOrigin = env.VITE_APP_ORIGIN || env.APP_URL || 'http://localhost';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/messaging.js', 'resources/js/chat-notifications.js', 'resources/js/dashboard.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host: true,
            port: 4000,
            origin: `http://${viteHost}:4000`,
            cors: {
                origin: appOrigin,
            },
            hmr: {
                host: viteHost,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
