# Verification record

Date: 2026-10-19. Environment: Windows, PHP 8.2.12, Composer 2.9.5, Node 24.14.1, OSS Serverless 4.4.0.

| Check | Result |
|---|---|
| `php tests/run.php` | 15 tests, 0 failures |
| PHP syntax checks | All application, test and helper files passed |
| `composer validate --strict` | Passed |
| `composer audit --format=json` | No advisories or abandoned packages reported |
| PowerShell parser | All helper scripts passed |
| Local HTTP smoke test | Three GET endpoints returned 200 with success=true and correct counts/totals |
| Local POST without AWS configuration | Returned 500 with generic JSON error, as intended |
| PDF preview | One A4 page; extracted text checked; rendered PNG visually inspected for alignment and readability |
| S3 network-boundary tests | Real SDK request contains PDF bytes, expected key, PDF content type, no public ACL; denial returns API failure |
| Serverless package | Passed for the real Sydney bucket; portable CloudShell bundle checksum matched |
| ZIP review | Contains entrypoint, source, CSVs, Composer autoload, AWS S3 compiled models; no Node tools, tests, output or credentials |
| CloudFormation review | Bref PHP 8.2 layer on provided.al2023, timeout 28s, PutObject restricted to bucket/payslips/* |
| Code review | Independent read-only review found no actionable defects |
| Source secret-pattern scan | 28 initial untracked submission files scanned with no findings; staged scan repeated before commit |
| Publication secret scans | 30 staged, tracked and history files/blobs; no pattern findings; ignore rules reviewed |
| GitHub publication | Initial commit pushed to `gab26g/serverless-payroll-api`, branch `main` |
| Live deployment | CloudShell/Serverless succeeded in 63 seconds; stack UPDATE_COMPLETE |
| Live endpoints | All four returned HTTP 200 and success=true |
| Live error cases | Invalid ID 400, absent record 404, wrong method 405, absent endpoint 404 |
| Live S3 upload | PDF exists with application/pdf and SSE-S3; actual execution-role prefix policy verified |
| Downloaded S3 PDF | One page; expected employee, dates and money values checked; rendering visually reviewed |
| Submission evidence | Six evidence images, deployment screenshot, raw responses and downloaded PDF prepared |

The compiled AWS manifest is `vendor/aws/aws-sdk-php/src/data/manifest.json.php`; a separate `manifest.json` is not required.

## Actual source results

- Employees: 500.
- Payroll rows: 600.
- Gross: 4,981,004.63; deductions: 849,478.70; net: 4,131,525.93.
- Payroll #1 joins employee #319, Deniz Fischer; gross 14,131.54, deductions 12,626.36, net 1,505.18.
- Totals were independently recomputed with PowerShell decimal arithmetic before being pinned in the tests.

## Known audit limitation

`npm audit` reports four high-severity package entries arising from a single inherited `braces` glob-pattern advisory (GHSA-vfj7-8cjw-p6xm) through micromatch, fast-glob and osls. No fix was available from the audit. These are development-only dependencies and not in the Lambda ZIP. Packaging uses fixed patterns from the checked-in configuration. This finding is documented, not represented as a clean npm audit.

## Remaining user steps

- Review and personalize the reflection draft in SUBMISSION.md.
- Submit the repository link, API URL, screenshots, issue/resolution and reflection through the course page and verify its receipt.

The new student profile's provisioning limits were resolved operationally by deploying through the already-authorized shared console/CloudShell identity. No extra student-user permissions were granted. Live deployment details are in DEPLOYMENT-STATUS.md.

The local Windows sandbox falsely reported some downloaded files as unreadable to PHP despite reads succeeding. Tests and packaging were repeated with approved file access and passed. This is an execution-environment limitation; no vendor code or dataset permissions were weakened to hide it.
