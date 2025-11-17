import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    build: {
        outDir: 'src/resources',
        emptyOutDir: false,
        manifest: false,
        rollupOptions: {
            input: {
                'admin': resolve(__dirname, 'src/resources-src/js/admin.js'),
                'css/admin': resolve(__dirname, 'src/resources-src/scss/admin.scss'),
            },
            output: {
                entryFileNames: 'js/[name].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        // Extraire le nom du fichier SCSS source
                        const name = assetInfo.names?.[0] || assetInfo.name;
                        if (name.includes('admin')) {
                            return 'css/admin.css';
                        }
                        return 'css/[name][extname]';
                    }
                    return 'assets/[name][extname]';
                },
                chunkFileNames: 'js/[name]-[hash].js',
            },
        },
        cssCodeSplit: false,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: false,
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
            },
        },
    },
});
