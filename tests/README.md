Run the isolated plan checks with:

    php vendor/bin/phpunit --bootstrap vendor/autoload.php tests/PlanEntitlementsTest.php

Tests create their own SQLite in-memory tables and never migrate or modify the application database.

Plan quotas count current invoice, client, user and product records for the company; they do not reset monthly. Null means unlimited and zero blocks creation. Existing records remain readable after expiry or downgrade. Recurring schedules retain their due date when invoice creation is blocked.

White label removes platform attribution from the public invoice and quotation shell. Custom branding, white label, custom domain and platform subdomain are separate plan entitlements. Domain features also require platform configuration and custom domains still require DNS/TLS verification. Existing custom-branding plans receive the three new entitlements in the migration; other plans start with them disabled. Administrators can change every entitlement in Plans.

Each plan has trial_days (0–365 in the admin form). Paid signups use that duration; zero grants no trial and directs the account to subscription checkout. Free plans remain active without trial expiry. Existing tenants keep their existing subscription end date when trial_days is changed.
