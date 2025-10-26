<?php

declare(strict_types=1);

use Akira\Packagist\Commands\InstallCommand;
use Symfony\Component\Console\Tester\CommandTester;

test('install command exists', function () {
    expect(class_exists(InstallCommand::class))->toBeTrue();
});

test('install command is a console command', function () {
    $command = new InstallCommand();
    expect($command)->toBeInstanceOf(\Illuminate\Console\Command::class);
});

test('install command can be instantiated', function () {
    $command = new InstallCommand();
    expect($command)->not->toBeNull();
});
