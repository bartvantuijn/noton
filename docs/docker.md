# Self-host Noton with Docker

You can self-host Noton using Docker and Docker Compose. This guide explains how to set up the application with a PostgreSQL or MySQL database.

## Requirements

- Docker
- Docker Compose

## Quick Start

1. Create a `docker-compose.yaml` file based on the provided `docker-compose.example.yaml` file in this repository. You can download it or copy its contents manually.

```yaml
services:
  noton:
    container_name: noton
    image: ghcr.io/bartvantuijn/noton:latest
    restart: unless-stopped
    ports:
      - "6686:6686"
    networks:
      - noton
    environment:
      APP_NAME: Noton
      APP_ENV: local
      APP_DEBUG: true
      APP_URL: http://localhost:6686
      APP_LOCALE: nl
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_PORT: 5432
      DB_DATABASE: noton_database
      DB_USERNAME: noton_user
      DB_PASSWORD: noton_password
    volumes:
      - ./storage:/srv/www/storage

  postgres:
    container_name: postgres
    image: postgres:16
    restart: unless-stopped
    networks:
      - noton
    environment:
      POSTGRES_DB: noton_database
      POSTGRES_USER: noton_user
      POSTGRES_PASSWORD: noton_password
    volumes:
      - ./postgres-data:/var/lib/postgresql/data

networks:
  noton:
    driver: bridge
```

2. Start Noton using Docker Compose:

```bash
docker compose up -d
```

3. Access the application:

```
http://localhost:6686
```

## Configuration

The default `docker-compose.yaml` uses:

- `noton` (Application container)
- `postgres` (PostgreSQL database)

You can customize the database credentials or switch to MySQL by editing the `docker-compose.yaml`.

## Environment Variables

Key settings such as the application environment, URL, and database credentials can be customized via environment variables in your `docker-compose.yaml` file. Below are some commonly used variables:

- `APP_NAME`: The name of the application (displayed in the UI).
- `APP_ENV`: `local` or `production`, determines how the application behaves.
- `APP_DEBUG`: `true` to enable debug mode.
- `APP_URL`: The base URL where your app will be hosted (e.g. `https://noton.example.com`).
- `APP_LOCALE`: The default locale (e.g. `nl`, `en`).
- `DB_*`: Database connection settings (host, port, user, password, etc.).

> Please note that when `APP_ENV` is **not** set to `local`, Noton automatically treats all incoming requests as HTTPS

## Custom Domains and SSL

If you're deploying behind a reverse proxy like Traefik or Nginx, make sure:

- The container exposes port `6686`
- You provide the correct labels for routing (see Traefik documentation)
- Your external Docker network is referenced correctly in `docker-compose.yaml`
- The `APP_URL` environment variable matches the public domain, for example: `APP_URL=https://noton.example.com`
- The `APP_ENV` environment variable is **not** set to `local` so that HTTPS behavior is enforced by the application

Additionally, make sure:

- Port `6686` is open on your host machine and not blocked by firewalls
- You can inspect logs if needed:

```bash
docker compose logs -f noton
```

## Volumes

For persistence, the app uses:

- `./storage` for application storage
- `./postgres-data` or `./mysql-data` for database data

## Updating

When a new version is released:

```bash
docker compose pull
docker compose up --no-deps -d
```

---
