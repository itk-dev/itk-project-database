---
name: project-db-uses-mariadb
description: This project runs on MariaDB; any Postgres references are leftover Symfony Flex scaffolding and should be flagged as bugs in reviews.
metadata:
  type: project
---

The itk-project-database stack runs on **MariaDB**, not Postgres. Authoritative
signals: `DATABASE_URL` in `.env` points at `mariadb:3306`, `.env.test` connects
to `mariadb`, the `tests.yaml` CI workflow pulls/uses the `mariadb` service, and
the `mariadb` service is the one wired with `depends_on`.

Any Postgres references are **leftover Symfony Flex doctrine-bundle scaffolding**
and should be treated as bugs to remove, not as a second supported database:
- a `database: postgres` service in `docker-compose.yml` (with `database_data`
  volume and a `!ChangeMe!` default password)
- `compose.override.yaml` exposing port 5432
- `identity_generation_preferences` for `PostgreSQLPlatform` in
  `config/packages/doctrine.yaml`

**Why:** the initial-project branch was scaffolded with the default Flex Postgres
template, then switched to MariaDB without cleaning up the Postgres artifacts.
**How to apply:** when reviewing devops/config, flag surviving Postgres bits as
blockers; verify Doctrine migrations were generated against MariaDB, not Postgres.
Ties in with the user's [[no-asset-compile-in-dev]] dev-hygiene concerns about
leftover scaffolding in the install/devops layer.
