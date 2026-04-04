# oon.click — developer convenience targets
# All targets are PHONY (no file artifacts with these names).

.PHONY: help up down build shell test fresh seed lint dev artisan logs migrate

# Default target: show help
help:
	@echo ""
	@echo "  oon.click API — available make targets"
	@echo ""
	@echo "  Docker"
	@echo "    up       Start all containers in detached mode"
	@echo "    down     Stop and remove containers"
	@echo "    build    Rebuild images from scratch (no cache)"
	@echo "    shell    Open a bash shell inside the app container"
	@echo "    logs     Follow container logs"
	@echo ""
	@echo "  Laravel"
	@echo "    test     Run the full test suite via php artisan test"
	@echo "    fresh    Drop all tables, run migrations, and seed"
	@echo "    seed     Run database seeders only"
	@echo "    migrate  Run pending migrations"
	@echo "    lint     Check code style with Pint (read-only)"
	@echo "    dev      Start the local dev server (serve + vite + queue)"
	@echo ""
	@echo "  Deployment"
	@echo "    deploy   Run deploy.sh against production"
	@echo ""

# ─── Docker ───────────────────────────────────────────────────────────────────

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

shell:
	docker compose exec app bash

logs:
	docker compose logs -f

# ─── Laravel (local, outside Docker) ─────────────────────────────────────────

test:
	php artisan test --parallel

fresh:
	php artisan migrate:fresh --seed

seed:
	php artisan db:seed

migrate:
	php artisan migrate

lint:
	vendor/bin/pint --test

lint-fix:
	vendor/bin/pint

# Run all three dev processes concurrently (matches composer run dev)
dev:
	php artisan serve & npx vite & php artisan queue:work --stop-when-empty

# Generic artisan passthrough:  make artisan CMD="route:list"
artisan:
	php artisan $(CMD)

# ─── Deployment ───────────────────────────────────────────────────────────────

deploy:
	bash deploy.sh production
