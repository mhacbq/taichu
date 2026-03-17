const ROLE_ALIAS_MAP = {
  admin: ['admin', 'super_admin'],
  super_admin: ['super_admin', 'admin'],
  operator: ['operator'],
  normal_admin: ['normal_admin', 'operator'],
  customer_service: ['customer_service', 'operator']
}

export function normalizeAdminRoles(input) {
  const source = Array.isArray(input) ? input : (input ? [input] : [])
  const roles = new Set()

  source
    .map(role => String(role || '').trim())
    .filter(Boolean)
    .forEach(role => {
      const aliases = ROLE_ALIAS_MAP[role] || [role]
      aliases.forEach(alias => roles.add(alias))
    })

  return Array.from(roles)
}

export function hasRoutePermission(userRoles, requiredRoles = []) {
  if (!Array.isArray(requiredRoles) || requiredRoles.length === 0) {
    return true
  }

  const currentRoles = normalizeAdminRoles(userRoles)
  return requiredRoles.some(role => currentRoles.includes(role))
}
