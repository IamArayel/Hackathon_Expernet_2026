# ACADEM'ÎLE — Hackathon Expernet 2026

Plateforme EdTech gamifiée qui utilise l'IA pour personnaliser les parcours de formation selon les besoins, les objectifs et le rythme de chaque apprenant.

> COMMENT L'IA ET LA GAMIFICATION VONT-ELLES RÉVOLUTIONNER LA FORMATION ?

## Membres de l'équipe

| Nom | Rôle |
| --- | ---- |
| Marielle AGATHE | |
| Anne LEBEAU | |
| Nassim ALI MAHOMED | |
| Jérôme CADERBY | |
| Matthias CLAIN | |
| Anthony DEGEILH | |
| Lucas DIJOUX | |
| Lucas JULIEN | |
| Théo KASPROWICZ | |
| Damien PAYET | |

## Stack technique

| Couche | Technologie |
| ------ | ----------- |
| Backend | Symfony (PHP 8.2) |
| Base de données | MySQL 8 |
| IA | Mistral AI (`mistral-small-latest`) |
| Gamification | XP, niveaux, badges |

## Prérequis

- PHP 8.2+
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download)
- MySQL 8+
- Une clé API Mistral AI (gratuite sur [console.mistral.ai](https://console.mistral.ai))

## Installation

```bash
# 1. Cloner le dépôt
git clone https://github.com/IAmArayel/Hackathon_Expernet_2026.git
cd Hackathon_Expernet_2026

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Renseigner DATABASE_URL et MISTRAL_API_KEY dans .env.local

# 4. Initialiser la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Lancer le serveur
symfony server:start
```

L'application est accessible sur https://localhost:8000.

## Architecture

### Flux de données

![alt text](ressources/Schema_Flux_Donnee.png)

[(Voir le schéma sur Canva)](https://canva.link/caeq8t7qaa2wor0)

### Architecture réseau

![Architecture réseau](ressources/architecture_reseau.png)

## Gestion du projet

### Conventions de branche

Format : **`<type>-<numéro-issue>(-<description>)`**

| Type | Usage |
| ---- | ----- |
| `feature` | Nouvelle fonctionnalité |
| `bugfix` | Correction non urgente |
| `hotfix` | Correction urgente en production |
| `security` | Correction de faille de sécurité |
| `test` | Ajout ou modification de tests |
| `refactor` | Restructuration interne |
| `chore` | Maintenance / outillage |

### Conventions de commit

Messages en français, suivant la spécification [Conventional Commits](https://www.conventionalcommits.org/fr/v1.0.0/#summary).

```text
feat: ajouter l'authentification JWT
fix: corriger le calcul des points XP
docs: mettre à jour le README
```

### Règles de contribution

- 1 carte GitHub Project = 1 branche = 1 Pull Request
- **1 review obligatoire** avant tout merge sur `main` pour valider les modifications apportées
- Carte GitHub Project complétée d'après le template fourni
