const crypto = require('crypto')

const sub = Number(process.argv[2] || 1)
const secret = process.argv[3] || 'local-dev-secret-please-change-in-production-abc123'
const now = Math.floor(Date.now() / 1000)

const base64url = (obj) => Buffer.from(JSON.stringify(obj)).toString('base64url')
const header = base64url({ alg: 'HS256', typ: 'JWT' })
const payload = base64url({
  iss: 'taichu-api',
  aud: 'taichu-app',
  iat: now,
  exp: now + 604800,
  sub,
})
const signature = crypto.createHmac('sha256', secret).update(`${header}.${payload}`).digest('base64url')

process.stdout.write(`${header}.${payload}.${signature}`)
