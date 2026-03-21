import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

const proxyTarget = process.env.VITE_PROXY_TARGET || 'http://localhost:8080'

export default defineConfig({
  base: '/maodou/',
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 3001,
    proxy: {
      '/api': {
        target: proxyTarget,
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        manualChunks(id) {
          // ECharts + zrender 单独拆分（最大）
          if (id.includes('node_modules/echarts') || id.includes('node_modules/zrender')) {
            return 'echarts'
          }
          // wangEditor 富文本编辑器（体积大）
          if (id.includes('node_modules/@wangeditor') || id.includes('node_modules/wangeditor')) {
            return 'editor'
          }
          // Element Plus 图标（注册全量图标时拆开避免 icon 打入 index chunk）
          if (id.includes('node_modules/@element-plus/icons-vue')) {
            return 'element-plus-icons'
          }
          // Element Plus 主库
          if (id.includes('node_modules/element-plus')) {
            return 'element-plus'
          }
          // Vue 核心生态
          if (id.includes('node_modules/vue-router') || id.includes('node_modules/@vue/') || id.includes('node_modules/vue/')) {
            return 'vue-vendor'
          }
          // pinia、@vueuse 等
          if (id.includes('node_modules/pinia') || id.includes('node_modules/@vueuse/')) {
            return 'vue-ecosystem'
          }
        },
      },
    },
  },
})
