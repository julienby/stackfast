import type { Engine, Register } from 'claude-code'

// Contrat de l'instance (AGENTS.md) : stacks/, bin/ et .claude/ viennent du hub.
const SYNCED = /^(stacks|bin|\.claude)\//
const ALLOWED = '.claude/settings.local.json'
const NEVER_COMMIT = /(^|\s|\/)(\.env|data\/|vendor\/|PLAN\.md)(\s|$)/

// Renvoie la raison du refus, ou undefined si le chemin est libre.
// Une instance a stacks/ et pas INIT/. Le hub a INIT/.
async function refusal($: Engine, path: string) {
  const root = await $.session.root()
  if (!path.startsWith(`${root}/`)) return undefined

  const rel = path.slice(root.length + 1)
  if (rel === ALLOWED || !SYNCED.test(rel)) return undefined
  if (!(await $.fs.exists(`${root}/stacks`)) || (await $.fs.exists(`${root}/INIT`))) return undefined

  return `${$.plugin.name}: ${rel} vient du hub. Modifie-le dans le hub, puis lance stackfast sync.`
}

export const register: Register = on => {
  on('tool.call', { tool: 'Edit' }, async ($, e, next) => {
    const reason = await refusal($, e.file_path)
    return reason ? { deny: reason } : next(e)
  })

  on('tool.call', { tool: 'Write' }, async ($, e, next) => {
    const reason = await refusal($, e.file_path)
    return reason ? { deny: reason } : next(e)
  })

  on('tool.call', { tool: 'NotebookEdit' }, async ($, e, next) => {
    const reason = await refusal($, e.notebook_path)
    return reason ? { deny: reason } : next(e)
  })

  on('tool.call', { tool: 'Bash' }, ($, e, next) => {
    const isBadAdd = /\bgit\s+add\b/.test(e.command) && NEVER_COMMIT.test(e.command)
    return isBadAdd
      ? { deny: `${$.plugin.name}: ne commite jamais .env, data/, vendor/, PLAN.md.` }
      : next(e)
  })
}
