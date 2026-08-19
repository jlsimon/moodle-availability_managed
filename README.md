# Managed Availability for Moodle

Managed Availability is a single Moodle availability-condition plugin that gives teachers one course dashboard for controlling access to sections, activities, and resources.

An item can be opened for the whole class, selected groups, individual enrolled users, or nobody. The managed condition is combined with Moodle's existing date, completion, grade, grouping, visibility, and other restrictions; it never bypasses them.

## Requirements and installation

- Moodle 4.5 LTS or later, including Moodle 5.x.
- Conditional availability enabled.

Install the only required component at:

```text
availability/condition/managed
```

Then run from the Moodle root:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

## Operation

Administrators can globally suspend or enable the product in the Managed availability plugin settings. Global suspension is reversible: managed conditions allow access temporarily while all unrelated restrictions remain active.

Course managers can enable a course with either all existing content open or all managed content closed. Enabling attaches exactly one `{"type":"managed"}` marker to each section and module while preserving the surrounding availability tree.

The teacher dashboard is available from course navigation or directly at:

```text
/availability/condition/managed/index.php?courseid=COURSE_ID
```

Related pages provide course configuration, help, and audit history. AJAX actions edit item rules, copy section settings to activities, and open the next section for a group or user.

## Data and permissions

The plugin owns these tables:

```text
availability_managed_course
availability_managed_rule
availability_managed_audit
```

Capabilities are:

```text
availability/managed:manage
availability/managed:viewaudit
availability/managed:configure
```

Group membership and enrolments are always read from Moodle and are not duplicated.

## Reconciliation and testing

```bash
php availability/condition/managed/cli/reconcile.php --courseid=123
php availability/condition/managed/cli/reconcile.php --all
vendor/bin/phpunit --testsuite availability_managed_testsuite
```

See [testing](docs/testing.md), [availability-tree handling](docs/availability-tree.md), and [data model](docs/data-model.md) for further detail.
