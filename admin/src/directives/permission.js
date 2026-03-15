/**
 * 权限指令
 * 控制元素的显示和操作权限
 */

import { useUserStore } from '@/stores/user'

// 权限检查函数
function checkPermission(value) {
  const userStore = useUserStore()
  
  if (!value || !Array.isArray(value)) {
    console.error('permission directive need roles array')
    return false
  }
  
  const roles = userStore.roles
  const hasPermission = roles.some(role => value.includes(role))
  
  return hasPermission
}

// 检查编辑权限
function checkEditPermission() {
  const userStore = useUserStore()
  return userStore.permissions?.includes('content:edit') || userStore.roles?.includes('admin')
}

// v-permission 指令
export const permission = {
  mounted(el, binding) {
    const { value } = binding
    
    if (!checkPermission(value)) {
      el.parentNode?.removeChild(el)
    }
  }
}

// v-edit 指令 - 控制编辑权限
export const edit = {
  mounted(el, binding) {
    const canEdit = checkEditPermission()
    
    if (!canEdit) {
      // 只读模式：移除点击事件
      el.style.pointerEvents = 'none'
      el.style.cursor = 'default'
      
      // 移除编辑提示
      const hints = el.querySelectorAll('.edit-hint')
      hints.forEach(hint => hint.remove())
    } else {
      // 编辑模式：添加编辑标识
      el.classList.add('can-edit')
    }
  }
}

// v-editable 指令 - 使元素可编辑
export const editable = {
  mounted(el, binding) {
    const { value } = binding
    const canEdit = checkEditPermission()
    
    if (!canEdit) return
    
    // 添加编辑功能
    let isEditing = false
    let originalContent = el.innerHTML
    
    // 双击进入编辑
    el.addEventListener('dblclick', (e) => {
      if (isEditing) return
      
      e.preventDefault()
      isEditing = true
      
      // 创建输入框
      const input = document.createElement(value?.type === 'textarea' ? 'textarea' : 'input')
      input.value = el.textContent
      input.className = 'inline-editor'
      input.style.cssText = `
        width: 100%;
        padding: 8px;
        border: 2px solid #409EFF;
        border-radius: 4px;
        font-size: inherit;
        font-family: inherit;
      `
      
      if (value?.type === 'textarea') {
        input.style.minHeight = '60px'
        input.rows = 3
      }
      
      el.innerHTML = ''
      el.appendChild(input)
      input.focus()
      
      // 保存编辑
      const saveEdit = async () => {
        const newValue = input.value
        
        if (newValue !== originalContent) {
          // 调用保存回调
          if (value?.onSave) {
            try {
              await value.onSave(newValue)
              originalContent = newValue
            } catch (error) {
              console.error('保存失败:', error)
            }
          }
        }
        
        el.innerHTML = originalContent
        isEditing = false
      }
      
      // 取消编辑
      const cancelEdit = () => {
        el.innerHTML = originalContent
        isEditing = false
      }
      
      input.addEventListener('blur', saveEdit)
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && value?.type !== 'textarea') {
          e.preventDefault()
          saveEdit()
        }
        if (e.key === 'Escape') {
          cancelEdit()
        }
      })
    })
    
    // 添加编辑提示
    el.addEventListener('mouseenter', () => {
      if (isEditing) return
      
      const hint = document.createElement('div')
      hint.className = 'edit-hint-overlay'
      hint.innerHTML = `
        <div style="
          position: absolute;
          top: -24px;
          left: 50%;
          transform: translateX(-50%);
          background: #409EFF;
          color: #fff;
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 12px;
          white-space: nowrap;
          z-index: 1000;
        ">
          双击编辑
        </div>
      `
      el.style.position = 'relative'
      el.appendChild(hint)
    })
    
    el.addEventListener('mouseleave', () => {
      const hint = el.querySelector('.edit-hint-overlay')
      if (hint) hint.remove()
    })
  }
}

// v-preview 指令 - 预览模式（禁用编辑）
export const preview = {
  mounted(el, binding) {
    const { value } = binding
    
    if (value) {
      // 进入预览模式
      el.classList.add('preview-mode')
      
      // 禁用所有编辑功能
      const editableElements = el.querySelectorAll('.can-edit, [contenteditable]')
      editableElements.forEach(elem => {
        elem.contentEditable = 'false'
        elem.classList.remove('can-edit')
        elem.style.pointerEvents = 'none'
      })
      
      // 隐藏编辑工具
      const tools = el.querySelectorAll('.edit-toolbar, .block-actions, .edit-hint')
      tools.forEach(tool => tool.style.display = 'none')
    }
  }
}

// v-drag 指令 - 拖拽权限控制
export const drag = {
  mounted(el, binding) {
    const canEdit = checkEditPermission()
    
    if (!canEdit) {
      el.draggable = false
      el.style.cursor = 'default'
    }
  }
}

// 注册所有指令
export function setupPermissionDirectives(app) {
  app.directive('permission', permission)
  app.directive('edit', edit)
  app.directive('editable', editable)
  app.directive('preview', preview)
  app.directive('drag', drag)
}

export default {
  install(app) {
    setupPermissionDirectives(app)
  }
}