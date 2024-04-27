## Commandes du projet Symfony en cours
## ------------------------------------
##
## Options disponibles :
##

include .env
ifneq (,$(wildcard ./.env.local))
    include .env.local
endif

ifndef APP_NAME
$(error APP_NAME is not set)
endif
APP_NAME := $(subst $\",,$(APP_NAME))

#ifndef GITHUB_REMOTE
#$(error GITHUB_REMOTE is not set)
#endif

PATH_FILE_CONF=/workspace/devops

ifndef DOCKER_LABEL
$(error DOCKER_LABEL is not set)
endif
DOCKER_REPO=sabinus52/$(DOCKER_LABEL)

VERSION := $(shell git describe --abbrev=0 2> /dev/null)
ifndef VERSION
	VERSION := dev
endif


.PHONY: help
help: Makefile ## Aide
	@sed -n 's/^##//p' $<
	@/bin/echo -e "$$(grep -hE '^\S+:.*##' $(MAKEFILE_LIST) | sed -e 's/:.*##\s*/:/' -e 's/^\(.\+\):\(.*\)/   \\x1b[36m\1\\x1b[m:\2/' | column -c2 -t -s :)"


.PHONY: start
start: ## Démarrage du service Docker MariaDB et Symfony
	@echo "===== Démarrage de l'application ${APP_NAME} ====="
	@echo ""
	@echo "----- Configuration du Docker -----"
	@docker-compose -f docker-compose-devp.yml config
	@./bin/console debug:dotenv
	@docker-compose -f docker-compose-devp.yml up -d --remove-orphans
	@echo "----- Démarrage du serveur applicatif -----"
	@symfony serve


.PHONY: deploy
deploy: ## Déploiement pour mise à jour de l'application
	@git pull
	@composer update
	@bin/console doctrine:migrations:migrate
	

.PHONY: check
check: ## Vérification de l'installation et des bons paramètres Symfony
	@symfony check:security
	@./bin/console debug:container --env-vars
	@./bin/console debug:dotenv
	@./bin/console doctrine:database:create


.PHONY: initialize
initialize: ## Initialise le projet
	echo "=== Initialisation du projet $(APP_NAME) ====="
	@echo "--- Configuration GIT ---------------------------------------------"
	@if [ $(git rev-parse --verify develop 2>/dev/null) ]; then \
		git remote add origin $(GITHUB_REMOTE); \
		git branch -M master; \
		git push -u origin master; \
		git stash; \
		git flow init; \
		git stash pop; \
		git push -u origin develop; \
		git config core.fileMode false; \
	fi
	@echo "--- Fichier de qualité de code ------------------------------------"
	cp ${PROJECTS_HOME}$(PATH_FILE_CONF)/ruleset.xml .
	cp ${PROJECTS_HOME}$(PATH_FILE_CONF)/phpstan.neon .
	cp ${PROJECTS_HOME}$(PATH_FILE_CONF)/.php-cs-fixer.dist.php .
	sed -i "s/%APP_NAME%/$(APP_NAME)/g" .php-cs-fixer.dist.php
	@echo "--- Fichier .gitignore --------------------------------------------"
	@if ! cat .gitignore | grep "###> My configuration ###" > /dev/null; then \
		echo "###> My configuration ###" >> .gitignore; \
		echo "/.env" >> .gitignore; \
		echo "/.vscode" >> .gitignore; \
		echo "/*.code-workspace" >> .gitignore; \
		echo "/*.conf" >> .gitignore; \
		echo "/DEVELOP*.md" >> .gitignore; \
		echo "###< My configuration ###" >> .gitignore; \
	fi
	@echo "--- Documentation -------------------------------------------------"
	touch README.md CHANGELOG.md DEVELOP-README.md


.PHONY: test
test: ## Tests unitaires avec PHPINIT
#phpunit --debug
	@if [ "${APP_ENV}" = "dev" ]; then \
		echo ok; \
	else \
		echo "nook"; \
	fi


.PHONY: phpmd
phpmd: ## Analyse de la qualité du code
	@phpmd src ansi ruleset.xml
	@phpmd tests ansi ruleset.xml


.PHONY: phpstan
phpstan: ## Analyse statique du code PHP
	@phpstan analyse src --level=7 --configuration=phpstan.neon


.PHONY: codestyle
codestyle: ## Analyse si le code suit le standard de Symfony
	@php-cs-fixer fix --dry-run --verbose --diff


.PHONY: codestyle-fix
codestyle-fix: ## Corrige les erreurs de standard de dev de Symfony
	@php-cs-fixer fix


.PHONY: rector
rector: ## Analyse de la qualité du code en suivant les recommandations
	@rector process --dry-run


.PHONY: rector-fix
rector-fix: # Corrige de la qualité du code en suivant les recommandations
	@rector process

ifneq (,$(wildcard ./finance.mk))
    include finance.mk
endif

##
##