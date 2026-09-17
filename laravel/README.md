# School Voting App — API

Backend for running school elections. Administrators create elections and add candidates; students verify a school email address, receive a one-time code, and cast a single vote. A public live-results page can be shared with anyone.

This is the Laravel rewrite of the original Express/MongoDB service. URLs, request bodies and response shapes are unchanged, so existing clients keep working.

## Stack

- PHP 8.4 · Laravel 13 · Laravel Sanctum (API tokens)
- PostgreSQL/MySQL in production, SQLite for local development and tests
- Resend for transactional email, Cloudinary for candidate photos
- Pest for tests, Scramble for generated OpenAPI docs

## Local setup

```bash
cd laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed        # SQLite; seeds admin@school.edu / password with one election
composer run dev                  # server + queue worker + log tail
```

The API is then at `http://localhost:8000`, interactive docs at `http://localhost:8000/docs/api`.

With the default `MAIL_MAILER=log`, verification codes are written to `storage/logs/laravel.log` instead of being sent. Set `MAIL_MAILER=resend` plus `RESEND_API_KEY` and `MAIL_FROM_ADDRESS` to send real email. Candidate photo uploads need the three `CLOUDINARY_*` variables.

Run the test suite with:

```bash
./vendor/bin/pest
```

## Environment variables

| Variable | Purpose |
|---|---|
| `APP_URL` | Public URL of this API (used in the monitor link returned after voting). |
| `CLIENT_URL` | Public URL of the voting frontend; `votingLink` in the create-election response is `{CLIENT_URL}/vote/{id}`. |
| `DB_*` | Database connection. `DB_CONNECTION=sqlite` needs nothing else. |
| `MAIL_MAILER`, `RESEND_API_KEY`, `MAIL_FROM_ADDRESS` | Email delivery. |
| `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET` | Photo storage. |
| `QUEUE_CONNECTION` | `sync` sends mail inline during the request (default on Render, where the free tier has no worker); `database` queues it for `php artisan queue:work`. |

## API overview

All endpoints are under `/api`. The full, always-current reference is generated from the code at `/docs/api`; `docs/api-requests.http` is a runnable request collection.

**Authentication.** Admin routes take the token from `/api/admin/login` (or `verify-email` / `reset-password`) as `Authorization: Bearer <token>`. The vote route takes the token from `/api/vote/{election}/confirm` the same way. The legacy `x-auth-token` and `x-vote-token` headers are still accepted.

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/admin/signup` | — | Register; emails a verification code |
| POST | `/admin/verify-email` | — | Confirm the code; returns a token |
| POST | `/admin/login` | — | Returns a token (24h) |
| POST | `/admin/forgot-password` | — | Emails a reset code |
| POST | `/admin/reset-password` | — | Sets a new password; returns a token |
| GET | `/elections` | admin | My elections |
| POST | `/elections` | admin | Create; returns `votingLink` |
| GET | `/elections/{id}` | — | Election + candidates |
| PATCH | `/elections/{id}` | owner | Update title/description/domains |
| DELETE | `/elections/{id}` | owner | Delete with candidates and votes |
| PATCH | `/elections/{id}/status` | owner | `{ "active": true\|false }` |
| GET | `/elections/{id}/results` | owner | Vote counts per candidate |
| GET | `/elections/candidates` | admin | All my candidates |
| GET | `/elections/{id}/candidates` | — | Candidates of an election |
| POST | `/elections/{id}/candidates` | owner | Multipart `name`, `bio`, `photo` (JPEG/PNG ≤ 5 MB) |
| PATCH/POST | `/elections/{id}/candidates/{cid}` | owner | Update (POST for multipart clients) |
| DELETE | `/elections/{id}/candidates/{cid}` | owner | Delete |
| POST | `/vote/{id}/verify` | — | `{ "email" }` → emails a code, returns `sessionId` |
| POST | `/vote/{id}/confirm` | — | `{ "sessionId", "code" }` → vote token (15 min) |
| POST | `/vote/{id}` | voter | `{ "candidateId" }` |
| GET | `/monitor/results/{id}` | — | Live results JSON |

The live results page is at `/monitor/election/{id}` (outside `/api`).

Errors are JSON with a `message` field. Validation failures return **400** (with an additional `errors` map), missing/invalid tokens **401**, ownership failures **403**, unknown ids **404**, and repeated auth/vote attempts **429**.

## Behaviour worth knowing

- **One vote per email per election** is a database unique constraint, not just an application check.
- **Domain matching is exact**: `school.edu` allows `x@school.edu` only, not `x@evilschool.edu`. A leading `@` in `allowedDomains` is tolerated.
- Vote tokens are **bound to one election** via a Sanctum token ability, expire after 15 minutes and are revoked the moment the vote is recorded.
- Verification and reset codes are 6 digits, generated with a CSPRNG and stored **hashed**. Admin codes live 24 h, reset codes 1 h, voter codes 1 h.
- An admin token cannot call vote routes and a vote token cannot call admin routes (separate Sanctum guards).
- Auth and voting endpoints are rate-limited per IP and per email.
- Expired voter sessions and tokens are pruned by the scheduler (`php artisan schedule:run` every minute in production).

## Project layout

```
app/Http/Controllers/Api/Admin/AuthController.php   signup / verify / login / password reset
app/Http/Controllers/Api/ElectionController.php     election CRUD, status, results
app/Http/Controllers/Api/CandidateController.php    candidate CRUD + Cloudinary upload
app/Http/Controllers/Api/VoteController.php         verify → confirm → cast
app/Http/Controllers/MonitorController.php          public live results (JSON + page)
app/Http/Middleware/MapLegacyTokenHeaders.php       x-auth-token / x-vote-token compatibility
app/Http/Requests/                                  validation rules per endpoint
app/Policies/ElectionPolicy.php                     ownership checks
app/Services/CloudinaryUploader.php                 signed REST upload
app/Mail/                                           the three one-time-code emails
app/Models/                                         Admin, Election, Candidate, Vote, VoterSession
tests/Feature/                                      one file per area; run with ./vendor/bin/pest
```

## Deployment

See `Dockerfile` and `render.yaml` at the repository root. The container runs migrations on boot and serves the app with FrankenPHP; a second process runs the queue worker and scheduler.
