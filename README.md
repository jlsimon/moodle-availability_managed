# Managed Availability for Moodle

[![Moodle Plugin CI](https://github.com/jlsimon/moodle-availability_managed/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/jlsimon/moodle-availability_managed/actions/workflows/moodle-ci.yml)

Managed Availability is a Moodle availability-condition plugin that gives
teachers a central course dashboard for controlling access to sections,
activities, and resources.

This repository contains one installable Moodle component:
`availability_managed`. It owns the availability condition, dashboard,
course configuration, rules, audit trail, privacy provider, safe course
restore handling, and reconciliation tools.

## Features

- Open content to the whole class, selected groups, individual enrolled
  users, or nobody.
- Manage sections, activities, and resources from one course dashboard.
- Copy a section's managed rules to all activities in that section.
- Review a group or user's progression and open the next closed section.
- Preserve Moodle date, grade, completion, grouping, visibility, and other
  availability restrictions. Managed availability combines with them and
  never bypasses them.
- Enable management independently per course and choose whether existing
  content starts open or closed.
- Suspend managed restrictions globally without deleting stored rules.
- Record functional changes in an audit trail.
- Reconcile conditions and rules from the command line.
- Support Moodle Privacy API.
- Ensure restored course copies start unmanaged, without orphaned conditions
  that could otherwise close content when site-specific rules are unavailable.

## Requirements

- Moodle 4.5 LTS or later, including Moodle 5.x.
- PHP and database versions supported by the selected Moodle release.
- Conditional availability enabled in Moodle.

The plugin has no additional plugin dependencies, bundled third-party
libraries, external services, API keys, or paid subscriptions. It works out
of the box after Moodle completes the standard plugin installation.

The CI matrix tests Moodle 4.5 with PHP 8.1 and Moodle 5.2 with PHP 8.4,
using both MariaDB and PostgreSQL.

## Installation

Place the plugin directory at:

```text
availability/condition/managed
```

The directory name must be `managed`. Then complete installation through
Site administration or run these commands from the Moodle root:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

Version 1.0.0 is the initial release of this component. Installation creates
the complete schema directly from `db/install.xml`.

## Configuration and use

An administrator can enable or suspend the condition at:

```text
Site administration → Plugins → Availability restrictions → Managed availability
```

Course managers then enable Managed Availability from the course navigation
and choose the initial open or closed state. Enabling a course attaches one
`{"type":"managed"}` node to each section and activity while preserving the
surrounding Moodle availability tree.

Teachers use the dashboard from course navigation or at:

```text
/availability/condition/managed/index.php?courseid=COURSE_ID
```

See the illustrated guides for complete workflows:

- [English user guide](docs/user_guide.md) · [HTML](docs/user_guide.html)
- [Guía de usuario en español](docs/user_guide.es.md) · [HTML](docs/user_guide.es.html)

## Data and permissions

The plugin owns these database tables:

```text
availability_managed_course
availability_managed_rule
availability_managed_audit
```

Group membership, enrolments, names, and email addresses remain owned by
Moodle and are resolved dynamically.

The plugin stores course configuration, access targets, the user ID of the
person who last changed a rule, and a functional audit trail. It does not
send data to an external service. Its Moodle Privacy API provider supports
data discovery, export, and deletion for individually targeted users and
audit actors.

Capabilities provided by the plugin are:

```text
availability/managed:manage
availability/managed:viewaudit
availability/managed:configure
```

## Maintenance and testing

Reconcile one course or every enabled course:

```bash
php availability/condition/managed/cli/reconcile.php --courseid=123
php availability/condition/managed/cli/reconcile.php --all
```

Run the PHPUnit suite:

```bash
vendor/bin/phpunit --testsuite availability_managed_testsuite
```

Additional technical documentation:

- [Architecture](docs/architecture.md)
- [Availability-tree handling](docs/availability-tree.md)
- [Data model](docs/data-model.md)
- [Testing](docs/testing.md)
- [Marketplace submission data](docs/marketplace.md)

Public support resources:

- [Issue tracker](https://github.com/jlsimon/moodle-availability_managed/issues)
- [Source repository](https://github.com/jlsimon/moodle-availability_managed)
- [Security policy](SECURITY.md)

## License

Copyright © 2026 Juan Luis Simon.

This plugin is licensed under the [GNU GPL v3 or later](COPYING.txt).
