# AGENT.md

Ce fichier fournit le contexte, l'architecture et les bonnes pratiques du projet pour tout agent IA (Antigravity, Codex, Claude Code, Cursor, etc.) devant intervenir sur le dépôt.

## Présentation du projet

* **Objectif de la librairie** : Restaurer et améliorer le support des "bundles" pour le `symfony/maker-bundle`. Historiquement, Symfony permettait de générer du code dans des bundles spécifiques, mais ce comportement a été standardisé vers un dossier `src/` unique (namespace `App\`). Cette librairie réintroduit la possibilité de choisir un bundle de destination lors de la création de contrôleurs, entités, etc.
* **Cas d'usage principaux** :
  - Générer un nouveau contrôleur, service ou entité directement dans `App\MonCustomBundle` au lieu du répertoire racine de l'application.
  - Créer facilement de nouveaux bundles complets via la commande personnalisée `make:bundle`.
* **Public visé** : Développeurs Symfony travaillant sur des applications modulaires ou des architectures multi-bundles.

## Architecture

* **Structure des répertoires** :
  - `DependencyInjection/` : Contient le `MakerCompilerPass` et l'extension. Ce composant est responsable de la modification dynamique du conteneur de dépendances.
  - `Generator/` : Surcharge le générateur du MakerBundle de base.
  - `Maker/` : Contient `DecoratingMaker`, qui agit comme un proxy pour les makers existants, ainsi que des makers personnalisés (`MakeBundle`).
  - `Resources/` : Configuration (services) et templates de génération (skeleton).
  - `Utils/` : Utilitaires pour la manipulation de la configuration (routes, bundles).
* **Responsabilités et Flux principaux** :
  - Lors de la compilation du conteneur (`MakerCompilerPass`), toutes les commandes taguées `maker.command` sont décorées par `DecoratingMaker`.
  - Lorsque l'utilisateur exécute une commande (ex: `make:controller`), `DecoratingMaker::interact` intercepte l'appel pour demander à l'utilisateur de sélectionner un bundle de destination.
  - `DecoratingMaker::generate` est ensuite appelé. Il modifie l'espace de noms du `Generator` injecté pour qu'il pointe vers le bundle sélectionné.
  - `Codifyo\MakerBundle\Generator\Generator` utilise la **réflexion PHP** pour modifier les propriétés privées du générateur parent (ex: `rootNamespace` de `TemplateComponentGenerator` ou `namespacePrefix` de `BaseGenerator`) en y **ajoutant** le namespace du bundle.
  - MakerBundle génère ensuite le code qui est routé correctement dans le dossier du bundle cible grâce à l'autoloader (PSR-4).

## Stack technique

* **Version PHP** : 8.0+ (Déduit de l'utilisation des types stricts et des fonctions modernes comme `str_contains`).
* **Version Symfony** : Compatible Symfony 5, 6 et 7. MakerBundle >= 1.40 géré via `TemplateComponentGenerator`.
* **Dépendances majeures** : `symfony/maker-bundle` (^1.0@dev).
* **Outils de qualité** : Aucun outil de qualité (PHPStan, PHP-CS-Fixer) n'est actuellement configuré sur le projet.

## Conventions de développement

* **Style de code** : PSR-12 standard, typage strict encouragé (`string`, `array`, `void`).
* **Nommage** : CamelCase pour les variables et méthodes, PascalCase pour les classes.
* **Organisation des services** : Injection par constructeur, configuration en YAML (`Resources/config/services.yaml`).
* **Gestion des exceptions** : Lancer des `\InvalidArgumentException` ou `\RuntimeException` avec des messages clairs.
* **Architecture** : Le pattern *Decorator* est au cœur du bundle. Il faut privilégier la composition et la décoration (via le Compiler Pass) plutôt que la surcharge directe par héritage quand c'est possible.

## Bonnes pratiques spécifiques au projet

* **Pièges connus** : 
  - **La réflexion PHP** : Le bundle repose lourdement sur la réflexion PHP pour altérer le comportement interne de `symfony/maker-bundle` (`BaseGenerator`, `TemplateComponentGenerator`). Toute modification de version majeure de Symfony MakerBundle risque de casser ces accès (ex: renommage de variables privées).
  - **Filtre des Bundles** : Actuellement, `DecoratingMaker::filterBundles` ne filtre **que** les bundles commençant par `App\`. Cela signifie que les bundles vendor ne sont pas listés.
  - **Injection tardive** : Les générateurs internes du MakerBundle peuvent changer entre les différentes versions (ex: passage à `ClassData`). Il faut s'assurer que les namespaces sont altérés de manière additive (ex: `$current . '\\' . $namespacePrefix`) et non par écrasement total.

## Processus de développement

Lorsqu'un agent travaille sur une fonctionnalité :
1. Comprendre le besoin (lire les logs ou reproduire le cas).
2. Identifier les impacts (ex: versions de MakerBundle supportées).
3. Vérifier les risques de régression.
4. Implémenter en préservant la compatibilité ascendante.
5. Ajouter ou mettre à jour les tests (Créer un dossier `tests/` si absent).
6. Vérifier et mettre à jour la documentation (`README.md`).
7. Vérifier les impacts sur la sécurité et les performances.

## Checklist avant livraison

* [ ] Respect des conventions.
* [ ] Couverture de tests suffisante (⚠️ point faible actuel du projet).
* [ ] Pas de duplication inutile.
* [ ] Pas de régression connue avec les anciennes versions de `maker-bundle`.
* [ ] Documentation mise à jour.

## Commandes utiles

Aucun environnement de test ou d'analyse statique n'est formellement installé. Voici les commandes standard :

* **Installation** : `composer install`
* **Test manuel** : A utiliser en l'installant dans une application Symfony factice locale (via `path` repository dans composer).
* **Qualité** : À implémenter (ex: `vendor/bin/phpstan analyse src`).

## Recommandations pour les futurs agents

* **Dette Technique** : Ce projet manque cruellement de tests automatisés. Si une modification complexe est demandée, vous devez tester manuellement la génération via un projet Symfony factice (comme fait avec la commande `make:controller TestController MyBundle`).
* **Compatibilité** : Vérifiez toujours comment les variables privées de `symfony/maker-bundle` évoluent dans ses nouvelles versions. La robustesse de ce bundle dépend de la correspondance des noms de propriétés privées.
