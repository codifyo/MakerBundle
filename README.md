# Codifyo MakerBundle

![License](https://img.shields.io/badge/License-MIT-blue.svg)

## Présentation

**Codifyo MakerBundle** est une librairie open source qui améliore et étend le célèbre [symfony/maker-bundle](https://github.com/symfony/maker-bundle). 

Historiquement, Symfony permettait de générer du code (contrôleurs, entités, services) directement dans des bundles spécifiques. Avec la standardisation des architectures "Bundle-less" dans `src/`, cette fonctionnalité a été retirée du `maker-bundle` officiel.

Ce bundle restaure cette fonctionnalité indispensable pour les applications modulaires. Il intercepte les commandes natives du MakerBundle (ex: `make:controller`, `make:entity`) pour vous permettre de choisir **le bundle de destination** dans lequel le code sera généré.

## Fonctionnalités

* 🚀 **Multi-Bundle Support** : Redirige la génération de fichiers vers n'importe quel bundle de votre dossier `src/`.
* 🪄 **Décoration Transparente** : Fonctionne avec toutes les commandes natives de `symfony/maker-bundle` sans changer vos habitudes.
* 📦 **Commande `make:bundle`** : Réintroduit une commande permettant d'initialiser rapidement un nouveau bundle from scratch (structure, classe d'extension, configuration).

## Compatibilité

* **PHP** : 8.0 et supérieur.
* **Symfony** : 5.4, 6.x et 7.x.
* **MakerBundle** : Supporte les versions `^1.0` (y compris les générateurs modernes utilisant `TemplateComponentGenerator` et `ClassData` introduits récemment).

## Installation

Installez le bundle via Composer :

```bash
composer require --dev codifyo/maker-bundle
```

Assurez-vous que le bundle est activé dans votre fichier `config/bundles.php` (uniquement pour l'environnement `dev`) :

```php
return [
    // ...
    Codifyo\MakerBundle\CodifyoMakerBundle::class => ['dev' => true],
];
```

## Utilisation

Le bundle fonctionne en tâche de fond. Vous n'avez pas de nouvelles commandes à apprendre pour générer vos classes ! Utilisez simplement vos commandes habituelles.

### Générer dans un bundle cible

```bash
php bin/console make:controller
```

Durant l'exécution, le bundle interceptera la commande et vous demandera :

```text
Choose the bundle where the command must be created (e.g. AcmeBundle):
> MonCustomBundle
```

Le contrôleur sera alors créé directement dans `src/MonCustomBundle/Controller/` avec le bon espace de nommage !

### Mode non-interactif

Vous pouvez spécifier le nom du bundle en tant que dernier argument si vous souhaitez automatiser la génération :

```bash
php bin/console make:controller NomDuController MonCustomBundle
```

### Créer un nouveau bundle

Utilisez la commande dédiée pour initialiser un nouveau bundle Symfony standard :

```bash
php bin/console make:bundle
```

## Configuration

Aucune configuration supplémentaire n'est requise. Le bundle analyse automatiquement les bundles enregistrés dans `config/bundles.php` qui se trouvent sous le namespace `App\`.

## Architecture

Sous le capot, Codifyo MakerBundle utilise l'injection de dépendances et le pattern Decorator :

* Un `CompilerPass` trouve toutes les commandes taguées avec `maker.command` et les remplace par un proxy (`DecoratingMaker`).
* Lors de l'exécution, ce proxy intercepte les requêtes interactives.
* Le bundle utilise la **Réflexion PHP** pour modifier temporairement le `rootNamespace` interne du générateur de Symfony MakerBundle afin d'y injecter le namespace du bundle cible, garantissant ainsi que l'autoloader PSR-4 place les fichiers dans le bon sous-dossier.

## Développement et Contribution

Les contributions (Pull Requests, signalement d'issues) sont les bienvenues ! 

1. Forkez le projet.
2. Créez une branche pour votre fonctionnalité.
3. Testez localement avec un projet Symfony.
4. Soumettez une PR.

## Tests

Actuellement, ce bundle s'appuie sur des tests d'intégration manuels en le liant à une application Symfony existante. 
*Les tests automatisés PHPUnit sont en cours de planification.*

## Licence

Ce projet est distribué sous la licence [MIT](LICENSE).
