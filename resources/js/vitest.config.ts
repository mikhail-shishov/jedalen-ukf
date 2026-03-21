import { fileURLToPath, URL } from 'node:url';
import { defineConfig, configDefaults } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./', import.meta.url)),
      '@assets': fileURLToPath(new URL('../assets', import.meta.url)),
    },
  },
  test: {
    environment: 'jsdom',
    root: fileURLToPath(new URL('./', import.meta.url)),
    exclude: [...configDefaults.exclude, 'e2e/**'],
    setupFiles: [fileURLToPath(new URL('./tests/setup.ts', import.meta.url))],
  },
});
