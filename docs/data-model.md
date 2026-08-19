# Data model

Managed targets are stored in the single `availability_managed_rule` table. Names, emails, group membership, and enrolment data remain owned by Moodle and are resolved dynamically.

Each row identifies:

- a course and one item (`section` or `cm`);
- one positive scope (`course`, `group`, or `user`) and its Moodle ID;
- enabled state and creation/modification metadata.

The unique index on `(courseid, itemtype, itemid, scope, scopeid)` makes rule writes idempotent. Item and scope indexes support dashboard lookups planned for later phases.

## Resolution

For an actively enrolled user, `access_manager` loads enabled rules for the course and builds a request-local set keyed by `itemtype:itemid`. A rule enters that set when any of these is true:

1. its scope is the current course;
2. its group is one of the user's current Moodle groups;
3. its user ID is the current user.

No match means denied. An inactive or missing enrolment is denied before rule resolution. Group membership is never copied into plugin storage.

The cache key is `courseid:userid`. Repository writes clear the request cache. There is no persistent MUC cache.
