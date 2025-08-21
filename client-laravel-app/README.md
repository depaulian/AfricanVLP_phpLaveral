# AfricanVLP Client Application

## Prerequisites
- Docker
- PHP 8.2
- Composer
- Admin application must be running (provides shared MySQL and Redis services)

## Getting Started

1. Clone this repository to your local machine.

2. **IMPORTANT**: Start the admin application first to create shared services:
```bash
cd admin-laravel-app
docker-compose up -d
```

3. Navigate to the client application directory:
```bash
cd client-laravel-app
```

4. Build the Docker image:
Run the command `docker-compose build` to build the Docker image for AfricanVLP Client Application. Make sure you are in the same directory as the `docker-compose.yml` file when running the command.

5. Start the application:
Run the command `docker-compose up -d africanvlpclient` to start the application. This will start the PHP-FPM container running the client application and connect to the existing shared MySQL and Redis services.

**Note:** The initial startup may take some time as the dependencies are installed and the containers are set up. This application connects to shared services created by the admin application.

6. Access the application:
- The client application is accessible at http://localhost:8086.
- The shared MySQL database is accessible at `localhost:3308` with the following credentials:
  - Database: africanvlp_shared
  - User: africanvlp_user
  - Password: secret

7. Enter the docker container:
Run the command `docker exec -it africanvlpclient bash` to enter the docker container. 
While in the container run:
1. `npm install` to install the dependencies.
2. `composer require predis/predis` to install predis
3. `cp .env.example .env` to copy the env
4. Update `.env` file with shared database credentials:
   ```env
   DB_HOST=africanvlp-db
   DB_DATABASE=africanvlp_shared
   DB_USERNAME=africanvlp_user
   DB_PASSWORD=secret
   REDIS_HOST=africanvlp-redis
   ```
5. `npm run dev` to run the application (will run on port 5174)
6. `npm run build` to build the application for production
7. **Note**: Database migrations and seeding should be done from the admin application to avoid conflicts
8. To login into the client application at http://localhost:8086 use the credentials below:
   - email: client@africanvlp.com
   - password: password

8. Shut down the containers:
Run the command `docker-compose down` to shut down the application.

## Configuration

The Docker configuration includes the following services:

- `africanvlpclient`: PHP-FPM container running the AfricanVLP Client Application.

**Shared Services (provided by admin application):**
- `africanvlp-db`: Shared MySQL container for both admin and client applications.
- `africanvlp-redis`: Shared Redis container for caching and session storage.

## Important Notes

- **Always start the admin application first** - it creates the shared database and Redis services
- This client application connects to existing shared services
- Both applications share the same database and Redis instance
- Client runs on port 8086, frontend dev server on port 5174
- Database migrations should be run from the admin application to avoid conflicts

## Shared Services

The client application connects to shared services created by the admin application:
- **Database**: `africanvlp-db` (MySQL 8.0) - port 3308
- **Redis**: `africanvlp-redis` (Redis Alpine) - port 6380
- **Network**: `africanvlp-network`

## Startup Order

1. Start admin application first: `cd admin-laravel-app && docker-compose up -d`
2. Then start client application: `cd client-laravel-app && docker-compose up -d`

Or use the root Makefile:
```bash
cd ../
make both-up
```