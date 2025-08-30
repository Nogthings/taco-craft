<?php

use Symfony\Component\Console\Tester\ApplicationTester;
use TacoCraft\Application;

test('application can be instantiated', function () {
    $app = new Application;

    expect($app)->toBeInstanceOf(Application::class);
    expect($app->getName())->toBe('TacoCraft');
    expect($app->getVersion())->toBe('1.0.0');
});

test('application has required commands', function () {
    $app = new Application;

    $commands = $app->all();
    $commandNames = array_keys($commands);

    expect($commandNames)->toContain('new');
    expect($commandNames)->toContain('list');
    expect($commandNames)->toContain('help');
});

test('application can show version', function () {
    $app = new Application;
    $app->setAutoExit(false);

    $tester = new ApplicationTester($app);
    $tester->run(['--version']);

    expect($tester->getDisplay())->toContain('TacoCraft version 1.0.0');
    expect($tester->getStatusCode())->toBe(0);
});

test('application can show help', function () {
    $app = new Application;
    $app->setAutoExit(false);

    $tester = new ApplicationTester($app);
    $tester->run(['help']);

    expect($tester->getDisplay())->toContain('Usage:');
    expect($tester->getDisplay())->toContain('help');
    expect($tester->getStatusCode())->toBe(0);
});
