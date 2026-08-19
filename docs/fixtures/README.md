# Illustrated guide fixtures

These scripts are intended only for the dedicated local Moodle test site.
They create fictional data and capture the real plugin interface used by
the English and Spanish guides.

## Rebuild the dataset

Run from the Moodle root:

```bash
php /absolute/path/to/docs/fixtures/create_user_guide.php
```

The command replaces only the dedicated course with short name
`MAVAIL-GUIDE`, creates or updates its fictional users, and stores the new
course ID in the ignored `.courseid` file. The fixture password is defined in
`create_user_guide.php` and can be supplied to the capture script through
`MOODLE_GUIDE_PASSWORD`.

## Regenerate screenshots

ChromeDriver and Google Chrome must be available. From the plugin project:

```bash
MOODLE_ADMIN_PASSWORD='local-password' python3 docs/fixtures/capture_user_guide.py all
```

Use `es` or `en` instead of `all` to regenerate one language. Optional
environment variables are `MOODLE_GUIDE_URL`, `MOODLE_GUIDE_COURSE`,
`MOODLE_ADMIN_USER`, and `MOODLE_GUIDE_PASSWORD`.

The capture process logs in through Moodle, waits for the live interface,
and writes full browser screenshots to `docs/images/guide_es` and
`docs/images/guide`. It hides unrelated local-plugin overlays but does not
alter or mock Managed Availability output.
