# Auto-Revalidation Guide

Complete guide to automatic background cache revalidation. Learn how it works, configure it, monitor it, and troubleshoot it.

## Overview

Auto-Revalidation automatically refreshes your cache **before** it expires, ensuring users always get fast responses with fresh data.

## Problem It Solves

### Without Auto-Revalidation

```
T=0min   : First request
           → API call: 300ms
           → Save cache (expires at T=60min)

T=30min  : Second request
           → Cache HIT: 5ms 

T=60min  : Cache EXPIRES

T=60min+ : Third request (unlucky timing!)
           → Cache MISS
           → API call: 300ms 
           → User waits 300ms
```

**Problem:** Every cache expiry causes a latency spike.

### With Auto-Revalidation

```
T=0min    : First request
            → API call: 300ms
            → Save cache (expires at T=60min)
            → Schedule renewal at T=55min

T=30min   : Second request
            → Cache HIT: 5ms 

T=55min   : Background job triggers
            → API call: 300ms (outside request!)
            → Cache renewed

T=60min   : Cache would expire, but ALREADY RENEWED

T=60min+  : Third request
            → Cache HIT: 5ms 
            → Data already fresh
            → NO LATENCY SPIKE
```

**Solution:** Zero latency for users, fresh data always!

---

## How It Works

### Step-by-Step Process

#### Step 1: Request Made

```php
$package = Packagist::package('laravel/framework');
// Internal steps:
// 1. Check cache
// 2. If miss: API call
// 3. Save with TTL: 28800 seconds (8 hours)
```

#### Step 2: Job Scheduled

Automatically scheduled in background:

```
revalidate_before_expiry = 300 seconds (5 minutes)
TTL = 28800 seconds (8 hours)

Job execution time = 28800 - 300 = 28500 seconds
                   = 7 hours 55 minutes

So: Job will execute in 7 hours 55 minutes
```

#### Step 3: Background Job Executes

```
T=7h55m : Queue worker picks up job
         → Makes API request to Packagist
         → Gets fresh data
         → Updates cache
         → Resets TTL to 28800 seconds again

T=8h00m : Original TTL would have expired
         → But cache already renewed!
```

#### Step 4: User Request

```
T=8h05m : User makes request
         → Cache HIT: ~5ms
         → Data already fresh from job
```

---

## Configuration

### Step 1: Enable Queue

Auto-revalidation **requires** a Laravel queue configured.

#### Option A: Redis Queue (Recommended)

```bash
# Install Redis
brew install redis

# Start Redis
redis-server
```

Configure Laravel:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

#### Option B: Database Queue

```bash
# Create jobs table
php artisan queue:table
php artisan migrate
```

Configure:

```env
QUEUE_CONNECTION=database
```

#### Option C: Sync Queue (Development Only)

For testing without external services:

```env
QUEUE_CONNECTION=sync
```

️ **Warning:** Jobs execute immediately, not truly in background.

### Step 2: Configure Packagist

```php
// config/packagist.php
'auto_revalidation' => [
    'enabled' => (bool) env('PACKAGIST_AUTO_REVALIDATION', true),
    'revalidate_before_expiry' => (int) env('PACKAGIST_REVALIDATE_BEFORE_EXPIRY', 300),
    'queue' => env('PACKAGIST_QUEUE', 'default'),
],
```

### Step 3: Set Environment Variables

```env
# .env
PACKAGIST_AUTO_REVALIDATION=true
PACKAGIST_REVALIDATE_BEFORE_EXPIRY=300
PACKAGIST_QUEUE=low
```

### Step 4: Start Queue Worker

```bash
# Simple start
php artisan queue:work redis

# Daemon mode (stays running)
php artisan queue:work redis --daemon

# With specific queue
php artisan queue:work redis --queue=low

# Multiple workers for concurrency
php artisan queue:work redis &
php artisan queue:work redis &
php artisan queue:work redis &
```

---

## Configuration Examples

### Scenario 1: Short TTL (Search Results)

```php
// config/packagist.php
'per_action' => [
    'SearchPackagesAction' => [
        'strategy' => RememberCache::class,
        'ttl' => 300,  // 5 minutes
    ],
],

'auto_revalidation' => [
    'enabled' => true,
    'revalidate_before_expiry' => 60,  // 1 minute before
    'queue' => 'low',
],
```

**Timeline:**
```
T=0min    : Search cached, job scheduled at T=4min
T=4min    : Job refreshes in background
T=5min    : Original TTL expires (but already fresh!)
T=4:59min : User request → Cache HIT 
```

### Scenario 2: Medium TTL (Package Details)

```php
// config/packagist.php
'per_action' => [
    'GetPackageAction' => [
        'strategy' => RevalidateCache::class,
        'ttl' => 3600,  // 1 hour
    ],
],

'auto_revalidation' => [
    'enabled' => true,
    'revalidate_before_expiry' => 300,  // 5 minutes before
    'queue' => 'low',
],
```

**Timeline:**
```
T=0min    : Package cached, job scheduled at T=55min
T=55min   : Job refreshes in background
T=1h00m   : Original TTL expires (but already fresh!)
T=1h05m   : User request → Cache HIT 
```

### Scenario 3: Long TTL (Statistics)

```php
// config/packagist.php
'per_action' => [
    'GetStatsAction' => [
        'strategy' => RememberCache::class,
        'ttl' => 86400,  // 1 day
    ],
],

'auto_revalidation' => [
    'enabled' => true,
    'revalidate_before_expiry' => 600,  // 10 minutes before
    'queue' => 'low',
],
```

**Timeline:**
```
T=0min     : Stats cached, job scheduled at T=23h50m
T=23h50m   : Job refreshes in background
T=24h00m   : Original TTL expires (but already fresh!)
T=24h10m   : User request → Cache HIT 
```

---

## Monitoring

### View Queue Jobs

#### Using Redis

```bash
# List all queued jobs
redis-cli KEYS "queues:*"

# View low priority queue
redis-cli LRANGE "queues:low" 0 -1

# Count jobs
redis-cli LLEN "queues:low"

# Clear all jobs
redis-cli FLUSHDB
```

#### Using Database

```sql
-- View pending jobs
SELECT * FROM jobs WHERE queue = 'low';

-- Count pending jobs
SELECT COUNT(*) FROM jobs;

-- View job details
SELECT id, queue, payload, attempts, created_at FROM jobs;
```

### Monitor Worker Status

```bash
# Check if worker is running
ps aux | grep "queue:work"

# Monitor in real-time (macOS)
watch 'ps aux | grep "queue:work"'

# Check system resources
top -p $(pgrep -f "queue:work")
```

### View Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Show failed job details
php artisan queue:show-failed

# Count failures
php artisan queue:count-failed

# Retry specific job
php artisan queue:retry 1

# Retry all
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Logging

Jobs log failures automatically:

```php
// In RevalidateCacheJob
try {
    $data = $client->get($this->endpoint);
    cache()->put($this->cacheKey, $data, ...);
} catch (\Exception $exception) {
    \Log::warning("Packagist cache revalidation failed for {$this->cacheKey}", [
        'exception' => $exception->getMessage(),
    ]);
}
```

View logs:

```bash
# Tail log file
tail -f storage/logs/laravel.log

# Filter Packagist logs
grep "Packagist" storage/logs/laravel.log

# Show last 100 lines
tail -n 100 storage/logs/laravel.log | grep "Packagist"
```

### Performance Metrics

Track auto-revalidation performance:

```php
// Command to analyze revalidation
class AnalyzeRevalidation extends Command
{
    public function handle()
    {
        // Query failed jobs
        $failed = DB::table('failed_jobs')
            ->where('payload', 'like', '%RevalidateCacheJob%')
            ->count();

        echo "Failed revalidations: {$failed}\n";

        // Query processed jobs (if using job history)
        $processed = DB::table('jobs_history')
            ->where('job', 'RevalidateCacheJob')
            ->count();

        echo "Processed revalidations: {$processed}\n";

        // Average execution time
        $avgTime = DB::table('jobs_history')
            ->where('job', 'RevalidateCacheJob')
            ->avg('duration_ms');

        echo "Average execution: {$avgTime}ms\n";
    }
}
```

---

## Optimization Tips

### 1. Use Low Priority Queue

Prevent revalidation jobs from blocking critical operations:

```env
PACKAGIST_QUEUE=low
```

### 2. Optimize revalidate_before_expiry

Match it to your latency tolerance:

```php
// Paranoid - refresh very early
'revalidate_before_expiry' => 600,  // 10 minutes before

// Balanced (default) - good for most cases
'revalidate_before_expiry' => 300,  // 5 minutes before

// Aggressive - refresh just before expiry
'revalidate_before_expiry' => 60,   // 1 minute before
```

### 3. Scale Queue Workers

More workers = faster job processing:

```bash
# Start multiple workers
for i in {1..5}; do
    php artisan queue:work redis --queue=low &
done

# Or use supervisor for process management
# See Laravel docs for supervisor configuration
```

### 4. Use Connection Pooling

```env
# For Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_TIMEOUT=null
REDIS_READ_TIMEOUT=null
```

### 5. Monitor Memory Usage

Revalidation shouldn't consume much memory:

```bash
# Check memory per worker
ps aux -o pid,user,%mem,vsz,rss,cmd | grep "queue:work"

# Restart workers periodically to clear memory leaks
# Use supervisor's 'numprocs' and 'autostart'
```

---

## Troubleshooting

### Issue: Jobs Not Being Processed

**Symptoms:**
- Queue has pending jobs
- Workers aren't processing them
- Cache keeps expiring

**Causes:**
1. Queue worker not running
2. Wrong queue name
3. Redis/database connection issues

**Solutions:**

```bash
# Step 1: Check queue worker
ps aux | grep "queue:work"

# Step 2: Start worker if stopped
php artisan queue:work redis --queue=low --daemon

# Step 3: Check queue configuration
cat config/queue.php

# Step 4: Verify connection
redis-cli PING  # Should return "PONG"

# Step 5: Check Laravel log
tail -f storage/logs/laravel.log
```

### Issue: Cache Still Expiring

**Symptoms:**
- Auto-revalidation enabled but cache expires
- Still seeing slow requests

**Causes:**
1. `revalidate_before_expiry` > `ttl`
2. Jobs failing silently
3. Queue not picking up jobs

**Solutions:**

```php
// Check configuration
if (config('packagist.auto_revalidation.revalidate_before_expiry')
    > config('packagist.use.ttl')) {
    throw new Exception('revalidate_before_expiry must be < ttl');
}

// Check failed jobs
php artisan queue:failed

// View job details
php artisan queue:show-failed  // Shows last 10 failed
```

### Issue: High Memory Usage

**Symptoms:**
- Queue workers consuming lots of RAM
- System slowing down
- Worker crashes

**Causes:**
1. Memory leaks in jobs
2. Too many concurrent workers
3. Large payloads

**Solutions:**

```bash
# Limit concurrent workers
php artisan queue:work redis --queue=low --max-jobs=1000

# Set max memory per process
php artisan queue:work redis --memory=512

# Use supervisor to auto-restart
# (Graceful restart clears memory)

# Monitor memory
watch -n 1 'ps aux | grep queue:work | grep -v grep'
```

### Issue: Jobs Timing Out

**Symptoms:**
- Jobs fail with timeout errors
- Revalidation doesn't complete
- Cache keeps expiring

**Causes:**
1. API is slow
2. Network latency
3. Job timeout too short

**Solutions:**

```env
# Increase timeout
QUEUE_TIMEOUT=300  # 5 minutes (default 60s)

# Or in code
'timeout' => 300,
```

```php
// In RevalidateCacheJob
// Add timeout-safe logic
public function handle()
{
    set_time_limit(300);  // 5 minutes

    try {
        // Long-running operation
        $data = $client->get($this->endpoint);
        cache()->put($this->cacheKey, $data, ...);
    } catch (TimeoutException $e) {
        // Handle timeout gracefully
        Log::warning("Job timeout for {$this->cacheKey}");
    }
}
```

### Issue: Redis Connection Lost

**Symptoms:**
- Redis connection errors in logs
- Jobs not being enqueued
- "Connection refused" errors

**Solutions:**

```bash
# Check Redis is running
redis-cli PING

# Restart Redis
redis-cli shutdown
redis-server

# Check Redis logs
# macOS Homebrew
log stream --predicate 'process == "redis-server"'

# Check Laravel Redis connection
php artisan tinker
Redis::ping()  // Should return "PONG"
```

---

## Performance Impact

### Latency Comparison

```
Scenario: 1000 requests/day, TTL=1h

Without Auto-Revalidation:
┌─────────────────────────────────────┐
│ 23 cache hits  @ 5ms   = 115ms      │
│ 1 cache miss   @ 300ms = 300ms      │
│ Average: 415ms / 24 = 17ms per req  │
└─────────────────────────────────────┘

With Auto-Revalidation:
┌─────────────────────────────────────┐
│ 24 cache hits  @ 5ms   = 120ms      │
│ 0 cache misses                      │
│ Average: 120ms / 24 = 5ms per req   │
│ Savings: 12ms per request = 70%   │
└─────────────────────────────────────┘
```

### Resource Usage

```
CPU Impact:
- Background job: ~10% CPU for ~100ms
- Happens outside request window
- Negligible impact on user experience

Memory Impact:
- Minimal - just job data in queue
- Cleared after execution
- No memory leak risk

API Load:
- Same API calls, just scheduled differently
- Actually REDUCES peak load
- Smoother resource usage over time
```

---

## Best Practices

###  DO

```php
// DO: Use low priority queue
'queue' => 'low',

// DO: Set revalidate_before_expiry < ttl
'ttl' => 3600,
'revalidate_before_expiry' => 300,  // 

// DO: Monitor failed jobs
php artisan queue:failed

// DO: Start queue worker on production
php artisan queue:work --daemon

// DO: Use appropriate timeouts
'timeout' => 300,  // 5 minutes
```

###  DON'T

```php
// DON'T: Set revalidate_before_expiry > ttl
'ttl' => 300,
'revalidate_before_expiry' => 600,  // 

// DON'T: Disable auto-revalidation without queue alternative
'enabled' => false,  // Then use RememberCache instead

// DON'T: Ignore failed jobs
// Check logs regularly

// DON'T: Run single queue worker
# Bad - single point of failure
php artisan queue:work redis

# Good - multiple workers
php artisan queue:work redis &
php artisan queue:work redis &

// DON'T: Set job timeout too short
'timeout' => 10,  //  Too short
'timeout' => 300, //  Reasonable
```

---

## Next Steps

- **[API Usage](./05-api-usage.md)** - Use the API
- **[Testing](./07-testing.md)** - Test your setup
- **[Advanced Topics](./09-advanced.md)** - Custom implementations

---

**Remember:** Auto-revalidation is optional but highly recommended for production. It's a game-changer for performance!
