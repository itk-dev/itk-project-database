# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

* [PR-2](https://github.com/itk-dev/itk-project-database/pull/2)
  Add Symfony UX Turbo and stimulus

### Added

- Initial Symfony 8 rebuild of the project database.
- Initiative and Contact entities with controlled-vocabulary enums and
  free-tagging terms (tags, stakeholders, strategies).
- Dashboard, initiative list with search/filter/sort/CSV export, and create/edit
  flows with inline contacts.
- Read-only API Platform API for initiatives, contacts and terms, exposing only
  published initiatives, with Swagger UI and ReDoc documentation.
- Local username/password authentication, user administration and an
  `app:create-admin` console command.
- Bilingual (Danish/English) interface using the itk-dev design tokens.
- Image and file attachments on initiatives, stored privately (VichUploaderBundle)
  and served through an authenticated download controller.
- Functional smoke tests covering the web pages, the public API and uploads.

### Changed

- Contacts are no longer exposed on the public API (they contain personal data).
- Removed the total-budget figure from the dashboard.
