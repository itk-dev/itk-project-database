# Project database

A Symfony application for registering and browsing municipal **initiatives** and
their **contacts**. It is a rebuild of the previous Drupal-based project
database, focused on a friendlier interface for creating and getting an overview
of initiatives.

The project follows the itk-dev
[`symfony` Docker template](https://github.com/itk-dev/devops_itkdev-docker) and
runs on PHP 8.4 / Symfony 8.

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
(`COMPOSE_DOMAIN`, e.g. `https://itk-projects.local.itkdev.dk`).

### Signing in

The development fixtures create two users (password `password` for both):

- `admin@example.com` — administrator
- `editor@example.com` — editor

Create an administrator manually with:

```sh
task create-admin -- you@example.com "Your Name"
```

## Access control

The application uses a single, flat trust model: authentication is required for
everything (the firewall protects `^/`), and **every authenticated user is fully
trusted**. Any signed-in user (`ROLE_USER`) can create, view, edit and delete any
initiative or contact, and can download any uploaded image or attachment by id.

The only elevated capability is **user administration** (`/admin/**`), which
requires `ROLE_ADMIN`.

This is intentional: the project database is an internal tool for a small,
trusted group of municipal editors, so per-record ownership or per-action
authorization would add complexity without a real security benefit. Uploaded
files are stored outside the web root and served only through the authenticated
`MediaController`, so they are never anonymously reachable — but they are not
restricted between authenticated users.

If a future requirement calls for restricting who may edit/delete a given record
(or read a given file), introduce a Symfony [Voter](https://symfony.com/doc/current/security/voters.html)
rather than loosening or working around the flat model.

## Development

```sh
task                      # list all tasks
task console -- <command> # run a Symfony console command
task coding-standards:fix # apply coding standards
task static-analysis      # run PHPStan
task test                 # run the test suite
task ci                   # run everything CI runs
```
