# Project database

A Symfony application for registering and browsing municipal **initiatives** and
their **contacts**, with a public read-only [API Platform](https://api-platform.com/)
API. It is a rebuild of the previous Drupal-based project database, focused on a
friendlier interface for creating and getting an overview of initiatives.

The project follows the itk-dev
[`symfony` Docker template](https://github.com/itk-dev/devops_itkdev-docker) and
runs on PHP 8.4 / Symfony 8.

## Features

- Dashboard with key figures and a status overview of all initiatives.
- List of initiatives with full-text search, faceted filters, column sorting,
  pagination and CSV export.
- Create and edit initiatives, including inline creation of contacts,
  free-tagging of tags, stakeholders and strategies, and image/file uploads.
- Contact management.
- Private file/image uploads, served only to signed-in users.
- Read-only public API (`/api`) with Swagger UI and ReDoc documentation.
- Local username/password login with user administration for administrators.
- Bilingual interface (Danish and English).

## Requirements

- [Docker](https://www.docker.com/) and the itk-dev
  [`itkdev-docker-compose`](https://github.com/itk-dev/devops_itkdev-docker) setup.
- [Task](https://taskfile.dev/) (optional, but the commands below use it).

## Installation

Start the containers and install everything (dependencies, database schema and
development fixtures):

```sh
task install
```

Without Task:

```sh
docker compose up --detach
docker compose exec phpfpm composer install
docker compose exec phpfpm bin/console doctrine:migrations:migrate --no-interaction
docker compose exec phpfpm bin/console doctrine:fixtures:load --no-interaction
```

The site is served on the domain configured in `.env`
(`COMPOSE_DOMAIN`, e.g. `https://itk-project-database.local.itkdev.dk`).

### Signing in

The development fixtures create two users (password `password` for both):

- `admin@example.com` — administrator
- `editor@example.com` — editor

Create an administrator manually with:

```sh
task create-admin -- you@example.com "Your Name"
```

## API

The read-only API is available under `/api`:

- `GET /api/initiatives` — list published initiatives (filterable, paginated).
- `GET /api/initiatives/{id}` — a single published initiative.
- `GET /api/terms` — the classification terms (tags, stakeholders, strategies).
- Interactive documentation (Swagger UI / ReDoc) is served at `/api`.

Only published initiatives are exposed; drafts are managed in the web interface.
Contacts and uploaded files are **not** exposed on the API — contacts contain
personal data, and files are served only through the authenticated web UI.

## Development

```sh
task                      # list all tasks
task console -- <command> # run a Symfony console command
task coding-standards:fix # apply coding standards
task static-analysis      # run PHPStan
task test                 # run the test suite
task ci                   # run everything CI runs
```

### Note on controlled vocabularies

The controlled vocabularies (status, category, type, organisational anchoring,
endorsement author and funding) are modelled as PHP enums in `src/Enum/` with
placeholder values. Adjust the enum cases and their translations
(`translations/messages.*.yaml`) to match the real domain values.
