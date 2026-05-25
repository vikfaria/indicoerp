import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'node:path';
import { glob } from 'glob';

const workdoPackages = glob.sync('packages/workdo/*/src/Resources/js/app.tsx');

export default defineConfig({
    base: './',
    plugins: [
        laravel({
            input:
            [
                'resources/css/app.css',
                'resources/js/app.tsx',
                ...workdoPackages
            ],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: 'localhost',
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers': '*',
        },
        watch: {
            ignored: ['**/vendor/**', '**/node_modules/**']
        },
        fs: {
            allow: ['..', 'packages']
        }
    },

    esbuild: {
        jsx: 'automatic',
        jsxImportSource: 'react',
    },
    resolve: {
        alias: {
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('/node_modules/react/') || id.includes('/node_modules/react-dom/') || id.includes('/node_modules/scheduler/')) {
                        return 'vendor-react';
                    }

                    if (id.includes('/node_modules/lucide-react/')) {
                        return 'vendor-icons';
                    }

                    if (id.includes('/node_modules/html2pdf.js/')) {
                        return 'vendor-pdf';
                    }

                    if (id.includes('/node_modules/recharts/')) {
                        return 'vendor-charts';
                    }

                    if (id.includes('/node_modules/@fullcalendar/')) {
                        return 'vendor-calendar';
                    }

                    if (id.includes('/node_modules/@tiptap/')) {
                        return 'vendor-editor';
                    }

                    if (id.includes('/node_modules/react-datepicker/')) {
                        return 'vendor-datepicker';
                    }

                    if (id.includes('/node_modules/@radix-ui/')) {
                        return 'vendor-radix';
                    }

                    if (id.includes('/node_modules/date-fns/') || id.includes('/node_modules/clsx/')) {
                        return 'vendor-utils';
                    }

                    return;
                }
            },
        },
        assetsDir: 'assets',
    }
});
