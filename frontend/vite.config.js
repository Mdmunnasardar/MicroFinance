import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
  base: './',
  plugins: [react()],
  server: {
    port: 5173,
    strictPort: true,
    // The legacy PHP API shims at /api/search.php and /api/notifications.php
    // don't send CORS headers, so the browser would block the cross-origin
    // request from the React dev server. Proxy them so the browser sees a
    // same-origin request. Backend code is unchanged.
    //
    // We also proxy /MicroFinance/uploads so that avatar/profile images
    // served by PHP are returned with the correct Content-Type instead of
    // being intercepted by Vite's SPA fallback (which returns text/html).
    proxy: {
      '/MicroFinance/api/search.php': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
      },
      '/MicroFinance/api/notifications.php': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
      },
      '/MicroFinance/uploads': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        installments: resolve(__dirname, 'installments.html'),
      },
    },
  },
});
