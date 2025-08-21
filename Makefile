.PHONY: setup admin-up admin-down client-up client-down both-up both-down db-shell redis-shell

# Initial setup - creates shared network and volume
setup:
	docker network create africanvlp-network || true
	docker volume create africanvlp-db-data || true

# Admin app (starts shared database and redis)
admin-up:
	cd admin-laravel-app && docker-compose up -d

admin-down:
	cd admin-laravel-app && docker-compose down

# Client app (connects to existing shared services)
client-up:
	cd client-laravel-app && docker-compose up -d

client-down:
	cd client-laravel-app && docker-compose down

# Both apps
both-up: admin-up client-up
both-down: client-down admin-down

# Database access
db-shell:
	docker exec -it africanvlp-db mysql -u africanvlp_user -psecret africanvlp_shared

# Redis access
redis-shell:
	docker exec -it africanvlp-redis redis-cli

# View logs
admin-logs:
	cd admin-laravel-app && docker-compose logs -f

client-logs:
	cd client-laravel-app && docker-compose logs -f

# Check shared services status
shared-status:
	docker ps --filter "name=africanvlp-"

# Clean up everything
clean:
	docker-compose -f admin-laravel-app/docker-compose.yml down -v
	docker-compose -f client-laravel-app/docker-compose.yml down -v
	docker network rm africanvlp-network || true
	docker volume rm africanvlp-db-data || true