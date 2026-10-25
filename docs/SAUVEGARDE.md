# Sauvegarde et restauration

Le code des apps est dans git. Le reste n'y est **pas** : `data/` et `.env` de chaque app, la config de l'instance, les certificats du proxy. `stackfast backup` les met dans une archive ; `stackfast restore` les remet, sur ce serveur ou sur un neuf.

## Sauvegarder

```sh
stackfast backup                   # toutes les apps → backups/<instance>-<date>.tar.gz (chmod 600)
stackfast backup demo              # une app
stackfast backup --dest /mnt/nas   # ailleurs (BACKUP_DIR dans .env pour le fixer)
stackfast backup --keep 14         # garde les 14 dernières (7 par défaut, BACKUP_KEEP)
stackfast backup --no-certs        # sans les certificats du proxy
```

| Dans l'archive | |
|---|---|
| `apps/<stack>/<slug>/data/` | les données (sauf `.sessions`, jetables) ; SQLite copiée de façon cohérente (`.backup`) si l'app tourne |
| `apps/<stack>/<slug>/.env` et `.env.agent` | les secrets de l'app |
| `instance/` | `.env`, `proxy/Caddyfile`, `proxy/domains`, `proxy/preview-auth` |
| `certs.tar` | le volume de données de Caddy : certificats et autorité locale. Sans lui, un serveur neuf redemande tout (limites de Let's Encrypt) |

**Pas dans l'archive** : le code (git commit + push : `backup` prévient s'il reste du code modifié dans `apps/`), les volumes des conteneurs que tu ajoutes à une app (base de données, worker : à sauvegarder avec leurs propres outils), les `.agents/`.

### Chiffrer

L'archive contient tes secrets en clair. Avant de la copier hors du serveur, mets dans le `.env` de l'instance :

```
BACKUP_PASSPHRASE=une longue phrase, gardée ailleurs aussi
```

L'archive devient `….tar.gz.enc` (AES-256). Sans la phrase, elle est illisible : note-la dans ton gestionnaire de mots de passe, pas seulement sur le serveur.

### Automatiser et copier hors du serveur

Une sauvegarde sur le même disque ne protège pas d'une panne du serveur. Cron, tous les jours à 3 h, puis copie ailleurs :

```sh
0 3 * * * cd /var/www/mon-instance && stackfast backup --keep 14 >> backups/cron.log 2>&1 && rsync -a --delete backups/ moi@autre-machine:sauvegardes/mon-instance/
```

`stackfast audit` signale l'absence de sauvegarde ou une sauvegarde de plus de 2 jours (`BACKUP_MAX_AGE_DAYS`).

## Restaurer

```sh
stackfast restore backups/main-20261025-030000.tar.gz --list     # voir sans toucher
stackfast restore <archive> demo                                  # une app : ses data/ et son .env
stackfast restore <archive>                                       # tout : instance + toutes les apps
stackfast restore <archive> --certs                               # + certificats du proxy
```

- Ce qui est remplacé n'est jamais écrasé : l'ancien état est mis dans `backups/pre-restore/<date>/`.
- Une app est arrêtée, restaurée puis relancée ; son code doit déjà être là (git).
- Avec des slugs, seules ces apps sont touchées ; `--instance` ajoute la config d'instance. Sans slug, tout est restauré.
- Archive chiffrée : `BACKUP_PASSPHRASE=… stackfast restore …`.

### Serveur détruit : repartir de zéro

1. Installe Docker, puis stackfast : `curl -fsSL …/install.sh | sh -s -- ~/mon-instance`.
2. Récupère le code des apps (`git clone` / `git pull` de ton dépôt d'instance dans ce dossier) et copie l'archive.
3. `cd ~/mon-instance && stackfast restore <archive> --certs` : `.env`, Caddyfile et domaines, certificats, puis chaque app (construction de l'image comprise).
4. Vérifie `HOST_UID` / `HOST_GID` dans `.env` (`id -u`, `id -g`), repointe le DNS vers la nouvelle IP, puis `stackfast ls && stackfast audit`.

Testé : sinistre simulé (conteneurs, volumes, images, `.env` et données supprimés) puis `restore --certs` : tout revient en 16 s, le certificat est identique (pas de réémission).

## Avant de migrer un VPS de l'ancien modèle

Les apps de l'ancien modèle n'ont pas de `compose.yml` : `stackfast backup` ne les voit pas encore. Prends un instantané à la main **avant** toute migration :

```sh
cd /var/www/mon-instance
tar czf ~/avant-migration-$(date +%F).tar.gz .env apps/*/*/data apps/*/*/services proxy 2>/dev/null
# ton Caddy actuel : sa config et son volume de certificats
docker inspect <conteneur-caddy> --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}'
sudo tar czf ~/caddy-avant-migration-$(date +%F).tar.gz <dossier-Caddyfile> <dossier-/data-de-caddy>
```

Copie ces archives hors du serveur (ces commandes de l'ancien modèle n'ont pas été éprouvées sur un vrai serveur : vérifie le contenu avec `tar tzf`). Après la migration (`stackfast migrate`), `stackfast backup` couvre toutes les apps.

## Limites

- Une sauvegarde qu'on n'a jamais restaurée n'en est pas une : essaie `restore` sur une copie avant d'en avoir besoin.
- Une app qui écrit pendant la sauvegarde peut donner des JSON de deux instants légèrement différents (chaque fichier est cohérent, l'ensemble non). Pour une copie exacte : `stackfast down <slug>`, `backup <slug>`, `stackfast up <slug>`.
- Pas d'envoi automatique vers un stockage distant : à toi de choisir (rsync, rclone, scp) ; le chiffrement le rend sûr.
