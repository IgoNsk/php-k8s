REGISTRY_PATH       = registry.hub.docker.com
REGISTRY_NAMESPACE  = igonsk
APP_IMAGE_NAME      = app-api
WEB_IMAGE_NAME      = app-web
DNS_IMAGE_NAME      = dns-cache
REF_NAME           ?= $(shell git rev-parse --abbrev-ref HEAD)
IMAGE_VERSION      ?= ${REF_NAME}-$(shell git rev-parse HEAD)
APP_IMAGE_PATH      = ${REGISTRY_PATH}/${REGISTRY_NAMESPACE}/${APP_IMAGE_NAME}
APP_BASE_IMAGE_VERSION = 1519957637
APP_BASE_IMAGE_PATH = ${REGISTRY_PATH}/${REGISTRY_NAMESPACE}/${APP_IMAGE_NAME}-base
WEB_IMAGE_PATH      = ${REGISTRY_PATH}/${REGISTRY_NAMESPACE}/${WEB_IMAGE_NAME}
DNS_IMAGE_PATH      = ${REGISTRY_PATH}/${REGISTRY_NAMESPACE}/${DNS_IMAGE_NAME}

export IMAGE_VERSION APP_BASE_IMAGE_PATH APP_BASE_IMAGE_VERSION WEB_IMAGE_PATH APP_IMAGE_PATH DNS_IMAGE_PATH
SHELL := env PATH=$(PATH) /bin/bash

.PHONY: dev-init
dev-init: export COMPOSE_FILE = docker-compose.dev.yml
dev-init:
	if [ ! -f .env ]; then touch .env; fi
	docker-compose run --rm app composer install

.PHONY: dev-migrate
dev-migrate: export COMPOSE_FILE = docker-compose.dev.yml
dev-migrate:
	docker-compose exec app dockerize -wait tcp://db:5432 -timeout 30s ./yii migrate/up --interactive=0 | tail -n 10

.PHONY: dev-app-bash
dev-app-bash: export COMPOSE_FILE = docker-compose.dev.yml
dev-app-bash:
	docker-compose exec app /bin/bash

.PHONY: dev-up
dev-up: export COMPOSE_FILE = docker-compose.dev.yml
dev-up: dev-down dev-init
	docker-compose up --build --force-recreate -d
	docker-compose logs -f

.PHONY: dev-down
dev-down: export COMPOSE_FILE = docker-compose.dev.yml
dev-down:
	@-docker-compose down --remove-orphans

.PHONY: build
build: build-web build-app build-dns

.PHONY: push
push: push-web push-app push-dns

.PHONY: build-web
build-web:
	docker-compose build web

.PHONY: push-web
push-web:
	docker-compose push web

.PHONY: build-dns
build-dns:
	docker-compose build dns-cache

.PHONY: push-dns
push-dns:
	docker-compose push dns-cache

.PHONY: build-app-base
build-app-base:
	docker build -f build/php/Dockerfile.base -t ${APP_BASE_IMAGE_PATH}:$(APP_BASE_IMAGE_VERSION) .

.PHONY: push-app-base
push-app-base:
	@if [ "$$(read -s -p 'Are you sure? Did you update var APP_BASE_IMAGE_VERSION? yes|no: Default no ' choice; echo $$choice)" == "yes" ]; then \
		docker push ${APP_BASE_IMAGE_PATH}:${APP_BASE_IMAGE_VERSION}; \
	else \
		echo "\nskip make target $@"; \
	fi

.PHONY: build-app
build-app:
	docker-compose build app

.PHONY: push-app
push-app:
	docker-compose push app

.PHONY: up
up:
	docker-compose up --build --force-recreate -d

.PHONY: down
down:
	@-docker-compose down --remove-orphans