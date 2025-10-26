# Troubleshooting Guide

Solutions for common issues and problems when using Laravel Packagist.

## Installation Issues

### Issue: Composer Installation Fails

**Error:**
```
Your requirements could not be resolved to an installable set of packages.
```

**Causes:**
1. Incompatible PHP version
2. Conflicting dependencies
3. Composer cache corruption

**Solutions:**

```bash
# Step 1: Check PHP version (need 8.4+)
php -v

# Step 2: Clear Composer cache
composer clear-cache

# Step 3: Update Composer itself
composer self-update

# Step 4: Try installing again
composer require akira/laravel-packagist

# Step 5: If still fails, check conflicts
composer why-not akira/laravel-packagist:1.0
```

### Issue: Service Provider Not Registered

**Error:**
```
Class 'Akira\Packagist\Facades\Packagist' not found
```

**Causes:**
1. Package not properly installed
2. Laravel auto-discovery not working
3. Service provider not registered

**Solutions:**

```bash
# Step 1: Verify package is installed
composer show akira/laravel-packagist

# Step 2: Regenerate autoloader
composer dump-autoload

# Step 3: Clear Laravel cache
php artisan cache:clear
php artisan config:clear

# Step 4: If still not found, manually register provider
# In config/app.php
'providers' => [
    // ...
    Akira\Packagist\Providers\PackagistServiceProvider::class,
],
```

### Issue: Configuration File Missing

**Error:**
```
Error in config/packagist.php: file does not exist
```

**Causes:**
1. Vendor publish not executed
2. Publish command failed
3. File deleted

**Solutions:**

```bash
# Step 1: Publish configuration
php artisan vendor:publish --provider="Akira\Packagist\Providers\PackagistServiceProvider"

# Step 2: Force republish (overwrite if exists)
php artisan vendor:publish --provider="Akira\Packagist\Providers\PackagistServiceProvider" --force

# Step 3: Verify file was created
test -f config/packagist.php && echo " Config exists" || echo " Missing"

# Step 4: Check file permissions
ls -la config/packagist.php
```

---

## API Connection Issues

### Issue: Connection Refused to Packagist

**Error:**
```
GuzzleException: cURL error 7: Failed to connect to repo.packagist.org port 443
```

**Causes:**
1. No internet connection
2. Firewall blocking Packagist
3. DNS resolution failure
4. Packagist server down

**Solutions:**

```bash
# Step 1: Check internet connectivity
ping 8.8.8.8

# Step 2: Test DNS resolution
nslookup repo.packagist.org
dig repo.packagist.org

# Step 3: Test direct connection
curl -v https://repo.packagist.org/packages.json

# Step 4: Check if Packagist is online
# Visit https://status.packagist.org in browser

# Step 5: Check firewall/proxy
# If behind corporate firewall, configure proxy:
composer config https-proxy https://proxy.example.com:8080

# Step 6: Disable SSL verification (last resort, not recommended)
curl -k https://repo.packagist.org/packages.json
```

### Issue: Timeout Errors

**Error:**
```
cURL error 28: Operation timed out after 30 seconds
```

**Causes:**
1. Slow network connection
2. Packagist server slow
3. Timeout too short

**Solutions:**

```php
// In config/packagist.php or code
// Increase HTTP timeout
config(['http.timeout' => 60]);

// Or in PackagistClient
$client = new PackagistClient(
    new GuzzleClient([
        'timeout' => 60,
    ])
);
```

### Issue: SSL Certificate Errors

**Error:**
```
cURL error 60: SSL certificate problem
```

**Causes:**
1. Outdated CA certificates
2. System certificate store not configured
3. Proxy certificate issues

**Solutions:**

```bash
# Step 1: Update CA bundle
# macOS
brew install cacert

# Ubuntu/Debian
sudo apt-get install ca-certificates

# Step 2: Configure PHP CA bundle
# In php.ini
openssl.cafile=/etc/ssl/certs/ca-bundle.crt

# Step 3: Verify curl CA
curl -v https://repo.packagist.org/packages.json | grep "CAfile"
```

---

## Cache Issues

### Issue: Cache Not Being Used

**Symptoms:**
- Always making API calls
- No performance improvement
- Cache directory/Redis shows nothing

**Causes:**
1. Caching disabled
2. Cache driver not working
3. Cache evicted/cleared

**Solutions:**

```bash
# Step 1: Verify caching is enabled
php artisan tinker
config('packagist.use.enabled')  # Should be true

# Step 2: Check cache configuration
php artisan tinker
app('cache')->ping()  # Should return true

# Step 3: Test cache manually
Cache::put('test', 'value', 60);
Cache::get('test')  # Should return 'value'

# Step 4: Check cache directory permissions (file cache)
ls -la storage/framework/cache/

# Step 5: Check Redis connection
redis-cli PING  # Should return "PONG"
```

### Issue: Cache Hits Not Happening

**Symptoms:**
- Every request goes to API
- Cache shows data but not used
- Performance not improving

**Causes:**
1. Cache keys not matching
2. TTL expired immediately
3. Cache driver issue
4. Wrong caching strategy selected

**Solutions:**

```bash
# Step 1: Verify cache keys
php artisan tinker
Cache::tags('packagist')->keys()  # Should show cached items

# Step 2: Check TTL values
config('packagist.use.ttl')  # Should be > 0

# Step 3: Test caching manually
$start = microtime(true);
Packagist::package('laravel/framework');
echo (microtime(true) - $start) . "ms\n";  // ~300ms first time

$start = microtime(true);
Packagist::package('laravel/framework');
echo (microtime(true) - $start) . "ms\n";  // ~5ms second time

# Step 4: Check cache strategy
config('packagist.use.strategy')  # Should be RevalidateCache, RememberCache, or ForeverCache
```

### Issue: Cache Growing Too Large

**Symptoms:**
- Storage/cache directory large
- Redis using lots of memory
- Database growing

**Causes:**
1. No cache cleanup
2. TTL too long
3. Too many cache keys
4. Forever cache not being cleared

**Solutions:**

```bash
# Step 1: Clear all caches
php artisan cache:clear

# Step 2: Clear packagist tags only
php artisan tinker
cache()->tags('packagist')->flush()

# Step 3: Check cache size
# For Redis
redis-cli INFO memory

# For file cache
du -sh storage/framework/cache/

# Step 4: Add cache cleanup command
# In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Clear old cache daily
    $schedule->command('cache:prune-stale-tags')
        ->daily();

    // Clear packagist cache weekly
    $schedule->call(function () {
        cache()->tags('packagist')->flush();
    })->weekly();
}
```

---

## Queue Issues

### Issue: Queue Worker Not Starting

**Error:**
```
ProcessException: Process exited with code 1
```

**Causes:**
1. Configuration invalid
2. Redis/database connection failed
3. Permission issues
4. Port conflicts

**Solutions:**

```bash
# Step 1: Check queue configuration
cat config/queue.php

# Step 2: Test queue connection
php artisan tinker
Queue::push('TestJob')  # Should not throw error

# Step 3: Start queue with verbose output
php artisan queue:work redis -vvv

# Step 4: Check system resources
top  # CPU and memory
df   # Disk space
netstat -an | grep LISTEN  # Check ports

# Step 5: Check permissions
ls -la artisan
whoami  # Current user
id  # User ID and groups

# Step 6: Check logs
tail -f storage/logs/laravel.log
```

### Issue: Jobs Not Processing

**Symptoms:**
- Jobs in queue but not executing
- Worker running but no progress
- Old jobs still pending

**Causes:**
1. Worker paused/crashed
2. Queue connection failed
3. Job exceptions silently failing
4. Max attempts exceeded

**Solutions:**

```bash
# Step 1: Check if worker is running
ps aux | grep "queue:work"

# Step 2: Restart worker
php artisan queue:restart

# Step 3: Start worker in debug mode
php artisan queue:work redis --verbose

# Step 4: Check failed jobs
php artisan queue:failed

# Step 5: Retry failed jobs
php artisan queue:retry all

# Step 6: Check queue length
php artisan tinker
Queue::size('low')  # Number of jobs in queue

# Step 7: Clear stuck jobs
php artisan queue:clear
```

### Issue: Memory Leak in Queue Worker

**Symptoms:**
- Worker memory usage grows
- Eventual crash/OOM
- System becomes sluggish

**Causes:**
1. Memory leak in job code
2. Large payload in jobs
3. Not enough memory limit
4. Supervisor not restarting

**Solutions:**

```bash
# Step 1: Monitor memory usage
watch -n 1 'ps aux | grep queue:work | grep -v grep'

# Step 2: Limit memory per process
php artisan queue:work redis --memory=512

# Step 3: Increase PHP memory limit
php -d memory_limit=1G artisan queue:work redis

# Step 4: Use supervisor to auto-restart
# /etc/supervisor/conf.d/packagist-queue.conf
[program:packagist-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=low
autostart=true
autorestart=true
numprocs=1
startsecs=0
stopwaitsecs=3600
max-memory=512M

# Step 5: Check for memory leaks in code
php artisan tinker
# Test repeated calls
for ($i = 0; $i < 1000; $i++) {
    Packagist::package('laravel/framework');
}
# Check memory usage
```

---

## Auto-Revalidation Issues

### Issue: Revalidation Jobs Not Dispatching

**Symptoms:**
- Config says auto-revalidation enabled
- No jobs in queue
- Cache still expires

**Causes:**
1. Auto-revalidation disabled in config
2. Queue not configured
3. `dispatch()` function not available
4. Cache strategy doesn't support revalidation

**Solutions:**

```php
// Step 1: Check configuration
config('packagist.auto_revalidation.enabled')  // Should be true

// Step 2: Verify queue is configured
config('queue.default')  // Should not be null

// Step 3: Check if dispatch function exists
function_exists('dispatch')  // Should be true

// Step 4: Verify cache strategy
config('packagist.use.strategy')  // Should be RevalidateCache

// Step 5: Enable debug logging
// In RevalidateCacheJob
if ($this->autoRevalidationEnabled) {
    Log::info("Dispatching revalidation for {$cacheKey}");
    dispatch(new RevalidateCacheJob(...));
}
```

### Issue: Revalidation Jobs Failing

**Symptoms:**
- Failed jobs queue filling up
- Revalidation not happening
- Cache expiring

**Causes:**
1. API error during revalidation
2. Job timeout too short
3. Payload too large
4. Invalid data format

**Solutions:**

```bash
# Step 1: Check failed jobs
php artisan queue:failed

# Step 2: View failed job details
php artisan queue:show-failed

# Step 3: Check logs for errors
grep "Packagist cache revalidation" storage/logs/laravel.log

# Step 4: Increase job timeout
# In config/queue.php
'connections' => [
    'redis' => [
        // ...
        'timeout' => 300,  // 5 minutes
    ]
]

# Step 5: Retry failed jobs
php artisan queue:retry all
```

---

## Performance Issues

### Issue: API Responses Slow

**Symptoms:**
- Requests taking 300-500ms
- No improvement with caching
- Packagist API seems slow

**Causes:**
1. Network latency
2. Packagist server slow
3. Large responses
4. No caching enabled

**Solutions:**

```bash
# Step 1: Measure baseline
curl -o /dev/null -s -w "%{time_total}s\n" https://repo.packagist.org/packages.json

# Step 2: Check your network
ping repo.packagist.org
traceroute repo.packagist.org

# Step 3: Enable caching
PACKAGIST_CACHE_ENABLED=true

# Step 4: Use auto-revalidation
PACKAGIST_AUTO_REVALIDATION=true

# Step 5: Monitor response times
# Add timing to your app
$start = microtime(true);
$package = Packagist::package('laravel/framework');
Log::info('Packagist call took ' . (microtime(true) - $start) . 's');
```

### Issue: High CPU Usage

**Symptoms:**
- High CPU when running queue
- 100% CPU usage
- System slow

**Causes:**
1. Too many queue workers
2. Tight polling loop
3. CPU-intensive operation in job
4. Infinite loop

**Solutions:**

```bash
# Step 1: Monitor CPU
top -p $(pgrep -f "queue:work")

# Step 2: Reduce worker count
pkill -f "queue:work"
php artisan queue:work redis &  # Single worker

# Step 3: Increase sleep between checks
php artisan queue:work redis --sleep=3  # Check every 3 seconds

# Step 4: Check job code for infinite loops
# Review RevalidateCacheJob and client code

# Step 5: Use supervisor for better management
# Limits CPU with cpuset and nice priority
```

---

## Validation Issues

### Issue: Package Name Validation Failing

**Error:**
```
InvalidArgumentException: Invalid package name
```

**Causes:**
1. Wrong format (missing vendor/)
2. Invalid characters
3. Leading/trailing spaces

**Solutions:**

```php
// Step 1: Check format
// Must be: vendor/package
Packagist::package('laravel/framework');     //  Correct
Packagist::package('framework');             //  Missing vendor
Packagist::package('Laravel/Framework');     //  Uppercase

// Step 2: Validate before calling
PackageValidator::validate('laravel/framework');  // Returns boolean

// Step 3: Use validateOrFail for debugging
try {
    PackageValidator::validateOrFail('invalid');
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
}

// Step 4: Check for common mistakes
$package = 'laravel/framework';
$package = trim($package);  // Remove spaces
$package = strtolower($package);  // Lowercase
```

### Issue: Search Query Too Long

**Error:**
```
InvalidArgumentException: Query is too long (max 1000 characters)
```

**Solutions:**

```php
// Step 1: Check query length
$query = 'long search string';
if (strlen($query) > 1000) {
    $query = substr($query, 0, 1000);
}

// Step 2: Validate before searching
SearchValidator::validate($query);

// Step 3: Simplify query
// Instead of: 'laravel web framework for building applications'
// Use: 'laravel framework'

// Step 4: Use filters for complex searches
Packagist::search('laravel', [
    'type' => 'library',
    // More specific filters
]);
```

---

## Debugging Techniques

### Enable Detailed Logging

```php
// In config/logging.php
'channels' => [
    'packagist' => [
        'driver' => 'single',
        'path' => storage_path('logs/packagist.log'),
        'level' => 'debug',
    ],
],

// Use in code
Log::channel('packagist')->debug('Message', ['data' => $data]);
```

### Use Tinker for Quick Tests

```bash
php artisan tinker

# Test basic functionality
Packagist::package('laravel/framework')

# Check configuration
config('packagist')

# Test cache
Cache::put('test', 'value', 60)
Cache::get('test')

# Test queue
Queue::size('low')
```

### Monitor in Real-Time

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep -i packagist

# Watch queue status
watch -n 1 'php artisan queue:size'

# Watch cache
watch -n 1 'redis-cli KEYS "packagist:*" | wc -l'
```

---

## Getting Help

### Provide Debugging Information

When reporting issues, include:

```bash
# PHP version
php -v

# Laravel version
php artisan --version

# Composer packages
composer show akira/laravel-packagist

# Configuration (sanitized)
cat config/packagist.php

# Recent logs
tail -n 100 storage/logs/laravel.log

# Environment info
php -i | grep -E "cache|memory|timeout"

# Queue status
php artisan queue:failed
```

### Common Solutions Checklist

- [ ] Restarted queue worker
- [ ] Cleared all caches
- [ ] Checked configuration
- [ ] Verified network connectivity
- [ ] Checked logs for errors
- [ ] Updated package
- [ ] Ran tests
- [ ] Checked PHP/Laravel versions

---

## Next Steps

- **[Advanced Guide](./09-advanced.md)** - Custom implementations
- **[Testing Guide](./07-testing.md)** - Writing tests
- **[API Usage](./05-api-usage.md)** - Reference

---

**Need more help?** Check the [Getting Started Guide](./01-getting-started.md) or review [API Usage](./05-api-usage.md) for examples.
