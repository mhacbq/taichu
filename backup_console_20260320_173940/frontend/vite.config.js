import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  // 基础路径，如果部署到子目录需要修改，如 '/taichu/'
  base: process.env.VITE_BASE_PATH || '/',
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '/api'),
      },
    },
  },
  build: {
    outDir: 'dist',
    // 静态资源处理
    assetsDir: 'assets',
    // 生成sourcemap用于调试
    sourcemap: process.env.NODE_ENV !== 'production',
    // chunk 体积警告阈值
    chunkSizeWarningLimit: 1000,
    // 代码分割
    rollupOptions: {
      output: {
        manualChunks(id) {
          // ECharts 体积最大，单独拆分
          if (id.includes('node_modules/echarts') || id.includes('node_modules/zrender')) {
            return 'echarts'
          }

          if (id.includes('node_modules/element-plus')) {
            return 'element-plus'
          }

          if (id.includes('node_modules/vue-router') || id.includes('node_modules/vue')) {
            return 'vue-vendor'
          }

          // pinia、@vueuse 等 vue 生态轻量库
          if (id.includes('node_modules/pinia') || id.includes('node_modules/@vueuse/')) {
            return 'vue-ecosystem'
          }

          return undefined
        },
      },
    },

  },
})
