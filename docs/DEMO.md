# Demo Projects

The bundle includes three demo projects, one for each supported Symfony version. Each demo has its own `docker-compose.yml` and can be run independently:

- **Symfony 6.4 Demo**: `demo/symfony6/` (Port 8001 by default, configurable via `PORT`)
- **Symfony 7.0 Demo**: `demo/symfony7/` (Port 8002 by default, configurable via `PORT`)
- **Symfony 8.0 Demo**: `demo/symfony8/` (Port 8003 by default, configurable via `PORT`)

**Docker stack:** Demos use **FrankenPHP** with **Caddy** as the web server. The bundled **Caddyfiles serve HTTP only** (`:80` in the container, published to the host as `PORT`: 8001/8002/8003 by demo). TLS/HTTPS is **not** enabled in the default demo configs; you can extend Caddy if you need HTTPS locally.

## Quick Start with Docker

Each demo can be started independently:

```bash
# Symfony 6.4 Demo
cd demo/symfony6
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8001 (default port)

# Symfony 7.0 Demo
cd demo/symfony7
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8002 (default port)

# Symfony 8.0 Demo
cd demo/symfony8
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8003 (default port)
```

Or use the Makefile helper commands from the `demo/` directory:

```bash
cd demo

make up-symfony6
make install-symfony6

make up-symfony7
make install-symfony7

make up-symfony8
make install-symfony8
```

## Troubleshooting

### Composer: `Could not resolve host: repo.packagist.org` (curl error 6)

The demo `docker-compose.yml` sets explicit DNS servers on the PHP service so Composer can resolve Packagist inside the container (common on **Docker Desktop** / **WSL2** when the embedded resolver fails).

1. **Recreate the container** so Docker applies the `dns:` block (editing compose alone does not update a running container):
   ```bash
   cd demo/symfony7   # or symfony6 / symfony8
   docker compose down
   docker compose up -d --force-recreate
   ```
2. **Check resolution from inside the container**:
   ```bash
   docker compose exec php getent hosts repo.packagist.org
   ```
   If this fails, your network or VPN may block public DNS; replace the `dns:` entries with your corporate resolvers, or use a `docker-compose.override.yml` (local only, not committed).
3. **Confirm the compose in use** includes `dns:` (for example: `docker compose config | grep -A5 dns`).

### Port already allocated (`Bind for 0.0.0.0:800x failed`)

Another stack is using the host port. Set `PORT` in `.env` (see each demo’s `.env.example`) or stop the other container, then `docker compose down` and `docker compose up -d` again.

Each demo includes:

- Independent `docker-compose.yml` for easy setup
- Complete test suite to verify bundle integration
- Code coverage configuration (100% coverage for demo code)
- Example controller and templates
- Web Profiler integration

## Running Demo Tests

Each demo has its own test suite with code coverage:

```bash
cd demo

make test-symfony6
make test-symfony7
make test-symfony8

make test-coverage-symfony6
make test-coverage-symfony7
make test-coverage-symfony8

make test-coverage-all
```

Or directly in each demo directory:

```bash
cd demo/symfony6
docker-compose exec php composer test
docker-compose exec php composer test-coverage
```

See [demo/README.md](../demo/README.md) in the repository for more details.
