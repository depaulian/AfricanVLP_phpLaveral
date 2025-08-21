# AfricanVLP Admin Application

## Prerequisites
- Docker
- PHP 8.2
- Composer
- Access to shared MySQL database (africanvlp-db)
- Access to shared Redis instance (africanvlp-redis)

## Getting Started

1. Clone this repository to your local machine.

2. Navigate to the admin application directory:
```bash
cd admin-laravel-app
```

3. Build the Docker image:
Run the command `docker-compose build` to build the Docker image for AfricanVLP Admin Application. Make sure you are in the same directory as the `docker-compose.yml` file whenls running the command.

4. Start the application:
Run the command `docker-compose up -d africanvlpadmin` to start the application. This will start the PHP-FPM container running the admin application, create the shared MySQL container for the database, and the shared Redis container for caching and session storage.

**Note:** The initial startup may take some time as the dependencies are installed and the containers are set up. This admin application creates the shared database and Redis services that will be used by both admin and client applications.

5. Access the application:
- The admin application is accessible at http://localhost:8085.
- The shared MySQL database is accessible at `localhost:3308` with the following credentials:
  - Database: africanvlp_shared
  - User: africanvlp_user
  - Password: secret

6. Enter the docker container:
Run the command `docker exec -it africanvlpadmin bash` to enter the docker container. 
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
5. `npm run dev` to run the application
6. `npm run build` to build the application for production
7. `php artisan migrate:fresh --seed` to seed default data
8. To login into the admin application at http://localhost:8085 use the credentials below:
   - email: admin@africanvlp.com
   - password: password

7. Shut down the containers:
Run the command `docker-compose down` to shut down the application.

## Configuration

The Docker configuration includes the following services:

- `africanvlpadmin`: PHP-FPM container running the AfricanVLP Admin Application.
- `africanvlp-db`: Shared MySQL container for both admin and client applications.
- `africanvlp-redis`: Shared Redis container for caching and session storage.

## Important Notes

- This admin application creates the shared database and Redis services
- The client application will connect to these shared services
- Always start the admin application before the client application
- Both applications share the same database and Redis instance
- Admin runs on port 8085, frontend dev server on port 5173

## Shared Services

The admin application creates shared services that are used by both admin and client applications:
- **Database**: `africanvlp-db` (MySQL 8.0)
- **Redis**: `africanvlp-redis` (Redis Alpine)
- **Network**: `africanvlp-network`