# Illustrated guide fixtures

These scripts are intended only for the dedicated local Moodle test site.
They create fictional data and capture the real plugin interface used by
the English and Spanish guides.

## Rebuild the dataset

Set a password used only by the fictional accounts and run the script from
the installed plugin:

```bash
MOODLE_GUIDE_PASSWORD='local-fixture-password' \
  php availability/condition/managed/docs/fixtures/create_user_guide.php
```

The command replaces only the dedicated course with short name
`MAVAIL-GUIDE`, creates or updates its fictional users, and stores the new
course ID in the ignored `.courseid` file.

## Regenerate screenshots

ChromeDriver and Google Chrome must be available. From the plugin project:

```bash
MOODLE_GUIDE_URL='https://moodle.example' \
MOODLE_ADMIN_PASSWORD='local-admin-password' \
MOODLE_GUIDE_PASSWORD='local-fixture-password' \
  python3 docs/fixtures/capture_user_guide.py all
```

Use `es` or `en` instead of `all` to regenerate one language. Optional
environment variables are `MOODLE_GUIDE_COURSE` and `MOODLE_ADMIN_USER`.
`MOODLE_ADMIN_PASSWORD` and `MOODLE_GUIDE_PASSWORD` are required.

The capture process logs in through Moodle, waits for the live interface,
and writes full browser screenshots to `docs/images/guide_es` and
`docs/images/guide`. It hides unrelated third-party overlays but does not
alter or mock Managed Availability output.
