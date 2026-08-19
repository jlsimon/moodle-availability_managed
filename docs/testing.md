# Testing

The mandatory integration environment is `atudemos.mudel.es`, Moodle root `/var/www/vhosts/atudemos.mudel.es/moodle`, PHP `/usr/bin/php8.3`, and acceptance course `courseid=2`.

After every deployment run the CLI upgrade, purge caches, initialise PHPUnit when the plugin version changes, execute the `availability_managed` test suite, and reconcile course 2. The acceptance dataset should contain three sections, representative Page/Quiz/SCORM modules, Groups A/B, a teacher, students in each group, and an individually targeted student.

Manual acceptance must verify that an unrelated date restriction still blocks a user allowed by Managed Availability and that disabling the product leaves the date restriction unchanged.
