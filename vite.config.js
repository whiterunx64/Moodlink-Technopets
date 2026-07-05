import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv } from 'vite';
import obfuscator from 'vite-plugin-javascript-obfuscator';

export default defineConfig(({ command, mode }) => {
    const isBuild = command === 'build';
    const isProd = mode === 'production';
    const env = loadEnv(mode, process.cwd(), 'VITE_');

    const sep = '[\\\\/]';
    const js = `resources${sep}js${sep}`;
    const sensitiveModules = [
        new RegExp(`${js}Layouts${sep}`),
        new RegExp(`${js}Pages${sep}(SummaryReports|UserAccounts|ReportedPosts|PostManagement)${sep}`),
        new RegExp(`${js}Pages${sep}Dashboard\\.vue$`),
        new RegExp(`${js}Components${sep}(Dashboard|SummaryReports|Students|Posts)${sep}`),
    ];

    const lightOptions = {
        compact: true,
        identifierNamesGenerator: 'hexadecimal',
        simplify: true,
        stringArray: true,
        stringArrayEncoding: ['base64'],
        stringArrayThreshold: 0.5,
        transformObjectKeys: false, // breaks some Vue reactivity/prop access; enable only after testing
        splitStrings: false,
        controlFlowFlattening: false,
        deadCodeInjection: false,
        selfDefending: false,
        debugProtection: false,
        disableConsoleOutput: true, // strip console/debugger here (no esbuild on Vite 8/Rolldown)
    };

    return {
        server: {
            host: '0.0.0.0',
            cors: {
                origin:
                    env.VITE_DEV_ORIGIN ||
                    /^https?:\/\/(localhost|127\.0\.0\.1|192\.168\.\d{1,3}\.\d{1,3})(:\d+)?$/,
            },
            hmr: env.VITE_HMR_HOST ? { host: env.VITE_HMR_HOST } : true,
        },

        build: {
            target: 'es2020',
            sourcemap: false,
            cssCodeSplit: true,
            modulePreload: { polyfill: false },
            reportCompressedSize: false,
            chunkSizeWarningLimit: 700,
            assetsInlineLimit: 4096,
            rollupOptions: {
                output: {
                    entryFileNames: 'assets/[hash].js',
                    chunkFileNames: 'assets/[hash].js',
                    assetFileNames: 'assets/[hash][extname]',
                    manualChunks(id) {
                        if (!id.includes('node_modules')) return;
                        if (
                            /[\\/]node_modules[\\/](chart\.js|apexcharts|echarts|chartjs-plugin-datalabels|vue-chartjs)[\\/]/.test(
                                id,
                            )
                        ) {
                            return 'charts';
                        }
                    },
                },
            },
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
            // Single pass, sensitive folders only, first-party code only.
            isBuild &&
                isProd &&
                obfuscator({
                    include: sensitiveModules,
                    exclude: [/node_modules/],
                    apply: 'build',
                    options: lightOptions,
                }),
        ].filter(Boolean),
    };
});