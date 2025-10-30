# Changelog

# 1.0.0 (2025-10-30)


### Bug Fixes

* add missing GitHub stats properties to PackageDTO constructor and fromArray ([2a6815a](https://github.com/akira-io/laravel-packagist/commit/2a6815ac01836f234bb99f1be4fdde2e22d815de))
* filter vendor packages manually to handle vendors without exact match ([f053825](https://github.com/akira-io/laravel-packagist/commit/f053825b89f17c78de81528e90a6c396b218b4ae))
* handle license as array in VersionDTO to prevent array to string conversion ([e5891d9](https://github.com/akira-io/laravel-packagist/commit/e5891d9f077840acd6f2cf46dfb0546b4e836407))
* update pre-release test command from npm to composer ([14f523f](https://github.com/akira-io/laravel-packagist/commit/14f523fe0df7803929f882b0cd8eb0e8659bf370))


### Features

* add dependency injection attributes for PackagistClient in ClientContract ([3086b52](https://github.com/akira-io/laravel-packagist/commit/3086b52d837130050f248e19d4c350354876ba93))
* add forget method and driver support check in BaseCache ([f9e4066](https://github.com/akira-io/laravel-packagist/commit/f9e40663028fe3ead34ac5accc0e7a401bb525ea))
* add forget method and driver support check in BaseCache ([d024618](https://github.com/akira-io/laravel-packagist/commit/d024618598ee42be54ce249db56e92d9cc6499d2))
* add GetRepositoryAction to retrieve GitHub repository details for a package ([77dc001](https://github.com/akira-io/laravel-packagist/commit/77dc001752fa2ef0806657b41c6601d542d3b3ff))
* add GetVendorPackagesAction and GetVendorTopPackagesAction for vendor-specific package retrieval ([8832b58](https://github.com/akira-io/laravel-packagist/commit/8832b58ce56d73add2c07d95b37ac7958af9df03))
* add GitHub release notification workflow to Discord ([56f2023](https://github.com/akira-io/laravel-packagist/commit/56f20233fe59663b42fa0343864c973165ca3d65))
* add GitHub stats (stars, forks, watchers, open_issues) and metadata (language, dependents, suggesters) to PackageDTO ([dfea01d](https://github.com/akira-io/laravel-packagist/commit/dfea01dd169643e8fde4aa29739b5a408dded306))
* add InstallCommand for Laravel Packagist installation and configuration ([5380265](https://github.com/akira-io/laravel-packagist/commit/53802653df2ee005062e0db9010a81592704e00c))
* add Laravel service provider configuration to composer.json ([12beece](https://github.com/akira-io/laravel-packagist/commit/12beece2a9ba99a64c804c15e0a56c1021d551c1))
* add package.json and .release-it.json for release management ([6bed64f](https://github.com/akira-io/laravel-packagist/commit/6bed64f8b8b75c2e1b439942ed0a582c120f8947))
* add Packagist SDK with caching strategies and API integration ([d302bbc](https://github.com/akira-io/laravel-packagist/commit/d302bbc20466c402a406ed78e44a4fa6c97f5a5f))
* enhance CacheContract and related classes to support endpoint parameter in get method ([def09a3](https://github.com/akira-io/laravel-packagist/commit/def09a34d404e8aa661a9866b867abda14a1b32f))
* enhance InstallCommand with Laravel Prompts for improved user interaction ([32a1bcf](https://github.com/akira-io/laravel-packagist/commit/32a1bcfe233f97451ea08aae15e4a9c87e8103da))
* enhance RevalidateCacheJob with dispatchable traits and queueable functionality ([46a8c0f](https://github.com/akira-io/laravel-packagist/commit/46a8c0f416d4ffa3682063a7f29442c96a8b7c83))
* enhance vendor package retrieval with pagination support ([820f353](https://github.com/akira-io/laravel-packagist/commit/820f353b2c713bc6be51a0c14a602656baca2917))
* implement auto revalidation for caching with configuration options ([16e652f](https://github.com/akira-io/laravel-packagist/commit/16e652f86fc085b6bf21cec101628dc1297a94c5))
* implement ShouldQueue interface and add middleware to RevalidateCacheJob ([b5b26e0](https://github.com/akira-io/laravel-packagist/commit/b5b26e0d744bfca4e0ba3759c40d3d8ec2a944a5))
* improve user interaction in InstallCommand with enhanced prompts and structured output ([1dae43a](https://github.com/akira-io/laravel-packagist/commit/1dae43af3534ba58d06dbfc5fc2cb914a6c61393))
* include full downloads object (total, monthly, daily) in PackageDTO ([d26184a](https://github.com/akira-io/laravel-packagist/commit/d26184a26cc847c222fc96b131f8f799f6b29ffc))
* refactor RevalidateCacheJob to use readonly properties and improve logging ([34125af](https://github.com/akira-io/laravel-packagist/commit/34125afc2ef8b521652597264ea4c15f29708d6e))
* refine user prompts and update GitHub repository link in InstallCommand ([d9befed](https://github.com/akira-io/laravel-packagist/commit/d9befed5e984615307a80b8ca98ed7dfdfb50618))
* update GetTopPackagesAction to use packageNames instead of packages in response handling ([52a4c9c](https://github.com/akira-io/laravel-packagist/commit/52a4c9cbcc28e18766b6104ac8daafe0eefaf2e0))
