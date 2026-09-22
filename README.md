# SpecialDay

![coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/Herriniaina/bc64628c9b1ba46a439c5c2e1c17437b/raw/coverage.json)

Symfony application running on FrankenPHP via Docker.

## Getting Started

1. Install [Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. `docker compose build --pull --no-cache`
3. `docker compose up --wait`
4. Open `https://localhost` and accept the auto-generated TLS certificate
5. `docker compose down --remove-orphans` to stop
6. `make xdebug` to recreate the container php on debug mode on
6. `make xdebug-off` for launching the container php on debug mode off