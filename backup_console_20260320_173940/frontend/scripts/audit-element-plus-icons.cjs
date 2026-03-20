const fs = require('fs')
const path = require('path')

const root = path.join(process.cwd(), 'src')
const validIcons = new Set(Object.keys(require('@element-plus/icons-vue')))
const targets = []

function walk(dir) {
  for (const name of fs.readdirSync(dir)) {
    const full = path.join(dir, name)
    const stat = fs.statSync(full)
    if (stat.isDirectory()) {
      walk(full)
    } else if (/\.(vue|js|ts)$/.test(name)) {
      targets.push(full)
    }
  }
}

function lineOf(text, idx) {
  return text.slice(0, idx).split(/\r?\n/).length
}

walk(root)

const issues = []
const importRe = /import\s*\{([^}]*)\}\s*from\s*['"]@element-plus\/icons-vue['"]/g

for (const filePath of targets) {
  const text = fs.readFileSync(filePath, 'utf8')
  let m
  while ((m = importRe.exec(text)) !== null) {
    const namesPart = m[1]
    const line = lineOf(text, m.index)
    const names = namesPart
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
      .map((s) => s.split(/\s+as\s+/)[0].trim())

    for (const name of names) {
      if (!validIcons.has(name)) {
        issues.push({
          file: path.relative(process.cwd(), filePath).replace(/\\/g, '/'),
          line,
          icon: name,
        })
      }
    }
  }
}

if (!issues.length) {
  console.log('OK: no invalid @element-plus/icons-vue imports found.')
  process.exit(0)
}

console.log(`Found ${issues.length} invalid icon import(s):`)
for (const item of issues) {
  console.log(`- ${item.file}:${item.line} -> ${item.icon}`)
}
process.exit(1)

