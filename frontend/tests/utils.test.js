/**
 * 太初命理 - 前端核心工具函数测试
 * 使用 Node.js 原生运行，无需额外测试框架依赖
 * 运行方式：node frontend/tests/utils.test.js
 */

// ============================================================
// 简易测试运行器（无依赖）
// ============================================================
let passed = 0
let failed = 0
const results = []

function test(name, fn) {
  try {
    fn()
    passed++
    results.push({ status: '✅', name })
  } catch (err) {
    failed++
    results.push({ status: '❌', name, error: err.message })
  }
}

function expect(actual) {
  return {
    toBe(expected) {
      if (actual !== expected) {
        throw new Error(`期望 ${JSON.stringify(expected)}，实际得到 ${JSON.stringify(actual)}`)
      }
    },
    toEqual(expected) {
      const a = JSON.stringify(actual)
      const b = JSON.stringify(expected)
      if (a !== b) {
        throw new Error(`期望 ${b}，实际得到 ${a}`)
      }
    },
    toBeTruthy() {
      if (!actual) throw new Error(`期望真值，实际得到 ${JSON.stringify(actual)}`)
    },
    toBeFalsy() {
      if (actual) throw new Error(`期望假值，实际得到 ${JSON.stringify(actual)}`)
    },
    toBeGreaterThan(n) {
      if (actual <= n) throw new Error(`期望 > ${n}，实际得到 ${actual}`)
    },
    toContain(str) {
      if (!String(actual).includes(str)) throw new Error(`期望包含 "${str}"，实际得到 "${actual}"`)
    },
  }
}

// ============================================================
// 内联 validators 逻辑（从 validators.js 复制，避免 ESM 导入问题）
// ============================================================
const validatePhone = (phone) => /^1[3-9]\d{9}$/.test(phone)
const validateCode = (code) => /^\d{4,6}$/.test(code)
const validateEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
const validatePassword = (password) => /^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z\d!@#$%^&*]{6,20}$/.test(password)
const validateNickname = (nickname) => /^[\u4e00-\u9fa5a-zA-Z0-9_\-]{2,20}$/.test(nickname)
const validateBirthDate = (date) => {
  if (!date) return false
  const birthDate = new Date(date)
  const now = new Date()
  const minDate = new Date('1900-01-01')
  return birthDate >= minDate && birthDate <= now
}

// ============================================================
// 内联 format 逻辑
// ============================================================
const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

// ============================================================
// 测试：validatePhone
// ============================================================
test('validatePhone: 有效的13x号码', () => {
  expect(validatePhone('13812345678')).toBeTruthy()
})
test('validatePhone: 有效的19x号码', () => {
  expect(validatePhone('19912345678')).toBeTruthy()
})
test('validatePhone: 有效的18x号码', () => {
  expect(validatePhone('18800000000')).toBeTruthy()
})
test('validatePhone: 10位数字（不足11位）应失败', () => {
  expect(validatePhone('1381234567')).toBeFalsy()
})
test('validatePhone: 12位数字应失败', () => {
  expect(validatePhone('138123456789')).toBeFalsy()
})
test('validatePhone: 非法开头10x应失败', () => {
  expect(validatePhone('10012345678')).toBeFalsy()
})
test('validatePhone: 含字母应失败', () => {
  expect(validatePhone('1381234567a')).toBeFalsy()
})
test('validatePhone: 空字符串应失败', () => {
  expect(validatePhone('')).toBeFalsy()
})

// ============================================================
// 测试：validateCode
// ============================================================
test('validateCode: 4位数字应通过', () => {
  expect(validateCode('1234')).toBeTruthy()
})
test('validateCode: 6位数字应通过', () => {
  expect(validateCode('123456')).toBeTruthy()
})
test('validateCode: 3位数字应失败（太短）', () => {
  expect(validateCode('123')).toBeFalsy()
})
test('validateCode: 7位数字应失败（太长）', () => {
  expect(validateCode('1234567')).toBeFalsy()
})
test('validateCode: 含字母应失败', () => {
  expect(validateCode('1234a')).toBeFalsy()
})

// ============================================================
// 测试：validateEmail
// ============================================================
test('validateEmail: 标准邮箱应通过', () => {
  expect(validateEmail('user@example.com')).toBeTruthy()
})
test('validateEmail: 带子域名邮箱应通过', () => {
  expect(validateEmail('user@mail.example.cn')).toBeTruthy()
})
test('validateEmail: 缺少@符号应失败', () => {
  expect(validateEmail('userexample.com')).toBeFalsy()
})
test('validateEmail: 缺少域名应失败', () => {
  expect(validateEmail('user@')).toBeFalsy()
})
test('validateEmail: 空字符串应失败', () => {
  expect(validateEmail('')).toBeFalsy()
})

// ============================================================
// 测试：validatePassword
// ============================================================
test('validatePassword: 字母+数字6位应通过', () => {
  expect(validatePassword('abc123')).toBeTruthy()
})
test('validatePassword: 字母+数字+特殊符号应通过', () => {
  expect(validatePassword('Abc123!@')).toBeTruthy()
})
test('validatePassword: 纯数字应失败（无字母）', () => {
  expect(validatePassword('123456')).toBeFalsy()
})
test('validatePassword: 纯字母应失败（无数字）', () => {
  expect(validatePassword('abcdefg')).toBeFalsy()
})
test('validatePassword: 不足6位应失败', () => {
  expect(validatePassword('a1')).toBeFalsy()
})
test('validatePassword: 超过20位应失败', () => {
  expect(validatePassword('abcdefghij1234567890a')).toBeFalsy()
})

// ============================================================
// 测试：validateNickname
// ============================================================
test('validateNickname: 中文昵称应通过', () => {
  expect(validateNickname('太初用户')).toBeTruthy()
})
test('validateNickname: 字母昵称应通过', () => {
  expect(validateNickname('taichu')).toBeTruthy()
})
test('validateNickname: 带下划线应通过', () => {
  expect(validateNickname('user_001')).toBeTruthy()
})
test('validateNickname: 1个字符应失败（太短）', () => {
  expect(validateNickname('a')).toBeFalsy()
})
test('validateNickname: 含空格应失败', () => {
  expect(validateNickname('user name')).toBeFalsy()
})
test('validateNickname: 含表情应失败', () => {
  expect(validateNickname('user😊')).toBeFalsy()
})

// ============================================================
// 测试：validateBirthDate
// ============================================================
test('validateBirthDate: 1990年出生日期应通过', () => {
  expect(validateBirthDate('1990-05-20')).toBeTruthy()
})
test('validateBirthDate: 1900年应通过（边界）', () => {
  expect(validateBirthDate('1900-01-01')).toBeTruthy()
})
test('validateBirthDate: 1899年应失败（超出边界）', () => {
  expect(validateBirthDate('1899-12-31')).toBeFalsy()
})
test('validateBirthDate: 未来日期应失败', () => {
  expect(validateBirthDate('2099-01-01')).toBeFalsy()
})
test('validateBirthDate: 空字符串应失败', () => {
  expect(validateBirthDate('')).toBeFalsy()
})
test('validateBirthDate: null应失败', () => {
  expect(validateBirthDate(null)).toBeFalsy()
})

// ============================================================
// 测试：formatDate
// ============================================================
test('formatDate: 标准日期格式化', () => {
  expect(formatDate('1990-05-20')).toBe('1990-05-20')
})
test('formatDate: 补零（月份单位数）', () => {
  expect(formatDate('2000-1-5')).toBe('2000-01-05')
})
test('formatDate: 空字符串返回空', () => {
  expect(formatDate('')).toBe('')
})
test('formatDate: null返回空', () => {
  expect(formatDate(null)).toBe('')
})
test('formatDate: 无效日期返回空', () => {
  expect(formatDate('not-a-date')).toBe('')
})

// ============================================================
// 边界/安全测试
// ============================================================
test('安全：XSS字符串不应通过手机验证', () => {
  expect(validatePhone('<script>alert(1)</script>')).toBeFalsy()
})
test('安全：SQL注入不应通过邮箱验证', () => {
  expect(validateEmail("' OR 1=1 --")).toBeFalsy()
})
test('安全：超长手机号应失败', () => {
  expect(validatePhone('1'.repeat(100))).toBeFalsy()
})

// ============================================================
// 输出测试结果
// ============================================================
console.log('\n' + '='.repeat(60))
console.log('  太初命理 前端工具函数测试报告')
console.log('='.repeat(60))

results.forEach(({ status, name, error }) => {
  console.log(`${status} ${name}`)
  if (error) console.log(`   └─ ${error}`)
})

console.log('='.repeat(60))
console.log(`总计: ${passed + failed} 个测试  通过: ${passed}  失败: ${failed}`)
console.log('='.repeat(60) + '\n')

if (failed > 0) {
  process.exit(1)
}
