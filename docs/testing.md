# Testing

Use a dedicated, non-production Moodle site with developer debugging enabled.
After every deployment, complete the CLI upgrade, purge caches, initialise
PHPUnit when required, and execute the `availability_managed` test suite.

The acceptance dataset should contain at least three sections,
representative activities and resources, two groups, a teacher, students in
each group, and an individually targeted student. Run reconciliation for the
acceptance course after changing its fixtures.

Manual acceptance must verify that an unrelated date restriction still
blocks a user allowed by Managed Availability and that disabling the plugin
leaves the date restriction unchanged.
