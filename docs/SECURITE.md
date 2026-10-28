# Sécurité : une faille PHP est annoncée, que faire ?

Exemple réel : **PHP2Shell** (CVE-2026-109618 et CVE-2026-109619, 21 octobre 2026). Deux exécutions de code à distance sans authentification, sur toute installation PHP qui sert le web. Exploitées avant même l'annonce. Corrigées en 8.5.12, 8.4.27, 8.3.36, 8.2.35. Seule la mise à jour protège vraiment.

## La procédure : deux commandes par VPS

```sh
stackfast update     # reconstruit chaque stack avec le dernier correctif, teste toutes les apps, bascule
stackfast audit      # vérifie ce qui est réellement servi
```

`audit` doit finir par « rien à faire ». C'est tout.

## Ce que fait `update`, et pourquoi c'est sûr

1. **Construire** une image candidate `:next` avec `docker build --pull` : l'image de base (`dunglas/frankenphp:1-php8.5…`) est retéléchargée, donc avec le dernier PHP 8.5.x publié. La branche (8.5) est fixée dans `stacks/php-htmx/stack.conf`, le correctif ne l'est pas.
2. **Tester** toutes les apps de la stack avec `:next` (lint + tests, conteneurs jetables). Un test rouge → arrêt, rien n'a changé, les apps tournent toujours.
3. **Basculer** : `:current` devient `:previous`, `:next` devient `:current`.
4. **Recréer** chaque conteneur. Indispensable : un processus PHP déjà lancé garde l'ancien code en mémoire.
5. **Mettre à jour** l'image du proxy Caddy, puis lancer `audit`.

Un problème après la bascule : `stackfast rollback php-htmx` revient à `:previous`, le temps de corriger. Attention, `:previous` contient la faille.

## Ce que vérifie `audit`

- la version PHP **dans l'image servie** (pas un `php -v` de l'hôte), comparée au dernier correctif de la branche publié sur php.net ;
- les réglages de durcissement actifs (`session.upload_progress.enabled`, `fatal_error_backtraces`, `display_errors`) ;
- chaque app : tourne-t-elle sur l'image courante ? Un conteneur non recréé sert encore l'ancien PHP ;
- l'état du proxy.

Exit 1 si une action est nécessaire, avec la commande à lancer. En cron :

```sh
0 7 * * * cd /var/www/mon-instance && stackfast audit > /tmp/stackfast-audit.log 2>&1 || mail -s "stackfast audit" toi@domaine.fr < /tmp/stackfast-audit.log
```

## Si l'image amont n'a pas encore le correctif

php.net publie parfois quelques heures avant les images Docker. `audit` le dit (« OBSOLÈTE, 8.5.12 est publié »). En attendant :

- les réglages de durcissement de la stack sont déjà actifs (`php.ini`) : `session.upload_progress.enabled = 0` et `fatal_error_backtraces = 0` coupent les chemins connus de CVE-2026-109619 ; les limites de taille de requête (PHP et proxy à 32 Mo) et le plafond mémoire par conteneur réduisent ceux de CVE-2026-109618 ;
- relance `stackfast update` plus tard.

Les sandboxes d'agents (`stackfast agent`) embarquent le même PHP et servent une preview : `update` reconstruit aussi leur image, et `audit` liste celles qui tournent encore sur l'ancienne (`stackfast agent start <slug> <nom>` les recrée).

## Pourquoi cette architecture limite les dégâts

- **Un conteneur par app** : une app compromise ne lit ni le code, ni les données, ni les secrets des autres (chaque app a son `.env`, son réseau).
- **Conteneur durci** (`stacks/php-htmx/app.compose.yml`) : système de fichiers en lecture seule (seuls l'app et `/tmp` s'écrivent), uid non root, aucune capability, `no-new-privileges`, plafonds mémoire / CPU / processus.
- **Pas de shell depuis PHP** : `exec`, `system`, `proc_open`… désactivés (`php.ini`). Un webshell injecté ne lance pas de commande.
- **Un seul point d'entrée** : seul le proxy publie des ports ; les apps ne sont joignables que par lui.
- **Une seule source de vérité** : branche PHP dans `stack.conf`, réglages dans `php.ini`, sécurité du conteneur dans `app.compose.yml`. Les changer dans le hub, puis `stackfast pull && stackfast sync && stackfast deploy` sur chaque VPS.

## Changer de branche PHP (8.5 → 8.6)

Dans le hub : `BASE_IMAGE` de `INIT/stacks/php-htmx/stack.conf`, une ligne dans `DECISIONS.md`, `stackfast push`. Sur chaque VPS : `stackfast pull && stackfast sync && stackfast update php-htmx`. Les tests de toutes les apps tournent avant la bascule.
