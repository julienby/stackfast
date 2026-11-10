import { test, expect } from 'claude-code/testing'

const ROOT = '/work/app'

// Moteur factice : racine fixe, bin/check présent ou non, et la sortie de la commande lancée.
function engine(on: any, hasCheck: boolean, output = { text: '', isError: false }) {
  on('session.start', () => ({ cwd: ROOT }))
  on('ui.render', ($: any, e: any) => { const { Text } = $.ui.resolve(e); return h(Text, null, 'moteur') })
  on('session.root', () => ({ value: ROOT }))
  on('fs.exists', () => ({ value: hasCheck }))
  on('tool.call', () => ({ result: 'x', ...output }))
}

async function line($: any): Promise<string | undefined> {
  const ui = await $.ui.mount({ plugin: 'check-bar', surface: 'terminal', component: 'AbovePrompt', props: {} })
  return (await ui.find({ tag: 'Text' }))?.text
}

const bash = ($: any, command: string) => $.tool.call({ tool: 'Bash', command })

test('sans bin/check, la ligne est cachée', async ($: any, on: any) => {
  engine(on, false)
  await $.session.start({ cwd: ROOT })
  expect(await line($)).toBe('moteur')
})

test('bin/check vert : la ligne est verte', async ($: any, on: any) => {
  engine(on, true, { text: 'CHECK OK', isError: false })
  await $.session.start({ cwd: ROOT })
  await bash($, 'bin/check')
  expect(await line($)).toContain('vert')
})

test('bin/check rouge : la ligne est rouge', async ($: any, on: any) => {
  engine(on, true, { text: 'CHECK FAILED', isError: true })
  await $.session.start({ cwd: ROOT })
  await bash($, './bin/check && echo fini')
  expect(await line($)).toContain('rouge')
})

test('une modification rend le résultat périmé', async ($: any, on: any) => {
  engine(on, true, { text: 'CHECK OK', isError: false })
  await $.session.start({ cwd: ROOT })
  await bash($, 'bin/check')
  await $.tool.call({ tool: 'Edit', file_path: `${ROOT}/a.php`, old_string: 'a', new_string: 'b' })
  expect(await line($)).toContain('pas lancé')
})

test('une autre commande ne change pas l’état', async ($: any, on: any) => {
  engine(on, true, { text: 'CHECK OK', isError: false })
  await $.session.start({ cwd: ROOT })
  await bash($, 'ls bin/')
  expect(await line($)).toContain('pas lancé')
})
