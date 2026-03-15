<template>
  <div
    class="editable-image"
    :class="{ 'is-hovered': isHovered && canEdit }"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <div class="image-wrapper" @click="handleClick">
      <el-image
        :src="modelValue"
        :fit="fit"
        :preview-src-list="preview ? [modelValue] : []"
        :style="{ width: width, height: height }"
      >
        <template #error>
          <div class="image-placeholder">
            <el-icon :size="48"><Picture /></el-icon>
            <span>暂无图片</span>
          </div>
        </template>
      </el-image>
      
      <!-- 编辑遮罩 -->
      <transition name="fade">
        <div v-if="isHovered && canEdit" class="image-overlay">
          <div class="overlay-content">
            <el-icon :size="32"><Edit /></el-icon>
            <span>点击更换图片</span>
          </div>
        </div>
      </transition>
    </div>
    
    <!-- 图片上传弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      title="更换图片"
      width="600px"
      destroy-on-close
    >
      <el-tabs v-model="activeTab">
        <!-- 本地上传 -->
        <el-tab-pane label="本地上传" name="upload">
          <el-upload
            class="image-uploader"
            drag
            :action="uploadUrl"
            :headers="uploadHeaders"
            :before-upload="beforeUpload"
            :on-success="handleUploadSuccess"
            :on-error="handleUploadError"
            accept="image/*"
          >
            <el-icon class="el-icon--upload"><Upload /></el-icon>
            <div class="el-upload__text">
              拖拽图片到此处或 <em>点击上传</em>
            </div>
            <template #tip>
              <div class="el-upload__tip">
                支持 JPG/PNG/GIF 格式，文件大小不超过 {{ maxSize }}MB
              </div>
            </template>
          </el-upload>
        </el-tab-pane>
        
        <!-- 图片库选择 -->
        <el-tab-pane label="图片库" name="gallery">
          <div class="image-gallery">
            <div
              v-for="img in galleryImages"
              :key="img.url"
              class="gallery-item"
              :class="{ 'is-selected': selectedImage === img.url }"
              @click="selectImage(img.url)"
            >
              <el-image :src="img.url" fit="cover" />
              <div v-if="selectedImage === img.url" class="selected-mark">
                <el-icon><Check /></el-icon>
              </div>
            </div>
          </div>
          <el-empty v-if="!galleryImages.length" description="暂无图片" />
        </el-tab-pane>
        
        <!-- 网络图片 -->
        <el-tab-pane label="网络图片" name="url">
          <el-form label-width="80px">
            <el-form-item label="图片地址">
              <el-input
                v-model="imageUrl"
                placeholder="请输入图片URL"
                clearable
              >
                <template #append>
                  <el-button @click="previewImage">预览</el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="imageUrl" label="图片预览">
              <el-image
                :src="imageUrl"
                style="max-width: 100%; max-height: 200px;"
                fit="contain"
              />
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>
      
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmChange" :loading="saving">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Edit, Picture, Upload, Check } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  canEdit: {
    type: Boolean,
    default: true
  },
  width: {
    type: String,
    default: '200px'
  },
  height: {
    type: String,
    default: '200px'
  },
  fit: {
    type: String,
    default: 'cover'
  },
  preview: {
    type: Boolean,
    default: true
  },
  maxSize: {
    type: Number,
    default: 5
  },
  uploadUrl: {
    type: String,
    default: '/api/upload/image'
  },
  saveApi: {
    type: Function,
    default: null
  },
  saveKey: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'change', 'save'])

const userStore = useUserStore()

const isHovered = ref(false)
const dialogVisible = ref(false)
const activeTab = ref('upload')
const imageUrl = ref('')
const selectedImage = ref('')
const saving = ref(false)

// 上传头部
const uploadHeaders = computed(() => ({
  Authorization: `Bearer ${userStore.token}`
}))

// 图片库数据
const galleryImages = ref([
  // 这里可以从API获取
  { url: 'https://placeholder.com/300x200', name: '示例1' },
  { url: 'https://placeholder.com/300x200', name: '示例2' }
])

// 处理点击
const handleClick = () => {
  if (!props.canEdit) return
  dialogVisible.value = true
  imageUrl.value = props.modelValue
  selectedImage.value = ''
}

// 上传前检查
const beforeUpload = (file) => {
  const isJPG = file.type === 'image/jpeg'
  const isPNG = file.type === 'image/png'
  const isGIF = file.type === 'image/gif'
  const isLtSize = file.size / 1024 / 1024 < props.maxSize
  
  if (!isJPG && !isPNG && !isGIF) {
    ElMessage.error('只支持 JPG/PNG/GIF 格式!')
    return false
  }
  if (!isLtSize) {
    ElMessage.error(`图片大小不能超过 ${props.maxSize}MB!`)
    return false
  }
  return true
}

// 上传成功
const handleUploadSuccess = (response) => {
  if (response.code === 200) {
    imageUrl.value = response.data.url
    ElMessage.success('上传成功')
  } else {
    ElMessage.error(response.message || '上传失败')
  }
}

// 上传失败
const handleUploadError = () => {
  ElMessage.error('上传失败，请重试')
}

// 选择图片库图片
const selectImage = (url) => {
  selectedImage.value = url
  imageUrl.value = url
}

// 预览图片
const previewImage = () => {
  if (!imageUrl.value) {
    ElMessage.warning('请输入图片地址')
  }
}

// 确认更换
const confirmChange = async () => {
  if (!imageUrl.value) {
    ElMessage.warning('请选择或上传图片')
    return
  }
  
  saving.value = true
  
  try {
    if (props.saveApi) {
      await props.saveApi({
        key: props.saveKey,
        value: imageUrl.value
      })
    }
    
    emit('update:modelValue', imageUrl.value)
    emit('change', imageUrl.value)
    emit('save', { key: props.saveKey, value: imageUrl.value })
    
    ElMessage.success('保存成功')
    dialogVisible.value = false
  } catch (error) {
    ElMessage.error('保存失败: ' + error.message)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.editable-image {
  position: relative;
  display: inline-block;
  
  &.is-hovered {
    cursor: pointer;
  }
}

.image-wrapper {
  position: relative;
  overflow: hidden;
  border-radius: 4px;
}

.image-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  background: #f5f7fa;
  color: #909399;
  
  span {
    margin-top: 8px;
    font-size: 14px;
  }
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  border-radius: 4px;
}

.overlay-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  
  span {
    font-size: 14px;
  }
}

.image-uploader {
  text-align: center;
  padding: 20px;
}

.image-gallery {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  max-height: 400px;
  overflow-y: auto;
  padding: 10px;
}

.gallery-item {
  position: relative;
  aspect-ratio: 1;
  border: 2px solid transparent;
  border-radius: 4px;
  overflow: hidden;
  cursor: pointer;
  
  &:hover {
    border-color: var(--el-color-primary-light-5);
  }
  
  &.is-selected {
    border-color: var(--el-color-primary);
  }
  
  .el-image {
    width: 100%;
    height: 100%;
  }
}

.selected-mark {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  background: var(--el-color-primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>