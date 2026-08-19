# Architecture

Managed Availability is one Moodle plugin with component name `availability_managed`, installed in `availability/condition/managed`.

The condition class is intentionally small. Its JSON marker is `{"type":"managed"}` and evaluation delegates to the plugin's `local\access_manager`. The same component owns course state, rule persistence, dashboard and external functions, lifecycle observers, audit, privacy, reconciliation, and administration settings.

This internal separation keeps evaluation independent from presentation without requiring a second Moodle component.

## Availability semantics

The managed marker is one node in Moodle's native availability tree:

```text
final access = managed condition AND all other Moodle conditions
```

The tree manager adds, normalizes, or removes only managed nodes. It never replaces unrelated AND/OR trees or their show/hide values. Invalid JSON fails without being overwritten.

Rules use OR semantics within the managed node: whole course, current membership of any selected group, or an explicit enrolled user. A missing rule denies access. Global suspension makes only the managed condition pass; it does not affect other Moodle checks.

## Persistence and migration

Dynamic targets are stored in `availability_managed_course`, `availability_managed_rule`, and `availability_managed_audit`, not in the availability JSON.

The upgrade step is idempotent. When legacy `local_mavail_*` tables are present, it copies their data without replacing existing consolidated records. It also migrates the old global setting and role capability assignments. Separate table names ensure that uninstalling the obsolete local plugin cannot delete consolidated data.

Moodle has no dedicated public setter for availability JSON on an existing module or section. The tree manager therefore performs a targeted database update and immediately rebuilds the course cache, matching the persistence used by core availability information classes.
