# Testing Guide

Complete guide to testing Laravel Packagist. Learn how to write tests, mock the API, test caching, and verify auto-revalidation.

## Overview

Laravel Packagist is designed to be testable. This guide covers:

- Writing unit tests
- Testing cache behavior
- Mocking API responses
- Testing validators
- Testing DTOs
- Integration testing

## Testing Philosophy

Laravel Packagist tests follow these principles:

1. **Independent** - Tests don't depend on external services
2. **Fast** - Tests run without network calls
3. **Deterministic** - Same result every time
4. **Clear** - Easy to understand what's being tested

## Running Tests

### Basic Test Execution

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Unit/Actions/GetPackageActionTest.php

# Run tests matching pattern
./vendor/bin/pest --filter="SearchPackages"

# Run with verbose output
./vendor/bin/pest -v

# Run with code coverage
./vendor/bin/pest --coverage
```

### Test Organization

```
tests/
├── Unit/
│   ├── Actions/
│   ├── Cache/
│   ├── Client/
│   ├── DTOs/
│   ├── Jobs/
│   ├── Support/
│   └── Validators/
└── Feature/
    └── PackagistManagerTest.php
```

---

## Unit Testing

### Testing Validators

Validators ensure input is correct before processing.

#### Example: Package Name Validator

```php
// tests/Unit/Validators/PackageValidatorTest.php

use Akira\Packagist\Validators\PackageValidator;

test('validates correct package names', function () {
    expect(PackageValidator::validate('laravel/framework'))->toBeTrue();
    expect(PackageValidator::validate('symfony/console'))->toBeTrue();
    expect(PackageValidator::validate('vendor/name'))->toBeTrue();
});

test('rejects invalid package names', function () {
    expect(PackageValidator::validate('invalid'))->toBeFalse();
    expect(PackageValidator::validate('/framework'))->toBeFalse();
    expect(PackageValidator::validate('laravel/'))->toBeFalse();
});

test('throws on invalid name with validateOrFail', function () {
    PackageValidator::validateOrFail('invalid');
})->throws(\InvalidArgumentException::class);
```

#### Example: Search Query Validator

```php
// tests/Unit/Validators/SearchValidatorTest.php

use Akira\Packagist\Validators\SearchValidator;

test('validates non-empty queries', function () {
    expect(SearchValidator::validate('laravel'))->toBeTrue();
    expect(SearchValidator::validate('symfony console'))->toBeTrue();
});

test('rejects empty queries', function () {
    expect(SearchValidator::validate(''))->toBeFalse();
    expect(SearchValidator::validate('   '))->toBeFalse();
});

test('rejects overly long queries', function () {
    $longQuery = str_repeat('a', 1001);
    expect(SearchValidator::validate($longQuery))->toBeFalse();
});

test('throws on validation failure', function () {
    SearchValidator::validateOrFail('');
})->throws(\InvalidArgumentException::class);
```

### Testing DTOs

DTOs are data containers that should handle type-safe conversions.

#### Example: PackageDTO

```php
// tests/Unit/DTOs/PackageDTOTest.php

use Akira\Packagist\DTOs\PackageDTO;

test('creates package DTO from array', function () {
    $data = [
        'name' => 'laravel/framework',
        'description' => 'The Laravel Framework.',
        'repository' => 'https://github.com/laravel/framework.git',
        'homepage' => 'https://laravel.com',
        'license' => 'MIT',
        'downloads' => 9999999,
        'favers' => 50000,
        'versions' => [],
        'maintainers' => [],
    ];

    $dto = PackageDTO::fromArray($data);

    expect($dto->name)->toBe('laravel/framework');
    expect($dto->description)->toBeString();
    expect($dto->downloads)->toBeInt();
    expect($dto->downloads)->toBe(9999999);
});

test('handles missing optional properties', function () {
    $data = [
        'name' => 'vendor/package',
        'versions' => [],
        'maintainers' => [],
    ];

    $dto = PackageDTO::fromArray($data);

    expect($dto->name)->toBe('vendor/package');
    expect($dto->description)->toBeString(); // Default value
});

test('converts DTO back to array', function () {
    $data = [
        'name' => 'laravel/framework',
        'description' => 'Test',
        'repository' => 'https://...',
        'homepage' => 'https://...',
        'license' => 'MIT',
        'downloads' => 100,
        'favers' => 50,
        'versions' => [],
        'maintainers' => [],
    ];

    $dto = PackageDTO::fromArray($data);
    $array = $dto->toArray();

    expect($array['name'])->toBe('laravel/framework');
    expect($array)->toHaveKey('description');
});
```

### Testing Cache

Test that caching works correctly.

#### Example: Cache Strategy Tests

```php
// tests/Unit/Cache/RemembrCacheTest.php

use Akira\Packagist\Cache\RememberCache;

test('remember cache saves and retrieves data', function () {
    $cache = new RememberCache();
    $called = false;

    $result = $cache->get('test-key', function () use (&$called) {
        $called = true;
        return 'test-value';
    }, 3600);

    expect($result)->toBe('test-value');
    expect($called)->toBeTrue();

    // Second call should use cache
    $called = false;
    $result = $cache->get('test-key', function () use (&$called) {
        $called = true;
        return 'new-value';
    }, 3600);

    expect($result)->toBe('test-value');  // Original cached value
    expect($called)->toBeFalse();          // Callback not called
});

test('forget clears cache', function () {
    $cache = new RememberCache();

    // Cache value
    $cache->get('test-key', function () {
        return 'test-value';
    }, 3600);

    // Forget cache
    $cache->forget('test-key');

    // Next access should call callback
    $called = false;
    $result = $cache->get('test-key', function () use (&$called) {
        $called = true;
        return 'new-value';
    }, 3600);

    expect($called)->toBeTrue();
    expect($result)->toBe('new-value');
});
```

#### Example: Auto-Revalidation Tests

```php
// tests/Unit/Cache/AutoRevalidationTest.php

use Akira\Packagist\Cache\AutoRevalidationTrait;

test('trait exists', function () {
    expect(trait_exists(AutoRevalidationTrait::class))->toBeTrue();
});

test('can enable auto revalidation', function () {
    $stub = new class {
        use AutoRevalidationTrait;
    };

    $result = $stub->enableAutoRevalidation(300, 'default');

    expect($result)->toBe($stub);
    expect($stub->autoRevalidationEnabled)->toBeTrue();
});

test('can disable auto revalidation', function () {
    $stub = new class {
        use AutoRevalidationTrait;
        public bool $autoRevalidationEnabled = true;
    };

    $stub->disableAutoRevalidation();

    expect($stub->autoRevalidationEnabled)->toBeFalse();
});
```

---

## Integration Testing

### Testing Actions with Mock Client

Test actions by providing mock responses.

#### Example: GetPackageAction

```php
// tests/Feature/GetPackageActionTest.php

use Akira\Packagist\Actions\GetPackageAction;
use Akira\Packagist\Cache\NoneCache;
use Akira\Packagist\Contracts\ClientContract;

class MockClient implements ClientContract
{
    public function get(string $endpoint): mixed
    {
        // Simulate API response
        return [
            'package' => [
                'name' => 'laravel/framework',
                'description' => 'The Laravel Framework.',
                'repository' => 'https://github.com/laravel/framework.git',
                'homepage' => 'https://laravel.com',
                'license' => 'MIT',
                'downloads' => 9999999,
                'favers' => 50000,
                'versions' => [],
                'maintainers' => [],
            ]
        ];
    }

    public function search(string $query, array $filters = []): mixed
    {
        return [];
    }
}

test('get package action returns package DTO', function () {
    $client = new MockClient();
    $cache = new NoneCache();

    $action = new GetPackageAction($client, $cache);
    $package = $action->handle('laravel/framework');

    expect($package->name)->toBe('laravel/framework');
    expect($package->description)->toContain('Laravel');
    expect($package->downloads)->toBe(9999999);
});

test('get package action validates package name', function () {
    $client = new MockClient();
    $cache = new NoneCache();

    $action = new GetPackageAction($client, $cache);
    $action->handle('invalid-no-vendor-slash');
})->throws(\InvalidArgumentException::class);
```

#### Example: SearchPackagesAction

```php
// tests/Feature/SearchPackagesActionTest.php

use Akira\Packagist\Actions\SearchPackagesAction;
use Akira\Packagist\Cache\NoneCache;
use Akira\Packagist\Contracts\ClientContract;

class SearchMockClient implements ClientContract
{
    public function get(string $endpoint): mixed
    {
        return [];
    }

    public function search(string $query, array $filters = []): mixed
    {
        // Simulate search response
        return [
            'results' => [
                [
                    'name' => 'laravel/framework',
                    'description' => 'The Laravel Framework.',
                    'downloads' => 9999999,
                    'favers' => 50000,
                    'repository' => 'https://github.com/laravel/framework',
                ],
            ],
        ];
    }
}

test('search packages action returns results', function () {
    $client = new SearchMockClient();
    $cache = new NoneCache();

    $action = new SearchPackagesAction($client, $cache);
    $results = $action->handle('laravel');

    expect($results)->toBeArray();
    expect($results)->not->toBeEmpty();
});

test('search packages action validates query', function () {
    $client = new SearchMockClient();
    $cache = new NoneCache();

    $action = new SearchPackagesAction($client, $cache);
    $action->handle('');
})->throws(\InvalidArgumentException::class);
```

---

## Testing Cache Behavior

### Testing TTL

Verify cache respects TTL settings.

```php
// tests/Unit/Cache/TTLTest.php

use Akira\Packagist\Cache\RememberCache;

test('cache respects TTL', function () {
    $cache = new RememberCache();

    // Store with 1 second TTL
    $cache->get('key', function () {
        return 'value';
    }, 1);

    // Immediately retrieve - should be cached
    $called = false;
    $result = $cache->get('key', function () use (&$called) {
        $called = true;
        return 'new';
    }, 1);

    expect($called)->toBeFalse();
    expect($result)->toBe('value');

    // Wait for expiry
    sleep(2);

    // Should call callback now (cache expired)
    $called = false;
    $result = $cache->get('key', function () use (&$called) {
        $called = true;
        return 'new';
    }, 1);

    expect($called)->toBeTrue();
    expect($result)->toBe('new');
});
```

### Testing Tag-Based Clearing

```php
// tests/Unit/Cache/TagsTest.php

use Akira\Packagist\Cache\RememberCache;
use Illuminate\Support\Facades\Cache;

test('cache tags allow bulk clearing', function () {
    // Note: This requires a cache driver that supports tags
    // Configure test cache to use appropriate driver
    config(['cache.default' => 'redis']);

    $cache = new RememberCache();

    // Store multiple entries with tags
    Cache::tags('packagist')->put('key1', 'value1', 3600);
    Cache::tags('packagist')->put('key2', 'value2', 3600);

    // Verify stored
    expect(Cache::tags('packagist')->get('key1'))->toBe('value1');

    // Clear all tagged entries
    Cache::tags('packagist')->flush();

    // Verify cleared
    expect(Cache::tags('packagist')->get('key1'))->toBeNull();
});
```

---

## Testing Auto-Revalidation

### Testing Job Dispatch

Verify revalidation jobs are scheduled.

```php
// tests/Feature/AutoRevalidationDispatchTest.php

use Akira\Packagist\Cache\RevalidateCache;
use Akira\Packagist\Jobs\RevalidateCacheJob;
use Illuminate\Support\Facades\Bus;

test('revalidate cache dispatches job when auto-revalidation enabled', function () {
    Bus::fake();  // Prevent actual job dispatch

    $cache = new RevalidateCache();
    $cache->enableAutoRevalidation(300, 'default');

    // Execute get with callback
    $cache->get('test-key', function () {
        return 'test-value';
    }, 3600, 'TestAction');

    // Verify job was dispatched
    Bus::assertDispatched(RevalidateCacheJob::class);
});

test('revalidate cache does not dispatch job when disabled', function () {
    Bus::fake();

    $cache = new RevalidateCache();
    $cache->disableAutoRevalidation();

    $cache->get('test-key', function () {
        return 'test-value';
    }, 3600);

    // No job should be dispatched
    Bus::assertNotDispatched(RevalidateCacheJob::class);
});
```

### Testing Job Execution

```php
// tests/Feature/RevalidateCacheJobTest.php

use Akira\Packagist\Jobs\RevalidateCacheJob;
use Akira\Packagist\Contracts\ClientContract;

class TestClient implements ClientContract
{
    public function get(string $endpoint): mixed
    {
        return ['data' => 'fresh'];
    }

    public function search(string $query, array $filters = []): mixed
    {
        return [];
    }
}

test('revalidate cache job updates cache', function () {
    $client = new TestClient();
    $job = new RevalidateCacheJob('test-key', 'packages.json', ['ttl' => 3600]);

    $job->handle($client);

    // Verify cache was updated
    $cached = cache()->get('test-key');
    expect($cached)->toBe(['data' => 'fresh']);
});

test('revalidate cache job handles exceptions gracefully', function () {
    class FailingClient implements ClientContract
    {
        public function get(string $endpoint): mixed
        {
            throw new \Exception('API Error');
        }

        public function search(string $query, array $filters = []): mixed
        {
            return [];
        }
    }

    $client = new FailingClient();
    $job = new RevalidateCacheJob('test-key', 'packages.json', ['ttl' => 3600]);

    // Should not throw, just log
    $job->handle($client);  // No exception expected
});
```

---

## Testing Best Practices

###  DO

```php
// DO: Use descriptive test names
test('get package action returns package DTO with correct properties', function () {
    // ...
});

// DO: Test both success and failure
test('validates correct input', function () { /* ... */ });
test('rejects invalid input', function () { /* ... */ });

// DO: Test edge cases
test('handles empty array', function () { /* ... */ });
test('handles null values', function () { /* ... */ });

// DO: Use appropriate cache for tests
$cache = new NoneCache();  // No network calls in tests

// DO: Mock external dependencies
class MockClient implements ClientContract { /* ... */ }

// DO: Verify side effects
Bus::assertDispatched(RevalidateCacheJob::class);
```

###  DON'T

```php
// DON'T: Make real API calls in tests
// Don't do this:
$package = Packagist::package('laravel/framework');

// DO: Mock the client instead
$mockClient = new MockClient();

// DON'T: Test with actual database
// Configure test env to use sqlite :memory:

// DON'T: Ignore test failures
// Always fix failing tests immediately

// DON'T: Write tests that pass sometimes
// Tests must be deterministic

// DON'T: Test implementation details
// Test behavior, not internals
```

---

## Debugging Tests

### Verbose Output

```bash
# Show detailed test output
./vendor/bin/pest -v

# Show only failures
./vendor/bin/pest --fail-on-empty

# Stop on first failure
./vendor/bin/pest --stop-on-failure
```

### Using Dump

```php
test('debug test', function () {
    $value = expensive_operation();

    dd($value);  // Dump and die
    dump($value); // Just dump
    ray($value);  // Ray.so for remote debugging
});
```

### Profiling

```bash
# Show slowest tests
./vendor/bin/pest --profile

# Show coverage
./vendor/bin/pest --coverage

# HTML coverage report
./vendor/bin/pest --coverage --coverage-html=coverage
```

---

## Continuous Integration

### GitHub Actions Example

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4

      - name: Install dependencies
        run: composer install --prefer-dist

      - name: Run tests
        run: ./vendor/bin/pest

      - name: Upload coverage
        run: ./vendor/bin/pest --coverage
```

---

## Next Steps

- **[Troubleshooting](./08-troubleshooting.md)** - Common issues
- **[Advanced Topics](./09-advanced.md)** - Custom implementations

---

**Tip:** Test often! Tests catch bugs early and make refactoring safe.
