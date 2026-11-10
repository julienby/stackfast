import { atom, read, update } from 'claude-code'
import type { Register } from 'claude-code'

import type { CheckStatus } from '../types'

// null = pas de bin/check dans ce projet : la ligne reste cachée.
const status = atom({ plugin: 'check-bar', key: 'status' } as const, null)

const LINES: Record<CheckStatus, { color: string; text: string }> = {
  ok: { color: 'green', text: '● bin/check : vert' },
  failed: { color: 'red', text: '● bin/check : rouge' },
  stale: { color: 'yellow', text: '○ bin/check : pas lancé depuis la dernière modification' },
}

export const register: Register = on => {
  on('session.start', async ($, e, next) => {
    const root = await $.session.root()
    if (await $.fs.exists(`${root}/bin/check`)) await update($, status, () => 'stale')

    return next(e)
  })

  // Une modification rend le dernier résultat périmé.
  for (const tool of ['Edit', 'Write', 'NotebookEdit'] as const) {
    on('tool.call', { tool }, async ($, e, next) => {
      if ((await read($, status)) !== null) await update($, status, () => 'stale')

      return next(e)
    })
  }

  on('tool.call', { tool: 'Bash' }, async ($, e, next) => {
    const ran = await next(e)
    if (!/(^|[\s;&|])(\.\/)?bin\/check\b/.test(e.command) || ran.deny !== undefined) return ran

    const isOk = ran.isError !== true && (ran.text ?? '').includes('CHECK OK')
    await update($, status, () => (isOk ? 'ok' : 'failed'))

    return ran
  })

  on('ui.render', { component: 'AbovePrompt' }, async ($, e, next) => {
    const current = await read($, status)
    if (e.props.hasSurvey || current === null) return next(e)

    const { Box, Text } = $.ui.resolve(e)
    const line = LINES[current]

    return (
      <Box>
        <Text color={line.color}>{line.text}</Text>
      </Box>
    )
  })
}
