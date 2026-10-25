# Agents isolés : plusieurs agents sur une app, sans qu'ils voient le reste

`stackfast agent` lance des agents (Claude Code) sur **une** app. Chacun travaille dans sa sandbox : sa copie de l'app, son conteneur, son réseau, sa preview. Les autres apps, les secrets de l'instance et docker lui sont invisibles. Ce qu'il produit revient dans l'instance par une fusion que tu relis.

## Trois agents sur une app

```sh
stackfast agent new demo a1 a2 a3          # crée et démarre trois sandboxes (la première construit l'image : quelques minutes)
stackfast agent run demo a1 "ajoute un export CSV des items"       # en autonomie, en arrière-plan
stackfast agent run demo a2 "ajoute une date d'échéance aux items"
stackfast agent shell demo a3              # ou : session interactive avec Claude Code dans la sandbox

stackfast agent ls                         # état, fichiers changés, adresse de preview
stackfast agent logs demo a1               # ce que l'agent a fait / dit
stackfast agent diff demo a1               # ce qu'il a changé dans l'app
stackfast agent apply demo a1              # fusion dans apps/php-htmx/demo (puis : git diff, stackfast check demo, commit)
stackfast agent rm demo a1                 # supprime la sandbox (refuse s'il reste des changements non appliqués)
```

Chaque agent a sa **preview** : `https://<APPS_HOST>/_preview/<slug>/<nom>/` (utilisateur `preview`, mot de passe affiché une fois à la création du premier agent ; `stackfast agent passwd` pour le changer). Sans mot de passe, la route n'existe pas : une preview n'est jamais publique.

## Ce que voit un agent

| | |
|---|---|
| **Écriture** | `apps/<stack>/<slug>/` (sa copie), `data/` jetable, son `home` (sessions Claude Code) |
| **Lecture seule** | `stacks/<stack>/`, `AGENTS.md`, `.claude/` (contrat et hook de la sandbox) |
| **Absent** | les autres apps, les `.env`, le socket docker, les commandes `stackfast`, l'historique git de l'instance |
| **Réseau** | un réseau à lui (il ne résout ni les autres apps ni les autres agents) + Internet |
| **Droits** | uid de l'hôte, aucune capability, `no-new-privileges`, système de fichiers en lecture seule, 1 Go / 2 CPU / 512 processus |
| **Env** | un token `/api` + `/mcp` à lui, et ce que tu lui donnes (voir plus bas) |

Dedans, l'agent sert son app (même image que la prod : FrankenPHP, PHP identique), lance `stacks/<stack>/run-checks` (un hook le relance à chaque fin de tour) et dispose de `git` pour son propre historique. Ce dépôt git est à lui : l'hôte ne le lit jamais.

## Ce qui revient dans l'instance

`apply` compare la copie de l'agent à l'app **telle qu'elle était au départ** (pas à son état actuel) :

- fichier modifié par l'agent seul : copié ;
- fichier modifié des deux côtés : **fusion à 3 voies** (`git merge-file`), conflit signalé avec marqueurs `<<<<<<<` ;
- fichier ajouté ou supprimé : appliqué si l'instance ne l'a pas touché depuis, sinon signalé ;
- `data/`, `.env`, `vendor/`, `.git` : jamais ;
- liens symboliques : `apply` refuse tout (un lien pourrait pointer hors de l'app) ;
- tout ce qui est hors de `apps/<stack>/<slug>/` (stack, contrat, autres apps) : ne peut pas revenir.

Après `apply`, la base de l'agent avance : un second `apply` ne rejoue pas ses changements, et deux agents sur la même app se fusionnent l'un après l'autre. `stackfast check` détecte les marqueurs de conflit oubliés (même dans une vue HTML).

## Donner à un agent ce dont il a besoin

- **Compte Claude** : dans le `.env` de l'instance, `AGENT_CLAUDE_CODE_OAUTH_TOKEN=` (`claude setup-token`) ou `AGENT_ANTHROPIC_API_KEY=`. Passé à la sandbox, jamais écrit dans sa copie. Préfère une clé dédiée aux agents (révocable, plafonnée).
- **Secrets de l'app** : aucun par défaut. Un fichier `apps/<stack>/<slug>/.env.agent` (gitignoré) est lu à la création : mets-y des clés de test, jamais celles de la prod.
- **Données** : la sandbox part avec un `data/` vide ; `stackfast agent new … --with-data` copie celles de l'app (à éviter si elles sont réelles).
- **Méthode** : le `CLAUDE.global.md` du hub est monté comme contrat global de l'agent.

## Limites à connaître

- **L'agent a Internet.** Il lui faut au moins l'API Claude et les CDN. Ce qu'il voit (son code, sa clé d'API, ses variables) peut donc sortir si on le lui fait faire (injection de prompt dans une page ou un paquet lu). D'où : aucun secret de production dans une sandbox, une clé API dédiée.
- Il joint les mêmes URLs publiques que n'importe qui (dont le proxy de l'instance et les apps, avec leurs propres protections), et le réseau de l'hôte comme tout conteneur Docker. Pour un agent non fiable, limite ces sorties au pare-feu de l'hôte.
- `agent run` lance Claude Code avec `--dangerously-skip-permissions` : la sandbox est la frontière, pas les confirmations. `agent shell` garde les confirmations normales.
- Les agents ne redémarrent pas seuls après un reboot du serveur (sandbox volontairement jetable) : `stackfast agent start <slug> <nom>` ; leurs fichiers sont conservés, un `run` en cours est perdu.
- `stackfast update` reconstruit aussi l'image des sandboxes ; `stackfast audit` liste les agents qui tournent sur l'ancienne (`stackfast agent start` les recrée). Une sandbox sert une preview sur Internet : même exigence de correctifs que les apps.
- Plusieurs agents sur **le même fichier** se fusionnent avec conflits possibles : découper les tâches par zone (`models/`, `views/`) les évite.

## Dans le détail

```
.agents/<slug>/<nom>/        (gitignoré)
├── work/                    miroir réduit de l'instance : apps/<stack>/<slug>, stacks/<stack>, AGENTS.md, .claude/ — monté en /work
├── base/                    l'app au départ (base de la fusion, jamais montée dans la sandbox)
├── home/                    sessions Claude Code, logs des runs, server.log — monté en /home/agent
├── env                      variables de la sandbox (chmod 600)
└── meta
```

Pas de `git worktree` : un worktree partage le `.git` de l'instance, donc l'historique des autres apps. Ici l'hôte ne lance jamais git sur le dépôt que l'agent contrôle (un `.git/config` piégé s'exécuterait chez toi) : tout le retour passe par une comparaison de fichiers.
