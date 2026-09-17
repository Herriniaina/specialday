# SpecialDay

Symfony application running on FrankenPHP via Docker.

## Getting Started

1. Install [Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. `docker compose build --pull --no-cache`
3. `docker compose up --wait`
4. Open `https://localhost` and accept the auto-generated TLS certificate
5. `docker compose down --remove-orphans` to stop
6. `make xdebug` to recreate the container php on debug mode on
6. `make xdebug-off` for launching the container php on debug mode off