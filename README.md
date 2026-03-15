# auroro/composer

Composer script resolution utilities for PHP.

## Installation

```bash
composer require auroro/composer
```

## Usage

`ScriptResolver` resolves Composer script references (`@` prefixes) into executable shell commands.

```php
use Auroro\Composer\ScriptResolver;

$resolver = new ScriptResolver(
    scripts: [
        'test' => 'vendor/bin/pest',
        'check' => ['@test', '@php vendor/bin/phpstan analyse'],
    ],
    bin: ['bin/lens'],
);

$resolver->resolve('@test');              // "vendor/bin/pest"
$resolver->resolve('@check');             // "vendor/bin/pest && php vendor/bin/phpstan analyse"
$resolver->resolve('@lens audit');        // "bin/lens audit"
$resolver->resolve('@putenv APP_ENV=ci'); // "export APP_ENV=ci"
```

### Supported prefixes

| Prefix | Resolves to |
|--------|-------------|
| `@php` | `php` |
| `@composer` | `composer` |
| `@putenv KEY=VAL` | `export KEY=VAL` |
| `@binname` | Matching bin entry path |
| `@scriptname` | Recursive script resolution |

Composer callbacks (`ClassName::method`) and modifier directives (`@no_additional_args`) are automatically stripped.

## License

MIT
