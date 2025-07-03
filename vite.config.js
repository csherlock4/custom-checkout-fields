import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: path.resolve(__dirname, 'admin/dist'),
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'src/main.jsx'),
        'order-meta': path.resolve(__dirname, 'src/order-meta.jsx'),
      },
      output: {
        entryFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]',
        format: 'es', // Use ES modules format
      },
    },
  },
  define: {
    'process.env.NODE_ENV': '"production"'
  }
})