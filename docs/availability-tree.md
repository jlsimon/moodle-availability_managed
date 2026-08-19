# Availability tree handling

Moodle stores children in `c`. An AND/NOT-OR root uses the parallel `showc` array, while OR/NOT-AND roots and all nested groups use one boolean `show`. Managed Availability treats that complete object as data owned jointly with Moodle and other availability plugins.

## Insertion

- Null availability becomes an AND root containing one managed marker.
- An existing AND root receives the marker as one additional child.
- An existing OR root is preserved as one child of a new AND root, with the managed marker as its sibling.
- Existing managed markers at any depth are removed before canonical insertion.
- An already canonical tree is unchanged.

Wrapping OR is essential. Adding the marker directly to OR would mean `managed OR existing`, allowing either branch to bypass the other. The wrapper enforces `managed AND all existing availability logic`.

## Removal

Removal walks the entire tree, deletes only conditions whose exact `type` is `managed`, removes corresponding root `showc` entries, and prunes nested groups left empty. Other nodes, operators, ordering, plugin-specific properties, and display values are retained. An empty root is stored as null.

## Failure behavior

Malformed JSON and roots whose `c` and `showc` arrays do not align are rejected before any database write. Nested groups require Moodle's boolean `show`. This favors an explicit reconciliation error over silently replacing unrelated restrictions.
