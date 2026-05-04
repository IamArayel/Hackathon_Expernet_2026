## 🔷 Contexte global

Projet réalisé dans le cadre d’un hackathon (48h) évalué selon plusieurs grilles de notation complémentaires :

DPI (Gestion de projet) : cadrage, pilotage, décisions, documentation
CDA / Dev (Développement) : qualité logicielle, architecture, robustesse
ASR (Infrastructure) : déploiement, sécurité, performance

Chaque grille est structurée en jalons temporels (H+4 à H+48) avec livrables obligatoires.

## 🔷 Objectif du projet

- Construire une solution répondant au concept :

- Utilisation de l’IA et de la gamification pour transformer la formation

- Le projet attendu doit être :
  - fonctionnel
  - démontrable
  - documenté
  - industrialisable (reprenable par une autre équipe)

## 🔷 Concept produit retenu

- Une plateforme EdTech intégrant :
  - Fonctionnalités principales
  - Authentification utilisateur
  - Parcours de formation
  - Système de progression (points, niveaux, badges)
  - Recommandation personnalisée (IA simplifiée ou simulée)

## 🔷 Contraintes clés (transversales aux grilles)

1. Preuve par éléments visibles

Le jury évalue uniquement ce qui est observable :

- dépôt Git actif
- commits réguliers et structurés
- documentation (README, schémas, CDC)
- application fonctionnelle
- logs, tests, déploiement

👉 Absence de preuve = absence de point

2. Progression obligatoire par jalons
Temps	Attendu
- H+4 / H+8	cadrage + setup
- H+16 / H+24	structuration + première version
- H+32 / H+40	robustesse + arbitrage
- H+48	produit final complet

👉 Le projet doit être fonctionnel à chaque étape, pas uniquement à la fin.

3. Cohérence inter-domaines
Gestion de projet → définit le besoin
Développement → implémente
Infrastructure → déploie

👉 Toute incohérence entre ces couches pénalise la note.

🔷 Exigences par domaine

📌 Gestion de projet (DPI)
- Attendus principaux
- Cahier des charges initial (CDC)
- Outil de suivi (Kanban, tâches assignées)
- Compte-rendus de réunions
- Gestion des risques et arbitrages
- Dossier final cohérent retraçant les décisions

📌 Développement (CDA)
- Attendus principaux
- Dépôt Git collaboratif avec commits propres
- Architecture claire (backend / frontend / DB)
- Code structuré (modularité)
- API documentée
- Gestion des erreurs
- Tests (même simples)
- Logs
- Application fonctionnelle en démo

📌 Infrastructure (ASR)
- Attendus principaux
- Environnement de développement et de test
- Processus de déploiement
- Environnement de production
- Sécurité (variables d’environnement, accès)
- Sauvegarde
- Supervision et logs
- Documentation technique

## 🔷 Architecture recommandée
- Backend
  - API REST (Node.js, Spring Boot, etc.)
  - Authentification (JWT)
- Frontend
  - Interface utilisateur (React ou équivalent)
  - Visualisation progression / gamification
- IA (simplifiée)
  - Système de recommandation basé sur :
    - progression
    - score
    - historique
- Gamification
  - XP (points)
  - niveaux
  - badges
- Infrastructure
  - Docker
  - Base de données (SQL)
  - Déploiement accessible (local ou cloud)

## 🔷 Livrables critiques

- `README.md` clair (installation + objectif)
- Cahier des charges (initial + final)
- Schémas :
  - architecture logicielle
  - infrastructure
- API documentée
- Outil de gestion de projet (preuves)
- Application déployée
- Documentation technique complète

## 🔷 Risques majeurs à éviter

- absence de documentation
- commits non structurés ou trop rares
- application non fonctionnelle
- absence de logs ou gestion d’erreurs
- incohérence entre besoin, code et infra
- absence de justification des choix techniques

## 🔷 Objectif final attendu

Produire un projet qui peut être perçu comme :

> Un produit SaaS éducatif fonctionnel, structuré, documenté, déployé et maintenable

et non comme un simple prototype technique.

## 🔷 Résumé en une phrase

Construire une plateforme de formation gamifiée avec IA, en respectant une progression incrémentale, avec preuves visibles à chaque étape, couvrant pilotage, développement et infrastructure, et livrer un produit complet, cohérent et réutilisable.