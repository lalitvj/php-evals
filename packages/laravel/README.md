# php-evals/laravel

Laravel-first bridge for `php-evals`.

Includes:
- `LaravelEvalsServiceProvider`
- config file: `ai-evals.php`
- command: `php artisan ai:eval`
- `ai:eval:compare`, `ai:eval:queue`, and `ai:eval:progress`
- built-in Prism and Laravel AI model clients

Use this package when your application already talks to models through Prism or Laravel AI and you want regression checks, stored runs, and CI gates around that behavior.

See:
- `docs/quickstart-laravel.md`
- `docs/installation.md`
