# Documentation Table of Contents

Complete documentation for Laravel Packagist - a production-grade Laravel package for Packagist.org API integration.

## Documentation Guides

### Getting Started
- **[01. Getting Started](./01-getting-started.md)** - Introduction and quick start
  - What is Laravel Packagist?
  - Key features
  - Quick start (3 steps)
  - Core concepts (Actions, DTOs, Cache Strategies)
  - Configuration overview
  - Next steps

### Installation & Setup
- **[02. Installation Guide](./02-installation.md)** - Complete installation instructions
  - Requirements (PHP 8.4+, Laravel 12+)
  - Step-by-step installation
  - Publishing configuration
  - Queue setup (Redis, Database, Sync)
  - Environment configuration
  - Verification & testing
  - Troubleshooting

### Configuration
- **[03. Configuration Guide](./03-configuration.md)** - All configuration options explained
  - Global cache settings
    - Cache driver selection
    - Cache strategy choice
    - TTL (Time To Live)
    - Tags for bulk clearing
    - Enable/disable caching
  - Auto-revalidation settings
    - Enable/disable auto-renewal
    - Revalidate before expiry timing
    - Queue name selection
  - Per-action configuration
    - GetPackageAction
    - SearchPackagesAction
    - GetStatsAction
    - GetMaintainersAction
  - Decision tree for configuration
  - Real-world examples

### Cache Strategies
- **[04. Cache Strategies Guide](./04-cache-strategies.md)** - Deep dive into 4 caching strategies
  - RevalidateCache (default) - Auto-renewal before expiry
  - RememberCache - Standard TTL-based caching
  - ForeverCache - Indefinite caching until manual cleanup
  - NoneCache - No caching (development only)
  - Strategy comparison & selection
  - Per-action strategy configuration
  - Performance metrics

### API Usage
- **[05. API Usage Guide](./05-api-usage.md)** - Complete API reference with examples
  - GetPackageAction - Fetch package details
    - Method signature
    - Parameters & returns
    - Working with versions & maintainers
    - Error handling
    - Real-world examples
  - SearchPackagesAction - Search for packages
    - Basic search
    - Advanced filtering
    - Error handling
    - Real-world examples
  - GetStatsAction - Packagist statistics
    - Stats by period
    - Filtering
    - Real-world examples
  - GetMaintainersAction - Package maintainers
    - Fetching maintainers
    - Real-world examples
  - Error handling & performance tips

### Auto-Revalidation
- **[06. Auto-Revalidation Guide](./06-auto-revalidation.md)** - Background cache refresh
  - How it works (step-by-step)
  - Problems it solves
  - Configuration
  - Setup examples (short/medium/long TTL)
  - Monitoring & alerting
  - Optimization tips
  - Troubleshooting
  - Performance impact
  - Best practices

### Testing
- **[07. Testing Guide](./07-testing.md)** - Writing tests for Laravel Packagist
  - Running tests
  - Unit testing
    - Testing validators
    - Testing DTOs
    - Testing cache
  - Integration testing
    - Testing actions with mock client
    - Testing cache behavior
  - Testing auto-revalidation
    - Job dispatch
    - Job execution
  - Best practices
  - Debugging
  - CI/CD integration

### Troubleshooting
- **[08. Troubleshooting Guide](./08-troubleshooting.md)** (Coming soon)
  - Common issues & solutions
  - Error messages explained
  - Performance debugging
  - Queue issues
  - Cache issues
  - API connection issues

### Advanced Topics
- **[09. Advanced Guide](./09-advanced.md)** (Coming soon)
  - Custom cache implementations
  - Custom validators
  - Custom actions
  - Extending the package
  - Performance optimization
  - Security considerations

---

##  Quick Navigation by Use Case

### "I'm just getting started"
1. [Getting Started](./01-getting-started.md)
2. [Installation](./02-installation.md)
3. [API Usage](./05-api-usage.md)

### "I want to configure for my needs"
1. [Configuration Guide](./03-configuration.md)
2. [Cache Strategies](./04-cache-strategies.md)
3. [Auto-Revalidation](./06-auto-revalidation.md)

### "I need to set up caching properly"
1. [Cache Strategies](./04-cache-strategies.md)
2. [Configuration Guide](./03-configuration.md)
3. [Auto-Revalidation](./06-auto-revalidation.md)

### "I want to test my implementation"
1. [Testing Guide](./07-testing.md)
2. [API Usage](./05-api-usage.md) (for examples)

### "Something isn't working"
1. [Troubleshooting Guide](./08-troubleshooting.md)
2. [Installation Guide](./02-installation.md) (setup issues)
3. [Configuration Guide](./03-configuration.md) (config issues)

### "I need more advanced features"
1. [Advanced Guide](./09-advanced.md)
2. [Configuration Guide](./03-configuration.md)

---

##  Reading Order

### For New Users (2 hours)
1. **Getting Started** (15 minutes)
   - Understand what the package does
   - See quick example

2. **Installation** (30 minutes)
   - Install the package
   - Set up queue if needed
   - Verify installation

3. **API Usage** (45 minutes)
   - Learn the 4 main actions
   - Try examples
   - Start building

4. **Configuration** (30 minutes)
   - Understand all options
   - Adjust for your needs

### For Production Setup (4 hours)
1. **Installation** (30 minutes)
   - Production-grade setup
   - Queue configuration (Redis)

2. **Configuration** (1 hour)
   - Choose cache driver
   - Set TTLs
   - Configure auto-revalidation

3. **Cache Strategies** (1 hour)
   - Understand each strategy
   - Choose best for your data

4. **Auto-Revalidation** (30 minutes)
   - Enable & configure
   - Set up monitoring

5. **Testing** (30 minutes)
   - Write tests
   - Verify setup

### For Operations (1.5 hours)
1. **Auto-Revalidation** (30 minutes)
   - Monitoring
   - Troubleshooting

2. **Troubleshooting** (30 minutes)
   - Common issues
   - Solutions

3. **Testing** (30 minutes)
   - Monitoring in tests

---

##  Cross-References

### Cache-Related
- Configuration → Cache Strategies → Auto-Revalidation
- Choose strategy → Configure per-action → Monitor auto-revalidation

### Setup-Related
- Installation → Configuration → Testing

### API-Related
- API Usage → Testing → Troubleshooting

### Performance-Related
- Cache Strategies → Auto-Revalidation → Testing

---

##  Key Concepts

### Actions
Methods that perform specific operations:
- `package()` - Get package details
- `search()` - Search for packages
- `stats()` - Get statistics
- `maintainers()` - Get maintainers

### DTOs
Type-safe data containers:
- `PackageDTO` - Package information
- `VersionDTO` - Version information
- `MaintainerDTO` - Maintainer information

### Cache Strategies
Different caching approaches:
- `RevalidateCache` - Auto-renewal (default)
- `RememberCache` - TTL-based
- `ForeverCache` - Manual cleanup
- `NoneCache` - No caching

### Validators
Input validation:
- `PackageValidator` - Package name format
- `SearchValidator` - Query validation
- `StatsValidator` - Period validation

---

## 🆘 Getting Help

### Common Questions
- "How do I cache data?" → [Cache Strategies](./04-cache-strategies.md)
- "How do I avoid latency?" → [Auto-Revalidation](./06-auto-revalidation.md)
- "My queue isn't working" → [Troubleshooting](./08-troubleshooting.md)
- "How do I test this?" → [Testing Guide](./07-testing.md)

### Documentation Structure
Each guide includes:
- **Overview** - What's covered
- **Concepts** - Background knowledge
- **Examples** - Real-world code
- **Best Practices** - Do's and don'ts
- **Troubleshooting** - Common issues

---

##  Additional Resources

### External Links
- [Packagist.org API Docs](https://packagist.org/about)
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Queue Docs](https://laravel.com/docs/queues)
- [Redis Documentation](https://redis.io/documentation)

### Code Examples
- Complete examples in each guide
- Real-world use cases
- Best practices demonstrated
- Error handling patterns

---

##  Learning Path

### Beginner (1-2 hours)
```
Getting Started
    ↓
Installation
    ↓
API Usage (basic)
    ↓
First Implementation
```

### Intermediate (4-6 hours)
```
Configuration
    ↓
Cache Strategies
    ↓
Auto-Revalidation
    ↓
Testing
    ↓
Production Deployment
```

### Advanced (8+ hours)
```
Advanced Guide
    ↓
Custom Implementations
    ↓
Performance Tuning
    ↓
Monitoring & Observability
```

---

##  Checklist for Getting Started

- [ ] Read Getting Started guide
- [ ] Install package with Composer
- [ ] Publish configuration file
- [ ] Configure cache driver (Redis recommended)
- [ ] Set up queue if using auto-revalidation
- [ ] Run tests to verify installation
- [ ] Try first API call with examples
- [ ] Configure for your specific needs
- [ ] Deploy to production with monitoring
- [ ] Set up alerts for queue failures

---

##  Keeping Up to Date

### Configuration Changes
When you update the package:
```bash
php artisan vendor:publish --provider="Akira\Packagist\Providers\PackagistServiceProvider" --force
```

### Documentation Updates
Check this table of contents for latest guides.

### API Changes
Follow Packagist.org API updates at [their documentation](https://packagist.org/about).

---

**Last Updated:** October 2025
**Documentation Version:** 1.0.0
**Package Version:** 1.x

---

Ready to get started? Begin with [Getting Started Guide](./01-getting-started.md) →
