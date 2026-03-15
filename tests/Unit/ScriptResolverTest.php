<?php

declare(strict_types=1);

use Auroro\Composer\ScriptResolver;

it('passes through plain commands', function () {
    $resolver = new ScriptResolver();

    expect($resolver->resolve('vendor/bin/pest'))->toBe('vendor/bin/pest');
});

it('resolves @php prefix', function () {
    $resolver = new ScriptResolver();

    expect($resolver->resolve('@php bin/lens audit'))->toBe('php bin/lens audit');
});

it('resolves @composer prefix', function () {
    $resolver = new ScriptResolver();

    expect($resolver->resolve('@composer validate'))->toBe('composer validate');
});

it('resolves @putenv to export', function () {
    $resolver = new ScriptResolver();

    expect($resolver->resolve('@putenv APP_ENV=test'))->toBe('export APP_ENV=test');
});

it('resolves @binname to bin entry path', function () {
    $resolver = new ScriptResolver(bin: ['bin/lens']);

    expect($resolver->resolve('@lens'))->toBe('bin/lens');
});

it('resolves @binname with arguments', function () {
    $resolver = new ScriptResolver(bin: ['bin/lens']);

    expect($resolver->resolve('@lens audit --strict'))->toBe('bin/lens audit --strict');
});

it('resolves @scriptname recursively', function () {
    $resolver = new ScriptResolver(scripts: [
        'test' => 'vendor/bin/pest',
        'check' => '@test',
    ]);

    expect($resolver->resolve('@test'))->toBe('vendor/bin/pest');
});

it('resolves nested script references', function () {
    $resolver = new ScriptResolver(scripts: [
        'inner' => '@php bin/run',
        'outer' => '@inner',
    ]);

    expect($resolver->resolve('@outer'))->toBe('php bin/run');
});

it('strips Composer callbacks from array scripts', function () {
    $resolver = new ScriptResolver();

    expect($resolver->resolve([
        'Composer\\Config::disableProcessTimeout',
        'php demo.php',
    ]))->toBe('php demo.php');
});

it('strips modifier directives', function () {
    $resolver = new ScriptResolver();

    expect($resolver->resolve([
        '@no_additional_args',
        'vendor/bin/pest',
    ]))->toBe('vendor/bin/pest');
});

it('joins array scripts with &&', function () {
    $resolver = new ScriptResolver();

    expect($resolver->resolve(['step1', 'step2']))->toBe('step1 && step2');
});

it('handles mixed array with callbacks, prefixes, and plain commands', function () {
    $resolver = new ScriptResolver();

    expect($resolver->resolve([
        'Composer\\Config::disableProcessTimeout',
        '@putenv APP_ENV=test',
        '@php bin/serve',
    ]))->toBe('export APP_ENV=test && php bin/serve');
});

it('guards against infinite recursion', function () {
    $resolver = new ScriptResolver(scripts: [
        'a' => '@b',
        'b' => '@a',
    ]);

    expect($resolver->resolve('@a'))->toBe('');
});

it('prefers bin entry over script reference', function () {
    $resolver = new ScriptResolver(
        scripts: ['lens' => 'echo script'],
        bin: ['bin/lens'],
    );

    expect($resolver->resolve('@lens audit'))->toBe('bin/lens audit');
});
