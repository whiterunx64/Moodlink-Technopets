import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import obfuscator from 'vite-plugin-javascript-obfuscator';

export default defineConfig(({ command }) => {
    const isBuild = command === 'build';

    return {
    server: {
        host: '0.0.0.0',
        cors: true,
        hmr: {
            host: '192.168.8.103',
        },
    },

    build: {

        minify: 'esbuild',
        sourcemap: false,
        modulePreload: false,
    },

    esbuild: {
        legalComments: 'none',
        drop: ['console', 'debugger'],
    },

    plugins: [
        tailwindcss(),
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        isBuild &&
            obfuscator({
                include: ['**/*.js'],
                exclude: [/node_modules/],
                apply: 'build',
                options: {
                    compact: true,
                    identifierNamesGenerator: 'hexadecimal',
                    simplify: true,
                    numbersToExpressions: true,
                    stringArray: true,
                    stringArrayEncoding: ['base64'],
                    stringArrayThreshold: 0.75,
                    splitStrings: true,
                    splitStringsChunkLength: 8,
                    transformObjectKeys: true,
                    unicodeEscapeSequence: false,
                    // Left off deliberately for performance:
                    controlFlowFlattening: false,
                    deadCodeInjection: false,
                    debugProtection: false,
                    disableConsoleOutput: false,
                },
            }),
    ].filter(Boolean),
    };
});
