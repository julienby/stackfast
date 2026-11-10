export type CheckStatus = 'ok' | 'failed' | 'stale'

declare module 'claude-code' {
  interface PluginState {
    'check-bar': { status: CheckStatus | null }
  }
}
