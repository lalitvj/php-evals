# Scale Evals with Laravel Queues

## The Problem

Running 500+ eval cases synchronously takes 15-25 minutes. If it fails at case 400, you start over, wasting time and API credits on cases that already passed.

## The Solution

The `ai:eval:queue` command splits your suite into chunks and dispatches each chunk as an independent Laravel queue job. You get parallel execution, progress tracking, and resume from failure.

## Prerequisites

- Laravel app with a working queue driver (Redis, SQS, or database)
- php-evals installed: `composer require php-evals/core php-evals/laravel`
- At least one eval suite in `storage/ai-evals/`
- Queue workers running: `php artisan queue:work`

## Step 1: Configure Queue Settings

Update `config/ai-evals.php`:

```php
'queue' => [
    'connection' => env('AI_EVALS_QUEUE_CONNECTION', 'redis'),
    'queue'      => env('AI_EVALS_QUEUE_NAME', 'evals'),
    'chunk_size' => 25,
],
```

| Setting | Purpose | Recommendation |
|---------|---------|----------------|
| `connection` | Laravel queue connection | `redis` for local, `sqs` for production |
| `queue` | Named queue | Dedicated name like `evals` to avoid blocking app jobs |
| `chunk_size` | Cases per job | 25 is a good default |

## Step 2: Dispatch to Queue

```bash
php artisan ai:eval:queue \
    --suite=support-bot \
    --chunk-size=25 \
    --run-id=queued-v1 \
    --connection=redis \
    --queue=evals
```

What happens:

1. Loads `support-bot.jsonl` and counts cases.
2. Splits into chunks of 25.
3. Creates a run record with status `queued`.
4. Dispatches `RunEvalChunkJob` instances to the queue.

Output:

```
Queued run queued-v1
Progress: 0/20 chunks completed
```

## Step 3: Monitor Progress

```bash
php artisan ai:eval:progress queued-v1
```

Mid-run:

```
Run: queued-v1
Status: running
Chunks: 12/20
```

Complete:

```
Run: queued-v1
Status: completed
Chunks: 20/20
```

## Step 4: Resume Failed Runs

If workers crash or you stop mid-run:

```bash
php artisan ai:eval:queue --resume-run-id=queued-v1
```

Only incomplete chunks are re-dispatched. Already-completed results are preserved.

```
Resumed run queued-v1
Progress: 12/20 chunks completed
```

You can resume as many times as needed.

## Step 5: Cost Optimization

### Enable Response Caching

```bash
php artisan ai:eval:queue \
    --suite=support-bot \
    --chunk-size=25 \
    --run-id=cached-v1 \
    --cache-enabled \
    --cache-ttl=3600
```

Or globally in config:

```php
'cache_enabled' => true,
'cache_path'    => storage_path('ai-evals/cache'),
'cache_ttl'     => 3600,
```

### Deterministic Mode

Fixed seed for consistent outputs:

```bash
php artisan ai:eval:queue \
    --suite=support-bot \
    --run-id=det-v1 \
    --deterministic \
    --seed=42
```

### Replay a Previous Run

Skip the model and reuse stored responses:

```bash
php artisan ai:eval \
    --suite=support-bot \
    --replay-run-id=queued-v1 \
    --run-id=replayed-v1
```

Useful when you change assertions but want to test against the same model outputs.

## Queue Configuration Tips

### Choosing Chunk Size

| Suite Size | Chunk Size | Rationale |
|-----------|-----------|-----------|
| < 50 | 50 | Single chunk, no overhead |
| 50-200 | 25 | Good parallelism/overhead balance |
| 200-1000 | 25-50 | Better resume granularity |
| 1000+ | 50-100 | Avoid too many jobs |

### Worker Count

Run multiple workers for parallelism:

```bash
php artisan queue:work redis --queue=evals --tries=2 --timeout=300 &
php artisan queue:work redis --queue=evals --tries=2 --timeout=300 &
php artisan queue:work redis --queue=evals --tries=2 --timeout=300 &
```

Or use Laravel Horizon:

```php
// config/horizon.php
'environments' => [
    'production' => [
        'eval-workers' => [
            'connection' => 'redis',
            'queue' => ['evals'],
            'balance' => 'auto',
            'processes' => 4,
            'tries' => 2,
            'timeout' => 300,
        ],
    ],
],
```

**Timeout guideline:** Set `--timeout` to at least `chunk_size * seconds_per_case * 2`. For 25 cases at 3 seconds each, use 300 seconds minimum.

### Dedicated Queue

Always use a dedicated queue name rather than `default`. This prevents eval jobs from blocking application queue jobs.

## Next Steps

- **CI integration**: See [Tutorial 04: CI/CD Regression](./04-ci-cd-regression.md)
- **Compare queued runs**: Use `php artisan ai:eval:compare baseline queued-v1`
- **Structured output**: See [Tutorial 06: Structured Output](./06-structured-output.md)
