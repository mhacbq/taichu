/**
 * 可视化编辑组件库
 * 提供所见即所得的编辑能力
 */

import EditableText from './EditableText.vue'
import EditableImage from './EditableImage.vue'
import EditableSelect from './EditableSelect.vue'
import EditableSwitch from './EditableSwitch.vue'
import EditableColor from './EditableColor.vue'

export {
  EditableText,
  EditableImage,
  EditableSelect,
  EditableSwitch,
  EditableColor
}

// 默认导出所有组件
export default {
  EditableText,
  EditableImage,
  EditableSelect,
  EditableSwitch,
  EditableColor
}

// 插件安装方法
export const install = (app) => {
  app.component('EditableText', EditableText)
  app.component('EditableImage', EditableImage)
  app.component('EditableSelect', EditableSelect)
  app.component('EditableSwitch', EditableSwitch)
  app.component('EditableColor', EditableColor)
}