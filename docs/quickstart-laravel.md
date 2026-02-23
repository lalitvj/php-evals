# Quickstart (Laravel)

For a full onboarding flow with use-case examples, start here:

- `docs/first-time-user-guide.md`

Laravel shortest path:
1. Register `PhpEvals\\Laravel\\LaravelEvalsServiceProvider` (if auto-discovery is disabled).
2. Run `php artisan ai:eval:init`.
3. Update `config/ai-evals.php` to use your real model client.
4. Run `php artisan ai:eval sample`.
5. Store and compare runs with `php artisan ai:eval:compare`.

Queue/resume for large suites:
- `php artisan ai:eval:queue`
- `php artisan ai:eval:progress <run-id>`
- `php artisan ai:eval:queue --resume-run-id=<run-id>`
