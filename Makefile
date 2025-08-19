# =========
# Makefile
# =========

# Carga variables desde .env si existe (DB_NAME, DB_USER, DB_PASS, etc.)
ifneq (,$(wildcard .env))
	include .env
	export
endif

# Comandos base
COMPOSE ?= docker compose

# Valores por defecto si no vienen de .env
PROJECT_NAME ?= clothingshop
DB_NAME      ?= $(or $(MYSQL_DATABASE),clothingshop)
DB_USER      ?= $(or $(MYSQL_USER),appuser)
DB_PASS      ?= $(or $(MYSQL_PASSWORD),123)

# Archivo SQL por defecto (ajústalo si usas otro)
SQL_FILE ?= clothingshop.sql

# Carpeta de copias de seguridad
BACKUP_DIR ?= backups

.DEFAULT_GOAL := help

.PHONY: help up build restart logs ps down downv php-bash db-bash db-shell db-import db-dump composer-install composer-update fix-perms

help: ## Muestra esta ayuda
	@echo ""
	@echo "Comandos disponibles:"
	@echo "  make up               - Levantar contenedores en segundo plano"
	@echo "  make build            - Construir imágenes (php) y levantar"
	@echo "  make restart          - Reiniciar servicios"
	@echo "  make logs             - Ver logs siguiendo (CTRL+C para salir)"
	@echo "  make ps               - Ver estado de servicios"
	@echo "  make down             - Parar y borrar contenedores"
	@echo "  make downv            - Parar, borrar contenedores y volúmenes"
	@echo "  make php-bash         - Shell dentro del contenedor PHP"
	@echo "  make db-bash          - Shell dentro del contenedor MySQL"
	@echo "  make db-shell         - Cliente mysql conectado a $(DB_NAME)"
	@echo "  make db-import        - Importar $(SQL_FILE) en la BBDD"
	@echo "  make db-dump          - Exportar dump a $(BACKUP_DIR)/"
	@echo "  make composer-install - composer install en el contenedor PHP"
	@echo "  make composer-update  - composer update en el contenedor PHP"
	@echo "  make fix-perms        - Ajustar permisos del código dentro del contenedor"
	@echo ""

up: ## Levantar contenedores
	$(COMPOSE) up -d

build: ## Construir imágenes y levantar
	$(COMPOSE) up -d --build

restart: ## Reiniciar servicios
	$(COMPOSE) restart

logs: ## Ver logs de todos los servicios
	$(COMPOSE) logs -f --tail=200

ps: ## Estado de servicios
	$(COMPOSE) ps

down: ## Parar y borrar contenedores (sin volúmenes)
	$(COMPOSE) down

downv: ## Parar y borrar contenedores + volúmenes (¡pierdes la BBDD local!)
	$(COMPOSE) down -v

php-bash: ## Shell en el contenedor PHP (sh)
	$(COMPOSE) exec php sh -lc 'sh || bash || ash'

db-bash: ## Shell en el contenedor MySQL
	$(COMPOSE) exec db bash -lc 'bash || sh'

db-shell: ## Abrir cliente mysql conectado a la base
	$(COMPOSE) exec db mysql -u$(DB_USER) -p$(DB_PASS) $(DB_NAME)

db-import: ## Importar clothingshop.sql en la base de datos
	@test -f $(SQL_FILE) || (echo "ERROR: No se encuentra $(SQL_FILE) en el directorio actual" && exit 1)
	@echo "Importando $(SQL_FILE) en $(DB_NAME)..."
	$(COMPOSE) exec -T db mysql -u$(DB_USER) -p$(DB_PASS) $(DB_NAME) < $(SQL_FILE)
	@echo "Importación completada."

db-dump: ## Exportar dump de la base a backups/ con marca de tiempo
	@mkdir -p $(BACKUP_DIR)
	@echo "Creando volcado de $(DB_NAME)..."
	$(COMPOSE) exec -T db mysqldump -u$(DB_USER) -p$(DB_PASS) $(DB_NAME) > $(BACKUP_DIR)/$(DB_NAME)-$$(date +%F-%H%M%S).sql
	@echo "Dump creado en $(BACKUP_DIR)/"

composer-install: ## Ejecutar composer in
