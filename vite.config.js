import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
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
    ],
    build: {
        assetsDir: 'build',
        assetsInlineLimit: 0,
        manifest: true,
        chunkSizeWarningLimit: 2000,
        minify: false,
        rollupOptions: {
          input: 'resources/js/app.js',
          output: {
            assetFileNames: (assetInfo) => {
              let extType = assetInfo.name.split('.').at(1);
              if (/png|jpe?g|svg|eot|ttf|woff|gif|tiff|bmp|ico/i.test(extType)) {
                extType = 'img';
                return `${extType}/[name]-[hash][extname]`;
              }
              // return `[name]-[hash][extname]`;
              return `[name]-[hash][extname]`;
            },
            chunkFileNames: 'js/[name]-[hash].js',
            entryFileNames: 'js/[name]-[hash].js',
          },
        },
      },
    ssr: {
        noExternal: ['@inertiajs/server'],
    },
    server: {
      host: '127.0.0.1',
      port: 5173
    }
});
