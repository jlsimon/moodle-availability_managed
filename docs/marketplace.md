# Moodle Marketplace submission data

## Identity

- Plugin name: **Managed availability**
- Component: `availability_managed`
- Plugin type: Availability restriction
- Repository: <https://github.com/jlsimon/moodle-availability_managed>
- License: GNU GPL v3 or later

## Descriptions

Short description:

> Centrally control access to course sections and activities for the whole
> course, selected groups, or individual enrolled users.

The full English description is the introduction and **Features** section of
the repository `README.md`. Version 1.0.0 is the first public release of this
standalone component.

## Release compatibility

- Minimum Moodle version: Moodle 4.5 LTS (`2024100700`)
- Also tested: Moodle 5.2
- Databases tested: MariaDB and PostgreSQL
- PHP tested: 8.1 with Moodle 4.5; 8.4 with Moodle 5.2
- Additional plugin dependencies: None
- External services, credentials, and subscriptions: None

## Public links

- Documentation: <https://github.com/jlsimon/moodle-availability_managed/blob/main/docs/user_guide.md>
- Bug tracker: <https://github.com/jlsimon/moodle-availability_managed/issues>
- Security reports: <https://github.com/jlsimon/moodle-availability_managed/security/advisories/new>
- CI: <https://github.com/jlsimon/moodle-availability_managed/actions/workflows/moodle-ci.yml>

## Suggested screenshots

Upload representative images from `docs/images/guide/`, especially:

1. `01-dashboard-overview.png`
2. `04-edit-targets-modal.png`
3. `05-group-perspective.png`
4. `08-student-ana-view.png`
5. `10-course-configuration.png`
6. `11-audit-log.png`

## Privacy summary

The plugin stores course configuration, course-item access targets, acting
user IDs, and a functional audit trail. Group membership, enrolment, names,
and email addresses remain in Moodle core and are resolved dynamically. No
data is sent to external services. A Moodle Privacy API provider implements
discovery, export, and deletion for individually targeted users and audit
actors.

## Upload artifact

The release ZIP must contain a single top-level directory named `managed`.
Its `version.php` must identify release `1.0.0`, and `CHANGES.md` supplies the
release notes. Use the separately attached release asset rather than
GitHub's automatically generated source archive.
