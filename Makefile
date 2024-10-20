.PHONY: help runprod rundev runtest runcoverage update updatecomposer updatepackage updateglide getvendordir phpstan phpcs phpcbf phpcsfix phpcsfixtypes build buildprod builddev update

help:
		@echo "Makefile commands:"
		@echo "runprod"
		@echo "rundev"
		@echo "migrations-list - lists all migrations"
		@echo "migrations-diff - creates a new migration"
		@echo "migrations-execute VERSION=<version> - executes a specific migration"
		@echo "updatecomposer"
		@echo "updatepackage"
		@echo "updatecss"
		@echo "updateglide"
		@echo "updatedocker"
		@echo "getvendordir"
		@echo "phpstan"
		@echo "phpcs"
		@echo "phpcbf"
		@echo "phpcsfix"
		@echo "phpcsfixtypes"
		@echo "replenish"
		@echo "build"
		@echo "buildprod"
		@echo "builddev"
		@echo "update = updatecomposer updatepackage updatecss updateglide"

.DEFAULT_GOAL := rundev

SHELL = /bin/bash
LAST_WEB_COMMIT := $(shell git rev-parse --short HEAD)

runprod:
		@docker compose -f docker-compose.yml up -d --remove-orphans

runprodtest: buildprod
		@docker compose -f docker-compose.yml up -d --remove-orphans

rundev: builddev
		@docker compose up -d --remove-orphans
		@make replenish
		@docker compose exec web rm -rf data/cache/module-config-cache.application.config.cache.php

migration-list: replenish
		@docker compose exec -T web ./orm migrations:list

migration-diff: replenish
		@docker compose exec -T web ./orm migrations:diff
		@docker cp "$(shell docker compose ps -q web)":/code/module/Application/migrations ./module/Application/migrations

migration-up: replenish migration-list
		@read -p "Enter the migration version to execute (e.g., Application\\Migrations\\Version20241020212355 -- note escaping the backslashes is required): " version; \
		docker compose exec -it web ./orm migrations:execute --up $$version

migration-down: replenish migration-list
		@read -p "Enter the migration version to down (e.g., Application\\Migrations\\Version20241020212355 -- note escaping the backslashes is required): " version; \
		docker compose exec -it web ./orm migrations:execute --down $$version

stop:
		@docker compose down

runtest: loadenv
		@vendor/phpunit/phpunit/phpunit --bootstrap ./bootstrap.php --configuration ./phpunit.xml --stop-on-error --stop-on-failure

runcoverage: loadenv
		@vendor/phpunit/phpunit/phpunit --bootstrap ./bootstrap.php --configuration ./phpunit.xml --coverage-html ./coverage

runworkflows:
		@act -P ubuntu-latest=shivammathur/node:latest

getvendordir:
		@rm -Rf ./vendor
		@docker cp "$(shell docker compose ps -q web)":/code/vendor ./vendor

replenish:
		@docker cp ./public "$(shell docker compose ps -q web)":/code
		@docker compose exec web chown -R www-data:www-data /code/public
		@docker cp ./data "$(shell docker compose ps -q web)":/code
		@docker compose exec web chown -R www-data:www-data /code/data
		@docker compose exec web rm -rf data/cache/module-config-cache.application.config.cache.php
		@docker compose exec web composer dump-autoload --dev
		@docker compose exec web ./orm orm:generate-proxies

update: updatecomposer updatepackage updatecss updateglide updatedocker

loadenv:
		@set -o allexport
		@source .env
		@set +o allexport

copyconf:
		cp config/autoload/local.development.php.dist config/autoload/local.php
		cp config/autoload/doctrine.local.development.php.dist config/autoload/doctrine.local.php
		cp config/autoload/laminas-developer-tools.local.php.dist config/autoload/laminas-developer-tools.local.php

phpstan:
		@docker compose exec web echo "" > phpstan/phpstan-baseline-pr.neon
		@docker compose exec web vendor/bin/phpstan analyse -c phpstan.neon --memory-limit 1G

phpstanpr:
		@git fetch --all
		@git update-ref refs/heads/temp-phpstanpr refs/remotes/origin/main
		@git checkout --detach temp-phpstanpr
		@echo "" > phpstan/phpstan-baseline.neon
		@echo "" > phpstan/phpstan-baseline-pr.neon
		@make rundev
		@docker compose exec web vendor/bin/phpstan analyse -c phpstan.neon --generate-baseline phpstan/phpstan-baseline-pr.neon --memory-limit 1G --no-progress
		@git checkout -- phpstan/phpstan-baseline.neon
		@git checkout -
		@docker cp "$(shell docker compose ps -q web)":/code/phpstan/phpstan-baseline-pr.neon ./phpstan/phpstan-baseline-pr.neon
		@make rundev
		@docker compose exec web vendor/bin/phpstan analyse -c phpstan.neon --memory-limit 1G --no-progress

psalm: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --no-cache --no-diff

psalmall: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --no-cache --ignore-baseline --no-diff

psalmdiff: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --no-cache --show-info=true --diff

psalmbaseline: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --set-baseline=psalm/psalm-baseline.xml --no-cache --no-diff

psalmfix: loadenv
		@echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><files/>" > psalm/psalm-baseline-pr.xml
		@vendor/bin/psalm --no-cache --alter --issues=InvalidReturnType,InvalidNullableReturnType

phpcs: loadenv
		@vendor/bin/phpcs -p

phpcbf: loadenv
		@vendor/bin/phpcbf -p --filter=GitModified

phpcbfall: loadenv
		@vendor/bin/phpcbf -p

phpcsfix: loadenv
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --rules=@PSR1,@PSR12,@DoctrineAnnotation,@PHP82Migration,group_import,-single_import_per_statement module
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --rules=@PSR1,@PSR12,@DoctrineAnnotation,@PHP82Migration,group_import,-single_import_per_statement config

phpcsfixrisky: loadenv
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --allow-risky=yes --rules=@PHP80Migration:risky,-declare_strict_types,-use_arrow_functions  module
		@vendor/bin/php-cs-fixer fix --cache-file=data/cache/.php-cs-fixer.cache --allow-risky=yes --rules=@PHP80Migration:risky,-declare_strict_types,-use_arrow_functions  config

checkcomposer: loadenv
		@XDEBUG_MODE=off vendor/bin/composer-require-checker check composer.json

checkcomposerunused: loadenv
		@XDEBUG_MODE=off vendor/bin/composer-unused

checkcomposeroutdated:
		@composer outdated

updatecomposer:
		@docker cp ./composer.json "$(shell docker compose ps -q web)":/code/composer.json
		@docker compose exec web php composer update -W
		@docker cp "$(shell docker compose ps -q web)":/code/composer.lock ./composer.lock

updatepackage:
		@docker cp ./package.json "$(shell docker compose ps -q web)":/code/package.json
		@docker compose exec web npm update
		@docker cp "$(shell docker compose ps -q web)":/code/package-lock.json ./package-lock.json

updatecss:
		@docker cp ./public "$(shell docker compose ps -q web)":/code
		@docker compose exec web chown -R www-data:www-data /code/public
		@docker compose exec web npm run scss
		@docker cp "$(shell docker compose ps -q web)":/code/public/css/gewis-theme.css ./public/css/gewis-theme.css

updateglide:
		@docker cp ./docker/glide/composer.json "$(shell docker compose ps -q glide)":/glide/composer.json
		@docker compose exec glide composer update -W
		@docker cp "$(shell docker compose ps -q glide)":/glide/composer.lock ./docker/glide/composer.lock

updatedocker:
		@docker compose pull
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/web/gewisweb/web:production -f docker/web/production/Dockerfile .
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/web/gewisweb/web:development -f docker/web/development/Dockerfile .
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/web/gewisweb/glide:latest -f docker/glide/Dockerfile docker/glide
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/web/gewisweb/matomo:latest -f docker/matomo/Dockerfile docker/matomo
		@docker build --pull --no-cache -t abc.docker-registry.gewis.nl/web/gewisweb/nginx:latest -f docker/nginx/Dockerfile docker/nginx

build: buildweb buildglide buildmatomo buildnginx

buildprod: buildwebprod buildglide buildmatomo buildnginx

builddev: buildwebdev buildglide buildmatomo buildnginx

buildweb: buildwebprod buildwebdev

buildwebprod:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" -t abc.docker-registry.gewis.nl/web/gewisweb/web:production -f docker/web/production/Dockerfile .

buildwebdev:
		@docker build --build-arg GIT_COMMIT="$(LAST_WEB_COMMIT)" -t abc.docker-registry.gewis.nl/web/gewisweb/web:development -f docker/web/development/Dockerfile .

buildglide:
		@docker build -t abc.docker-registry.gewis.nl/web/gewisweb/glide:latest -f docker/glide/Dockerfile docker/glide

buildmatomo:
		@docker build -t abc.docker-registry.gewis.nl/web/gewisweb/matomo:latest -f docker/matomo/Dockerfile docker/matomo

buildnginx:
		@docker build -t abc.docker-registry.gewis.nl/web/gewisweb/nginx:latest -f docker/nginx/Dockerfile docker/nginx


