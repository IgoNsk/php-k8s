REGISTRY_PATH       = docker-hub.2gis.ru
REGISTRY_NAMESPACE  = 2gis-webapi
APP_IMAGE_NAME      = app-api
WEB_IMAGE_NAME      = app-web
REF_NAME           ?= $(shell git rev-parse --abbrev-ref HEAD)
IMAGE_VERSION      ?= ${REF_NAME}-$(shell git rev-parse HEAD)
APP_IMAGE_PATH      = ${REGISTRY_PATH}/${REGISTRY_NAMESPACE}/${APP_IMAGE_NAME}
APP_BASE_IMAGE_VERSION = 1519957637
APP_BASE_IMAGE_PATH = ${REGISTRY_PATH}/${REGISTRY_NAMESPACE}/${APP_IMAGE_NAME}-base
WEB_IMAGE_PATH      = ${REGISTRY_PATH}/${REGISTRY_NAMESPACE}/${WEB_IMAGE_NAME}

export IMAGE_VERSION APP_BASE_IMAGE_VERSION
SHELL := env PATH=$(PATH) /bin/bash

.PHONY: dev-init
dev-init: export COMPOSE_FILE = docker-compose.dev.yml
dev-init:
	$(info target: $@, IMAGE_VERSION: $(IMAGE_VERSION))
	if [ ! -f .env ]; then touch .env; fi

.PHONY: dev-up
dev-up: export COMPOSE_FILE = docker-compose.dev.yml
dev-up: dev-down dev-init
	$(info target: $@, IMAGE_VERSION: $(IMAGE_VERSION))
	docker-compose up --build --force-recreate -d
	docker-compose logs -f

.PHONY: dev-down
dev-down: export COMPOSE_FILE = docker-compose.dev.yml
dev-down:
	$(info target: $@, IMAGE_VERSION: $(IMAGE_VERSION))
	@-docker-compose down --remove-orphans

.PHONY: build
build: build-web

.PHONY: push
push: push-web

.PHONY: build-web
build-web:
	$(info target: $@, IMAGE_VERSION: $(IMAGE_VERSION))
	docker-compose build web

.PHONY: push-web
push-web:
	$(info target: $@, IMAGE_VERSION: $(IMAGE_VERSION))
	docker-compose push web

.PHONY: up
up:
	$(info target: $@, IMAGE_VERSION: $(IMAGE_VERSION))
	docker-compose up --build --force-recreate -d
	docker-compose logs -f

.PHONY: down
down:
	$(info target: $@, IMAGE_VERSION: $(IMAGE_VERSION))
	@-docker-compose down --remove-orphans