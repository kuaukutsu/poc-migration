PHP_VERSION ?= 8.3
VERSION ?= $$(git rev-parse --verify HEAD)
USER = $$(id -u)
ARGS = $(filter-out $@,$(MAKECMDGOALS))

# https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
.PHONY: help
.DEFAULT_GOAL := help

help: ## Display this help screen
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

composer: ## composer install
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		composer:latest \
		composer install --optimize-autoloader --ignore-platform-reqs

composer-up: ## composer update
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		composer:latest \
		composer update --no-cache --ignore-platform-reqs

composer-dump: ## composer dump-autoload
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		composer:latest \
		composer dump-autoload

composer-cli: ## composer console
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		composer:latest \
		sh

fix: ## run fix tools
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		composer:latest \
		composer fix

check: ## run analysis tools
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		composer:latest \
		composer check

psalm: ## psalm
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		ghcr.io/kuaukutsu/php:${PHP_VERSION}-cli \
		./vendor/bin/psalm --php-version=${PHP_VERSION} --no-cache --show-info=true

phpstan: ## phpstan
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		ghcr.io/kuaukutsu/php:${PHP_VERSION}-cli \
		./vendor/bin/phpstan analyse -c phpstan.neon

phpunit: ## phpunit
	docker run --init -it --rm -v "$$(pwd):/app" -u ${USER} -w /app \
		ghcr.io/kuaukutsu/php:${PHP_VERSION}-cli \
		./vendor/bin/phpunit

phpcs: ## php code snifferphp: detect violations of a defined coding standard
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		ghcr.io/kuaukutsu/php:${PHP_VERSION}-cli \
		./vendor/bin/phpcs

phpcbf: ## php code sniffer: automatically correct
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		ghcr.io/kuaukutsu/php:${PHP_VERSION}-cli \
		./vendor/bin/phpcbf

rector: ## rector
	docker run --init -it --rm -u ${USER} -v "$$(pwd):/app" -w /app \
		ghcr.io/kuaukutsu/php:${PHP_VERSION}-cli \
		./vendor/bin/rector

.PHONY: infection
infection:
	- docker build --target tests -t app_cli .docker/php/cli
	- docker run --init -it --rm \
		--add-host=host.docker.internal:host-gateway \
		--env-file .docker/base.env \
		-u $(USER) \
		-v "$$(pwd):/app" \
		-w /app \
		app_cli ./vendor/bin/infection
	- docker image rm -f app_cli


## App

up: ## Run server
	USER=$(USER) docker compose -f ./docker-compose.yml --profile serve up -d --remove-orphans

stop: ## Stop server
	docker compose -f ./docker-compose.yml --profile serve stop

restart: ## Restart server
	USER=$(USER) docker compose -f ./docker-compose.yml --profile serve restart

down: stop
	docker compose -f ./docker-compose.yml down --remove-orphans

build:
	- USER=$(USER) docker compose -f ./docker-compose.yml build cli
	- USER=$(USER) docker compose -f ./docker-compose.yml build postgres
	- USER=$(USER) docker compose -f ./docker-compose.yml build mysql

remove: down _image_remove _container_remove _volume_remove

app:
	USER=$(USER) docker compose -f ./docker-compose.yml run --rm -u $(USER) -w /example cli sh

mysql:
	USER=$(USER) docker compose -f ./docker-compose.yml run --rm -u $(USER) -w /src mysql sh

_image_remove:
	docker image rm -f \
		migration-cli \
		migration-postgres \
		migration-mysql

_container_remove:
	docker rm -f \
		migration_postgres \
		migration_mysql

_volume_remove:
	docker volume rm -f \
		migration_pg_data \
		migration_mysql_data
