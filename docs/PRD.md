Sur un VPS je vais héberger plusieurs apps / saas / sites

Ce vps va héberger un proxy caddy qui va gérer les redirections / https
Le but est de mutualiser plusieurs projets sur un même serveur
Ex :
https://web.byjulien.com/kvm
https://web.byjulien.com/saas1
https://web.byjulien.com/saas2
https://web.byjulien.com/projet1
https://web.byjulien.com/blog-julien

## L'étape 1 

c'est de développer vite mon idée dans un environnement déjà prêt (sous domaine ok, https ok, php ok, composer ok ...)

Pour cela en général je vais utiliser une stack technique
Par exemple pour le projet blog-julien je vais utiliser une stack php / htmx / sqlite
Je n'ai pas envie à chaque projet de tout redéfinir je veux juste créer un sous répertoire dans src -> blog-julien
Puis dire donner le contexte de l'environnement à mon coding stack (le harnais) pour alller droit au but dès le début
Parfois il manque un élément dans ma stack ... pas de bidouille, on fait évoluer la stack en ajoutant la lib manquante. Cette modification sera valable pour tous les projets mais on reste cohérent.
Si vraiement les modifications changent la stack en profindeur alors on va peut être recréer une stack (nouvelle image docker)
Un des objectifs est d'éviter d'avoir 50 containers docker qui tournent !
La stack va évoluer naturellement : si on passe de htmx2 à 4 ... alors tous les projets basculent.

## L'étape 2 
si le projet va plus loin je vais peut être ne plus l'héberger derrire un nom de domaine générique dans un sous répertoire mais avec un sou domaine dédié
Peux être que https://web.byjulien.com/blog-julien va devenir https://blog.byjulien.com
Pour des saas on peut imaginer https://web.byjulien.com/saas1 devienne https://saas1.com ou https://app.saas1.com

Ce point est important car la bascule doit être simple à effectuer ... je ne dois pas toucher (trop) au code quand je change de domaine ou sous domaine. Le passage subdirectory à une app à la racine d'un sous domaine doit être pensé avant.

La logique est : développer vite en construisant sur une stack déjà prête puis de déplacer virtuellement sur un autre domaine ou sous domaine. Le but aussi est de garder un context IA le plus léger. Donc on va lui dire voici cdans quel environnement tu es (caddy, php, htmx ...) vas à l'essentiel

## Etape 3 
organisation de l'application / initialisation
il faut de la même façon créer l'orgnisation des répertoire (ex dans saas.domaine.fr)
Je dois pouvoir lire facilement l'application / l'organisation
Pas trop d'abstraction complexe
Une structure MVC
Une approche HATEOAS - hypermedia
Des routes expressives et logiques
L'application et son organisation doit être au service du modèle (claude, codex) pour simplifier sa compréhension
Il faut imaginer claude, codex, kimi comme des amis et l'organisation doit leur simplifier la vie et éviter de surcharger leur contexte. Car c'est eux qui vont générer le code.
Il faut avoir une approche test raisonnable et pragmatique (éviter les régressions)
les développement doivent être responsives basés sur la sémantique du html
La philosophie est d'utiliser les outils pour ce qu'ils savent faire de mieux. Le html a des balises avec une sémantique donc il faut les utliser au mieux.
Philosophie KISS
Si je ne peux pas comprendre simplement alors le problème est peut être mal posé

La philosophie d'Elon Musk 5 steps process est une bonne approche.

Le but final est de créer des services qui fonctionnent réellement en production et qui sont performants

## Etape 4

Capitaliser !!
Il faut capitaliser sur ce qui fonctionne ou pas
Il faut capitaliser au niveau de l'application
Il faut capitaliser au niveau de la stack technique
Il faut capitaliser au niveau global CLAUDE.md racine
Bref il faut capitaliser à tous les niveaux
Ma stack, ma méthode, ma production doit s'améliorer à chaque itération 
Et il faut trouver un moyen de remonter cette connaissance dans mes autres projets (github ??) pour créer une boucle vertueuse.
Cette boucle doit permettre de s'améliorer pas juste d'accumuler des connaissances / règles ... etc 

## Etape 5

Je dois pouvoir déployer facilement (créer / modifier)

Commençons avec ces contraintes et faisons évoluer le repo MA-STACK-IA
Pour la stack on va travailler uniquement sur php-htmx (pas les autres pour le moment)