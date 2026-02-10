# Demo Projects

The bundle includes three demo projects, one for each supported Symfony version. Each demo has its own `docker-compose.yml` and can be run independently:

- **Symfony 6.4 Demo**: `demo/symfony6/` (Port 8001 by default, configurable via PORT env variable)
- **Symfony 7.0 Demo**: `demo/symfony7/` (Port 8001 by default, configurable via PORT env variable)
- **Symfony 8.0 Demo**: `demo/symfony8/` (Port 8001 by default, configurable via PORT env variable)

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
# Access at: http://localhost:8001 (default port)

# Symfony 8.0 Demo
cd demo/symfony8
docker-compose up -d
docker-compose exec php composer install
# Access at: http://localhost:8001 (default port)
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
