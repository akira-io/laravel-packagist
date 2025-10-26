# API Usage Guide

Complete reference for using the Laravel Packagist API with detailed examples for every action.

## Overview

Laravel Packagist provides 4 main actions to interact with Packagist.org:

| Action | Method | Returns | Cache | Example |
|--------|--------|---------|-------|---------|
| `GetPackageAction` | `package()` | `PackageDTO` | Forever | Get package details |
| `SearchPackagesAction` | `search()` | `array` | 5 min | Search packages |
| `GetStatsAction` | `stats()` | `array` | 1 hour | Get stats |
| `GetMaintainersAction` | `maintainers()` | `array` | Forever | Get maintainers |

---

## 1. GetPackageAction - Fetch Package Details

Retrieve complete information about a specific package.

### Method Signature

```php
public function package(string $package): PackageDTO
```

### Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `$package` | string | Vendor/package name | `laravel/framework` |

### Returns

`PackageDTO` object with properties:

```php
class PackageDTO {
    public readonly string $name;           // Vendor/name
    public readonly string $description;    // Package description
    public readonly string $repository;     // Git repository URL
    public readonly array $versions;        // array<string, VersionDTO>
    public readonly array $maintainers;     // array<int, MaintainerDTO>
    public readonly string $homepage;       // Website URL
    public readonly string $license;        // License (MIT, etc.)
    public readonly int $downloads;         // Total downloads
    public readonly int $favers;            // Total favorites
}
```

### Basic Example

```php
use Akira\Packagist\Facades\Packagist;

// Get package
$package = Packagist::package('laravel/framework');

// Access properties
echo $package->name;           // "laravel/framework"
echo $package->description;    // "The Laravel Framework."
echo $package->downloads;      // 9999999 (int)
echo $package->favers;         // 50000 (int)
echo $package->homepage;       // "https://laravel.com"
echo $package->license;        // "MIT"
echo $package->repository;     // "https://github.com/laravel/framework.git"
```

### Working with Versions

Each version is a `VersionDTO` object:

```php
$package = Packagist::package('laravel/framework');

// Access all versions
foreach ($package->versions as $versionName => $version) {
    echo "{$versionName}: {$version->description}";
}

// Access specific version
$latestVersion = $package->versions['11.0.0'] ?? null;

// Check version properties
if ($latestVersion) {
    echo $latestVersion->version;       // "11.0.0"
    echo $latestVersion->license;       // "MIT"
    echo $latestVersion->description;   // Version description
    echo count($latestVersion->require); // Dependencies count
}
```

### Working with Maintainers

Each maintainer is a `MaintainerDTO` object:

```php
$package = Packagist::package('laravel/framework');

// List all maintainers
foreach ($package->maintainers as $maintainer) {
    echo $maintainer->name;      // "Taylor Otwell"
    echo $maintainer->email;     // "taylor@laravel.com"
    echo $maintainer->homepage;  // "https://taylorotwell.com"
}

// Find specific maintainer
$taylor = collect($package->maintainers)
    ->firstWhere('name', 'Taylor Otwell');

if ($taylor) {
    echo "Email: {$taylor->email}";
}
```

### Error Handling

Package name validation happens automatically:

```php
//  Invalid package name
$package = Packagist::package('invalid-no-vendor-slash');
// Throws: InvalidArgumentException

//  Valid format
$package = Packagist::package('vendor/package');

// Common valid formats
Packagist::package('laravel/framework');
Packagist::package('symfony/console');
Packagist::package('monolog/monolog');
Packagist::package('twig/twig');
```

### Caching Behavior

By default, uses `ForeverCache`:

```php
// First call: API request
$package = Packagist::package('laravel/framework');  // ~300ms

// Second call: Cache hit
$package = Packagist::package('laravel/framework');  // ~5ms

// Same data, no API call
```

To disable caching:

```php
// In config/packagist.php
'per_action' => [
    'GetPackageAction' => [
        'strategy' => NoneCache::class,  // Always fetch fresh
    ],
]
```

### Real-World Examples

#### Example 1: Display Package Info

```php
// Controller
public function show($vendor, $package)
{
    $pkg = Packagist::package("{$vendor}/{$package}");

    return view('package.show', [
        'name' => $pkg->name,
        'description' => $pkg->description,
        'downloads' => number_format($pkg->downloads),
        'favers' => number_format($pkg->favers),
        'license' => $pkg->license,
        'maintainers' => $pkg->maintainers,
        'latestVersion' => array_key_first($pkg->versions),
    ]);
}
```

#### Example 2: Check Package Popularity

```php
$package = Packagist::package('laravel/framework');

if ($package->downloads > 1_000_000) {
    echo "Super popular package!";
} elseif ($package->favers > 10_000) {
    echo "Well-liked by community";
} else {
    echo "Growing package";
}
```

#### Example 3: Export Package Metadata

```php
$package = Packagist::package('symfony/console');

$metadata = [
    'name' => $package->name,
    'versions' => array_keys($package->versions),
    'maintainers' => array_map(
        fn($m) => $m->email,
        $package->maintainers
    ),
    'license' => $package->license,
    'exported_at' => now(),
];

return json_encode($metadata);
```

---

## 2. SearchPackagesAction - Search Packages

Search for packages matching specific criteria.

### Method Signature

```php
public function search(
    string $query,
    array $filters = []
): array
```

### Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `$query` | string | Search term | `laravel` |
| `$filters` | array | Filter options | `['type' => 'library']` |

### Filter Options

| Filter | Type | Default | Description | Example |
|--------|------|---------|-------------|---------|
| `per_page` | int | 15 | Results per page (max 100) | `['per_page' => 50]` |
| `type` | string | - | Package type (library, etc) | `['type' => 'library']` |
| `tags` | string | - | Search by tags | `['tags' => 'laravel']` |
| `sort` | string | - | Sort order | `['sort' => 'downloads']` |

### Basic Search

```php
use Akira\Packagist\Facades\Packagist;

// Simple search
$results = Packagist::search('laravel');

// Results is an array of package info
foreach ($results as $package) {
    echo $package['name'];           // "laravel/framework"
    echo $package['description'];    // Package description
    echo $package['downloads'];      // Number of downloads
    echo $package['favers'];         // Number of favers
    echo $package['repository'];     // Repository URL
}
```

### Search Result Structure

Each result is an array:

```php
$result = [
    'name' => 'laravel/framework',
    'description' => 'The Laravel Framework.',
    'url' => 'https://packagist.org/packages/laravel/framework',
    'repository' => 'https://github.com/laravel/framework',
    'downloads' => 9999999,
    'favers' => 50000,
    'type' => 'library',
    'abandoned' => false,  // Or 'name/replacement' if abandoned
];
```

### Advanced Filtering

Filter search results by type and control pagination:

```php
// Search for libraries only
$libraries = Packagist::search('laravel', [
    'type' => 'library',
]);

// Get more results (up to 100 per page)
$results = Packagist::search('laravel', [
    'per_page' => 50,  // Default is 15, max is 100
]);

// Combine multiple filters
$packages = Packagist::search('orm', [
    'type' => 'library',
    'per_page' => 100,
    'sort' => 'downloads',  // Sort by downloads or stars
]);

// Search for abandoned packages
$abandoned = Packagist::search('old-package', [
    'abandoned' => true,
]);
```

### Pagination Support

The `per_page` parameter controls result pagination:

```php
// Get default 15 results
$results = Packagist::search('laravel');  // ~15 results

// Get 50 results
$results = Packagist::search('laravel', ['per_page' => 50]);  // ~50 results

// Get maximum 100 results per request
$results = Packagist::search('laravel', ['per_page' => 100]);  // ~100 results

// Values over 100 are automatically capped at 100
$results = Packagist::search('laravel', ['per_page' => 500]);  // Still returns max 100
```

### Error Handling

Query validation:

```php
//  Empty query
Packagist::search('');  // Throws: InvalidArgumentException

//  Whitespace only
Packagist::search('   ');  // Throws: InvalidArgumentException

//  Too long (>1000 chars)
$longQuery = str_repeat('a', 1001);
Packagist::search($longQuery);  // Throws: InvalidArgumentException

//  Valid queries
Packagist::search('laravel');
Packagist::search('symfony console');
Packagist::search('database orm');
```

### Caching Behavior

By default, uses `RememberCache` with 5-minute TTL:

```php
// First search: API request
$results = Packagist::search('laravel');  // ~150ms

// Same search within 5 minutes: Cache hit
$results = Packagist::search('laravel');  // ~5ms

// Different search: New API request
$results = Packagist::search('symfony');  // ~150ms (new cache)
```

### Real-World Examples

#### Example 1: Find Best Testing Framework

```php
$testingPackages = Packagist::search('testing framework');

// Find the most popular
$popular = collect($testingPackages)
    ->sortByDesc('downloads')
    ->first();

echo "Most popular: {$popular['name']}";
echo "Downloads: {$popular['downloads']}";
```

#### Example 2: Build Package Recommendations

```php
public function findAlternatives($packageName)
{
    // Extract category from package name
    $category = explode('/', $packageName)[1];  // "framework" from "laravel/framework"

    // Search for similar packages
    $alternatives = Packagist::search($category, [
        'type' => 'library',
    ]);

    // Sort by popularity
    return collect($alternatives)
        ->sortByDesc('downloads')
        ->take(5);
}
```

#### Example 3: Monitor Deprecated Packages

```php
$deprecated = Packagist::search('old-library');

foreach ($deprecated as $package) {
    if (!empty($package['abandoned'])) {
        echo "{$package['name']} is abandoned";

        // If abandoned value is a string, it's the replacement
        if (is_string($package['abandoned'])) {
            echo "Use {$package['abandoned']} instead";
        }
    }
}
```

#### Example 4: Paginated Search Results

```php
// Note: API returns paginated results automatically
public function searchWithPagination($query)
{
    $allResults = [];
    $page = 1;

    do {
        $results = Packagist::search($query);  // API handles pagination

        $allResults = array_merge($allResults, $results);

        // Stop after first 100 results
        if (count($allResults) >= 100) {
            break;
        }

        $page++;
    } while (count($results) > 0);

    return $allResults;
}
```

---

## 3. GetStatsAction - Packagist Statistics

Get overall Packagist.org statistics.

### Method Signature

```php
public function stats(array $filters = []): array
```

### Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `$filters` | array | Filter options | `['period' => 'monthly']` |

### Available Filters

| Filter | Type | Values | Default |
|--------|------|--------|---------|
| `period` | string | `total`, `yearly`, `monthly`, `daily` | `total` |

### Basic Usage

```php
use Akira\Packagist\Facades\Packagist;

// Get total stats
$stats = Packagist::stats();

echo $stats['packages'];     // Total packages
echo $stats['downloads'];    // Total downloads
echo $stats['favers'];       // Total favers
echo $stats['updates'];      // Total updates
```

### Stats by Period

```php
// Total all-time
$total = Packagist::stats(['period' => 'total']);

// Last year
$yearly = Packagist::stats(['period' => 'yearly']);

// Last month
$monthly = Packagist::stats(['period' => 'monthly']);

// Last day
$daily = Packagist::stats(['period' => 'daily']);
```

### Response Structure

Each response contains:

```php
$stats = [
    'packages' => 999999,      // Total packages
    'downloads' => 50000000,   // Total downloads
    'favers' => 100000,        // Total favorites
    'updates' => 99999,        // Recent updates
];
```

### Error Handling

Period validation:

```php
//  Invalid period
Packagist::stats(['period' => 'invalid']);
// Throws: InvalidArgumentException

//  Valid periods
Packagist::stats(['period' => 'total']);
Packagist::stats(['period' => 'yearly']);
Packagist::stats(['period' => 'monthly']);
Packagist::stats(['period' => 'daily']);
```

### Caching Behavior

Uses `RememberCache` with 1-hour TTL:

```php
// First call: API request
$stats = Packagist::stats();  // ~100ms

// Within 1 hour: Cache hit
$stats = Packagist::stats();  // ~5ms
```

### Real-World Examples

#### Example 1: Dashboard Statistics Widget

```php
// Controller
public function dashboard()
{
    $stats = Packagist::stats(['period' => 'yearly']);

    $metrics = [
        'total_packages' => number_format($stats['packages']),
        'total_downloads' => number_format($stats['downloads']),
        'growth' => $this->calculateGrowth($stats),
        'updated_at' => now(),
    ];

    return view('dashboard', $metrics);
}
```

#### Example 2: Growth Tracking

```php
public function trackGrowth()
{
    $totalStats = Packagist::stats(['period' => 'total']);
    $yearlyStats = Packagist::stats(['period' => 'yearly']);

    $percentageOfYearly = ($yearlyStats['downloads'] / $totalStats['downloads']) * 100;

    echo "This year: {$percentageOfYearly}% of all downloads";
}
```

#### Example 3: Scheduled Statistics Report

```php
// Command: php artisan packagist:daily-report
class DailyPackagistReport extends Command
{
    public function handle()
    {
        $daily = Packagist::stats(['period' => 'daily']);

        $report = "Daily Report\n";
        $report .= "Packages: {$daily['packages']}\n";
        $report .= "Downloads: {$daily['downloads']}\n";
        $report .= "Generated: " . now() . "\n";

        \Log::channel('packagist')->info($report);

        // Send to analytics service
        Analytics::log('packagist_stats', $daily);

        $this->info('Report generated');
    }
}
```

---

## 4. GetMaintainersAction - Package Maintainers

Get maintainers for a specific package.

### Method Signature

```php
public function maintainers(string $package): array
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$package` | string | Vendor/package name |

### Basic Usage

```php
use Akira\Packagist\Facades\Packagist;

$maintainers = Packagist::maintainers('laravel/framework');

foreach ($maintainers as $maintainer) {
    echo $maintainer['name'];      // "Taylor Otwell"
    echo $maintainer['email'];     // "taylor@laravel.com"
    echo $maintainer['homepage'];  // "https://taylorotwell.com"
}
```

### Response Structure

Each maintainer is an array:

```php
$maintainer = [
    'name' => 'Taylor Otwell',
    'email' => 'taylor@laravel.com',
    'homepage' => 'https://taylorotwell.com',
];
```

### Error Handling

```php
//  Invalid package name
Packagist::maintainers('invalid-no-vendor-slash');
// Throws: InvalidArgumentException

//  Valid format
Packagist::maintainers('laravel/framework');
```

### Real-World Examples

#### Example 1: Contact Package Maintainers

```php
public function sendMaintainerNotification($packageName, $message)
{
    $maintainers = Packagist::maintainers($packageName);

    foreach ($maintainers as $maintainer) {
        Mail::to($maintainer['email'])->send(
            new PackageMaintainerNotification($message)
        );
    }

    return count($maintainers) . " emails sent";
}
```

#### Example 2: Display Package Team

```php
// View component
@props(['packageName'])

@php
    $maintainers = Packagist::maintainers($packageName);
@endphp

<div class="maintainers">
    <h3>Maintained by</h3>
    @foreach($maintainers as $maintainer)
        <div class="maintainer">
            <a href="mailto:{{ $maintainer['email'] }}">
                {{ $maintainer['name'] }}
            </a>
            @if($maintainer['homepage'])
                <a href="{{ $maintainer['homepage'] }}" target="_blank">
                    Website
                </a>
            @endif
        </div>
    @endforeach
</div>
```

#### Example 3: Verify Maintainer

```php
public function isMaintainer($packageName, $email)
{
    $maintainers = Packagist::maintainers($packageName);

    return collect($maintainers)
        ->pluck('email')
        ->contains($email);
}
```

---

## Facade Methods Reference

Complete list of available methods:

```php
use Akira\Packagist\Facades\Packagist;

// Get package details
Packagist::package(string $package): PackageDTO

// Search for packages
Packagist::search(string $query, array $filters = []): array

// Get statistics
Packagist::stats(array $filters = []): array

// Get package maintainers
Packagist::maintainers(string $package): array
```

---

## Error Handling Best Practices

### Try-Catch Pattern

```php
use Akira\Packagist\Exceptions\InvalidArgumentException;
use Akira\Packagist\Exceptions\PackageNotFoundException;

try {
    $package = Packagist::package('vendor/package');
} catch (InvalidArgumentException $e) {
    // Invalid package name format
    echo "Invalid package name: " . $e->getMessage();
    return response()->json(['error' => 'Invalid format'], 400);
} catch (Exception $e) {
    // API error, network error, etc.
    echo "API error: " . $e->getMessage();
    return response()->json(['error' => 'API error'], 500);
}
```

### Graceful Degradation

```php
public function getPackageSafe($packageName)
{
    try {
        return Packagist::package($packageName);
    } catch (Exception $e) {
        \Log::error("Failed to fetch {$packageName}", [
            'exception' => $e->getMessage(),
        ]);

        // Return cached version or null
        return cache()->get("packagist:package:{$packageName}");
    }
}
```

---

## Performance Tips

### 1. Batch Requests

```php
//  Bad: Multiple API calls
foreach ($packages as $package) {
    $data = Packagist::package($package);
}

//  Good: Parallel or batch if possible
$packages = collect($packageNames)->map(
    fn($name) => Packagist::package($name)
);
```

### 2. Cache Aggressively

```php
// Use ForeverCache for rarely-changing data
'GetPackageAction' => [
    'strategy' => ForeverCache::class,
]
```

### 3. Lazy Load Related Data

```php
//  Load all data immediately
$packages = Packagist::search('laravel');

//  Load details only when needed
$packages = Packagist::search('laravel');  // Just metadata

if ($user->wantsDetails()) {
    // Load full details only if needed
    foreach ($packages as $pkg) {
        $full = Packagist::package($pkg['name']);
    }
}
```

---

## 5. GetAllPackagesAction - All Packages

Retrieve a complete list of all packages on Packagist.

### Method Signature

```php
public function packages(): array
```

### Returns

Array of package names:

```php
[
    'packages' => [
        'vendor/package-1',
        'vendor/package-2',
        // ... thousands more
    ]
]
```

### Basic Usage

```php
use Akira\Packagist\Facades\Packagist;

// Get all packages
$allPackages = Packagist::packages();

foreach ($allPackages['packages'] as $packageName) {
    echo $packageName;  // "laravel/framework"
}
```

---

## 6. GetTopPackagesAction - Top Packages

Retrieve the most downloaded packages on Packagist.

### Method Signature

```php
public function topPackages(int $limit = 9): array
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$limit` | int | 9 | Number of top packages to return |

### Returns

Array of top packages with download counts:

```php
[
    [
        'name' => 'laravel/framework',
        'description' => 'The Laravel Framework.',
        'downloads' => 999999999,
        'url' => 'https://packagist.org/packages/laravel/framework',
    ],
    // ... more packages
]
```

### Examples

```php
// Top 9 packages (default)
$topPackages = Packagist::topPackages();

// Top 5 packages
$topFive = Packagist::topPackages(5);

// Top 20 packages
$topTwenty = Packagist::topPackages(20);

// Iterate through results
foreach ($topPackages as $package) {
    echo "{$package['name']}: {$package['downloads']} downloads";
}
```

---

## 7. GetVendorPackagesAction - Vendor Packages

Retrieve all packages from a specific vendor.

### Method Signature

```php
public function vendorPackages(string $vendor): array
```

### Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `$vendor` | string | Vendor name (case-insensitive) | `laravel` or `Laravel` |

### Returns

Array of search results from vendor:

```php
[
    'results' => [
        [
            'name' => 'laravel/framework',
            'description' => 'The Laravel Framework.',
            'downloads' => 999999999,
            'url' => 'https://packagist.org/packages/laravel/framework',
        ],
        // ... more packages from vendor
    ],
    'total' => 123,
]
```

### Examples

```php
// Get all Laravel packages
$laravelPackages = Packagist::vendorPackages('laravel');

// Get all Symfony packages
$symfonyPackages = Packagist::vendorPackages('symfony');

// Iterate through vendor packages
foreach ($laravelPackages['results'] as $package) {
    echo $package['name'];
}

// Get total packages in vendor
echo "Total: {$laravelPackages['total']}";
```

### Real-World Examples

#### Example 1: Display Vendor Packages

```php
public function showVendor($vendor)
{
    $packages = Packagist::vendorPackages($vendor);

    return view('vendor.packages', [
        'vendor' => $vendor,
        'packages' => $packages['results'],
        'total' => $packages['total'],
    ]);
}
```

#### Example 2: Analyze Vendor Statistics

```php
public function analyzeVendor($vendor)
{
    $packages = Packagist::vendorPackages($vendor);

    $stats = collect($packages['results'])->map(function ($pkg) {
        return [
            'name' => $pkg['name'],
            'downloads' => $pkg['downloads'],
        ];
    })->sortByDesc('downloads');

    return $stats->take(10);  // Top 10
}
```

---

## 8. GetVendorTopPackagesAction - Top Vendor Packages

Retrieve the most downloaded packages from a specific vendor.

### Method Signature

```php
public function vendorTopPackages(string $vendor, int $limit = 9): array
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$vendor` | string | - | Vendor name |
| `$limit` | int | 9 | Number of top packages |

### Returns

Array of top packages from vendor, sorted by downloads:

```php
[
    [
        'name' => 'laravel/framework',
        'description' => 'The Laravel Framework.',
        'downloads' => 999999999,
        'url' => 'https://packagist.org/packages/laravel/framework',
        'repository' => 'https://github.com/laravel/framework',
    ],
    // ... more packages
]
```

### Examples

```php
// Top 9 Laravel packages (default)
$laravelTop = Packagist::vendorTopPackages('laravel');

// Top 5 Symfony packages
$symfonyTop = Packagist::vendorTopPackages('symfony', 5);

// Top 20 packages from any vendor
$vendorTop = Packagist::vendorTopPackages('vendor-name', 20);

// Use in dashboard
foreach ($laravelTop as $package) {
    echo "{$package['name']}: {$package['downloads']} downloads";
}
```

### Real-World Examples

#### Example 1: Vendor Dashboard

```php
public class VendorDashboard
{
    public function index($vendor)
    {
        $topPackages = Packagist::vendorTopPackages($vendor, 10);
        $stats = Packagist::stats();

        return view('dashboard', [
            'vendor' => $vendor,
            'topPackages' => $topPackages,
            'totalDownloads' => collect($topPackages)
                ->sum('downloads'),
        ]);
    }
}
```

#### Example 2: Package Comparison

```php
public function compare($vendor1, $vendor2)
{
    $vendor1Top = Packagist::vendorTopPackages($vendor1, 5);
    $vendor2Top = Packagist::vendorTopPackages($vendor2, 5);

    return [
        $vendor1 => $vendor1Top,
        $vendor2 => $vendor2Top,
    ];
}
```

---

## Next Steps

- **[Testing Guide](./07-testing.md)** - Test your implementation
- **[Troubleshooting](./08-troubleshooting.md)** - Common issues
- **[Advanced Topics](./09-advanced.md)** - Custom implementations

---

**Tip:** Start with simple queries and cache aggressively. The API has rate limits, so use caching wisely!
