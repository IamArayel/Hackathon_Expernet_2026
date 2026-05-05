DC  = docker compose
APP = $(DC) exec app

.DEFAULT_GOAL := help

.PHONY: help install start stop restart build reset logs sh db-sh \
        composer-install migrate fixtures tailwind tailwind-watch cc

help:
	@grep -E '^[a-zA-Z_-]+:.*?##' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

## ── Docker ────────────────────────────────────────────────────────────────────

start: ## Démarrer les conteneurs
	$(DC) up -d

stop: ## Arrêter les conteneurs
	$(DC) down

restart: ## Redémarrer les conteneurs
	$(DC) restart

build: ## (Re)construire les images Docker
	$(DC) up --build -d

reset: ## Supprimer les volumes et reconstruire (⚠ efface la base)
	$(DC) down -v --remove-orphans
	make install

logs: ## Afficher les logs en temps réel
	$(DC) logs -f

## ── Application ───────────────────────────────────────────────────────────────

install: build composer-install migrate tailwind ## Installation complète (premier lancement)

composer-install: ## Installer les dépendances Composer
	$(APP) composer install

migrate: ## Appliquer les migrations Doctrine
	$(APP) php bin/console doctrine:migrations:migrate --no-interaction

fixtures: ## Charger les données de développement (⚠ vide la base)
	$(APP) php bin/console doctrine:fixtures:load --no-interaction

tailwind: ## Compiler les assets Tailwind
	$(APP) php bin/console tailwind:build

tailwind-watch: ## Compiler les assets Tailwind en mode watch
	$(APP) php bin/console tailwind:build --watch

cc: ## Vider le cache Symfony
	$(APP) php bin/console cache:clear

## ── Accès shells ──────────────────────────────────────────────────────────────

sh: ## Ouvrir un shell dans le conteneur app
	$(DC) exec app sh

db-sh: ## Ouvrir un shell MariaDB
	$(DC) exec db mariadb -u $${MARIADB_USER} -p$${MARIADB_PASSWORD} $${MARIADB_DATABASE}
