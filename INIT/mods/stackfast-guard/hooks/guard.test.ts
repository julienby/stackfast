import { test, expect } from 'claude-code/testing'

const ROOT = '/work/app'

// Moteur factice : racine fixe, et la liste des dossiers qui existent.
function engine(on: any, dirs: string[]) {
  on('session.root', () => ({ value: ROOT }))
  on('fs.exists', (_$: any, e: any) => ({ value: dirs.includes(e.path.slice(ROOT.length + 1)) }))
  on('tool.call', () => ({ result: 'ok' }))
}

const edit = ($: any, file: string) =>
  $.tool.call({ tool: 'Edit', file_path: `${ROOT}/${file}`, old_string: 'a', new_string: 'b' })
const bash = ($: any, command: string) => $.tool.call({ tool: 'Bash', command })

test('Edit sous bin/ est refusé sur une instance', async ($: any, on: any) => {
  engine(on, ['stacks'])
  expect((await edit($, 'bin/check')).deny).toContain('vient du hub')
})

test('Edit sous stacks/ et .claude/ est refusé sur une instance', async ($: any, on: any) => {
  engine(on, ['stacks'])
  expect((await edit($, 'stacks/php-htmx/check')).deny).toBeDefined()
  expect((await edit($, '.claude/settings.json')).deny).toBeDefined()
})

test('settings.local.json et apps/ restent autorisés', async ($: any, on: any) => {
  engine(on, ['stacks'])
  expect((await edit($, '.claude/settings.local.json')).deny).toBeUndefined()
  expect((await edit($, 'apps/x/index.php')).deny).toBeUndefined()
})

test('le hub (avec INIT/) peut modifier bin/', async ($: any, on: any) => {
  engine(on, ['stacks', 'INIT'])
  expect((await edit($, 'bin/check')).deny).toBeUndefined()
})

test('git add .env est refusé, git add .env.example passe', async ($: any, on: any) => {
  engine(on, [])
  expect((await bash($, 'git add .env')).deny).toContain('ne commite jamais')
  expect((await bash($, 'git add -A data/')).deny).toBeDefined()
  expect((await bash($, 'git add .env.example')).deny).toBeUndefined()
  expect((await bash($, 'git status')).deny).toBeUndefined()
})
