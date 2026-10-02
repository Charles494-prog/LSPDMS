<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Account registration

People can request an account from `/register` or the link on the login page.
New registrations are created as unverified Secretaries with all feature
permissions disabled. The Head Secretary must grant account access before a
new user can sign in. Passwords are stored using Laravel's hashed password
cast, and duplicate email addresses are checked case-insensitively.

Signing out, resetting a password, or changing a password increments the
account's authentication version and revokes its remembered-device token.
Other active browser sessions are redirected to sign in on their next
10-second session check. Password reset and change show the message
"Change of Password Detected, Please Login Again."

## Direct messaging (Laravel Reverb)

Messaging is available to verified accounts from the **Messages** navigation item.
After migrating, configure unique Reverb credentials in `.env` (do not use the
example credentials in production):

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Build browser assets with `npm run build`. Run `php artisan reverb:start` beside
the Laravel web server for real-time message and typing updates. In production,
run Laravel's scheduler every minute (`php artisan schedule:run` from the system
scheduler); it runs `messages:cleanup-expired` daily to remove expired messages and
their private image files. For local development, `php artisan schedule:work` runs
the scheduler continuously. Images use Laravel's private local disk and are served
only through an authorized conversation route. Messaging and broadcast-channel
authorization use the existing verified `login_user` session, not Laravel's
standard authenticated-user guard.

## R2-10 eligibility and MK delistment

Run `php artisan migrate` to add the delistment history table, the R2-10
endorsement reason column, remove the obsolete date-of-entry requirement
toggle, and drop the obsolete stored R2-10 eligibility column from `records`.
Eligibility is calculated from the current record data and configured rules,
not stored in the records table. In the Superadmin dashboard, set the minimum years since date of entry
(1–3 years) and choose whether the baptism cutoff applies. When enabled, an
MS/UWP member's baptism date must be on or before the configured cutoff; the
member must also meet the configured entry-age threshold. MK members are always
eligible.

An endorsed MK member can be delisted from the De-Listment page by entering a
reason. The record is marked INACTIVE, and the date, reason, and delisting
secretary are retained in `delistment_histories` and appended to the record's
`log_section`.

## Historical monthly movement chart

The `2026_10_01_120200_create_and_import_monthly_record_snapshots` migration
creates `monthly_record_snapshots` and imports the monthly MS, UWP, and MK
counts by Purok, along with the visible movement-code counts from the supplied
January–October 2025 history image. No separate seed command or manual import
is needed. The Dashboard's Records Over Time chart reads every month currently
in this table, so newly inserted months and Puroks appear without code changes.
Select a chart point to inspect its movement codes. The graph bundles Chart.js
locally rather than requiring a third-party CDN.

## Monthly Modify movement history

Run `php artisan migrate` to create the Monthly Modify history tables.
`monthly_record_movement_histories` stores the prior Purok/Grupo and destination
status for each event. `monthly_record_code_histories` stores a separate row for each generated
monthly code, including member details and the deliberation date. An MS to UWP
change creates `MS: G` and `UWP: B` rows; Purok transfers create `STATUS: C-`
and `STATUS: C+` rows. A change to MK records the previous status with `E`;
`records.monthly_code` remains empty for MK. The snapshots and record update
are committed together.

An MS/UWP-to-MK reason is stored in
`records.reason_of_r2_10_endorsement` as `DD-MM-YYYY: (note)`. It is not added
to `records.log_section`.

Monthly Add and Subtract append the current dated monthly code to
`records.log_section` using `DD-MM-YYYY: STATUS: CODE,`. If a brief description
is provided, it is appended as a second dated entry.

## R4-01 monthly report

The Form Generator's Monthly Forms > R4-01 page uses the editable Word template
at `resources/templates/R4-01-EDITABLE.docx`. The download preserves its
landscape page setup, Tahoma text, and table layout. Select a Purok or All
Purok and an MS/UWP status to preview matching records in ascending
Purok–Grupo order. All-Purok exports keep each Purok on its own page sequence;
they never place one Purok's remaining rows on the next Purok's page. Each
page uses its Purok's configured Manggagawa and ministerial ID as the
Maysakop and Assigned No. The report month comes from the latest monthly session;
local, LCode, DCode, and static form labels come from the saved report settings
and template. Split Purok labels such as `2A` and `2B` are consolidated under
Purok 2, with PRK-GRP displayed as `2-Grupo`. BLG numbering runs 1–20, 21–40,
and so on across pages in the same configured Purok range, then resets to 1
for the next split range. Records without a monthly code use code A. Reports
paginate at 20 entries per page and can be downloaded as DOCX or PDF with
filenames such as `R4-01 (MS) Oct 2026.docx`. The DOCX centers the requested
data columns and left-aligns member names.
Generated DOCX and PDF downloads are retained in Form Generator > Monthly
Forms > Resumen & R4-01 Snapshots, where they can be filtered by report type,
month, and (for R4-01) status and downloaded again.

## Monthly Resumen generator

Form Generator > Monthly Forms > Resumen Generator builds the editable
MS/UWP monthly movement summary from the selected month's snapshot or active
monthly-session movements. Code A is the prior month's closing count; B and C+
are added, while C-, D, DD, E, G, and H are deducted to calculate the new
total. Split Puroks are shown separately by their configured Grupo ranges. The
page previews the MS and UWP totals and exports the supplied editable Word form
from `resources/templates/RESUMEN-EDITABLE.docx`.

In Monthly Modify, changing an MS/UWP record to MK records the prior status's
`E` code in the monthly history and requires an R2-10 reason. This does not
mark the member as endorsed; the separate R2-10 endorsement step remains
required. Subtract only marks eligible MS/UWP records INACTIVE and is blocked
while a record has a pending `monthly_code`. Inquire / Modify can edit record
details without clearing or changing that pending monthly code.

Inquire / Modify can optionally update a member's birthday and marital status.
`records.is_married` is a boolean: `1` means Married and `0` means Not Married,
which is the default for new records and unanswered marital status. Inquire /
Modify automatically sets and locks Kapisanan when the member is Married
(Buklod), or when a birthday is known (under 18 = Binhi, 18 or older = Kadiwa).
If neither is known, Kapisanan remains manually editable. Legacy
`filing_status` values are migrated into this boolean; unknown values such as
`NoInfo` become `0`.

The **Filter & BNH Helper** is available to Head Secretaries and secretaries
granted its dedicated permission. It filters active records by configured
Purok/group and Kalagayan, provides clickable Male/Female group counts, and
tracks Salaysay/Pakikipag-usap and Paghahanda checks. A member is qualified
only when both checks are marked. The helper can download its member list and
qualified gender counts as Word-compatible `.doc` files.
Group matrices are ordered by Purok and group, show a subtotal after every
configured Purok, and make each gender and total count clickable to open a
member popup. The BNH list and BNH Helper Form Resumen, including their Word
exports, use the active Purok and Kalagayan filters.
The BNH list can be searched by member name, and the same search is applied to
its Word download. The downloaded checklist uses the Wingdings Alt+0252 mark
for checked items and leaves an unqualified verdict blank. The three helper
sections are presented in separate tabs.
