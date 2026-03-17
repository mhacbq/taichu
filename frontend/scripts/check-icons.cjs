const fs = require('fs')
const path = require('path')

const root = path.join(process.cwd(), 'src')

function walk(dir) {
  let out = []
  for (const name of fs.readdirSync(dir)) {
    const full = path.join(dir, name)
    const stat = fs.statSync(full)
    if (stat.isDirectory()) out = out.concat(walk(full))
    else if (/\.(vue|js|ts)$/.test(name)) out.push(full)
  }
  return out
}

const files = walk(root)
const imported = new Set()
const importRe = /import\s*\{([\s\S]*?)\}\s*from\s*['"]@element-plus\/icons-vue['"]/g

for (const file of files) {
  const text = fs.readFileSync(file, 'utf8')
  let m
  while ((m = importRe.exec(text)) !== null) {
    m[1]
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
      .forEach((s) => {
        const base = s.split(/\s+as\s+/)[0].trim()
        if (base) imported.add(base)
      })
  }
}

const icons = require('@element-plus/icons-vue')
const exported = new Set(Object.keys(icons))
const missing = [...imported].filter((name) => !exported.has(name)).sort()

console.log(`Imported icons: ${imported.size}`)
console.log(`Missing exports: ${missing.length}`)
if (missing.length) console.log(missing.join('\n'))

