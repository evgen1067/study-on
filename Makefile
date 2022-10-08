COMPOSE=docker compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer

up:
	@${COMPOSE} up -d

down:
	@${COMPOSE} down

clear:
	@${CONSOLE} cache:clear

migration:
	@${CONSOLE} make:migration

migrate:
	@${CONSOLE} doctrine:migrations:migrate

entity:
	@${CONSOLE} make:entity

fixtload:
	@${CONSOLE} doctrine:fixtures:load

require:
	@${COMPOSER} require $2

encore_dev:
	@${COMPOSE} --env-file .env.local run node yarn encore dev --watch

encore_prod:
	@${COMPOSE} --env-file .env.local run node yarn encore production

phpunit:
	@${PHP} bin/phpunit --testdox

-include local.mk
