<?php

declare(strict_types=1);

namespace Auroro\Composer;

final class ScriptResolver
{
    /**
     * @param array<string, string|list<string>> $scripts Available scripts for @scriptname resolution
     * @param list<string> $bin Bin entry paths for @binname resolution
     */
    public function __construct(
        private array $scripts = [],
        private array $bin = [],
    ) {}

    /**
     * @param string|list<string> $command
     */
    public function resolve(string|array $command): string
    {
        $parts = is_array($command) ? $command : [$command];
        $resolved = [];

        foreach ($parts as $part) {
            $result = $this->resolveCommand($part);

            if ($result !== null) {
                $resolved[] = $result;
            }
        }

        return implode(' && ', $resolved);
    }

    private function resolveCommand(string $command, int $depth = 0): ?string
    {
        if ($depth > 10) {
            return null;
        }

        if ($this->isComposerCallback($command)) {
            return null;
        }

        if ($this->isModifier($command)) {
            return null;
        }

        if (str_starts_with($command, '@php ')) {
            return 'php ' . substr($command, 5);
        }

        if (str_starts_with($command, '@composer ')) {
            return 'composer ' . substr($command, 10);
        }

        if (str_starts_with($command, '@putenv ')) {
            return 'export ' . substr($command, 8);
        }

        if (str_starts_with($command, '@')) {
            return $this->resolveReference($command, $depth);
        }

        return $command;
    }

    private function resolveReference(string $command, int $depth): string
    {
        $rest = substr($command, 1);
        $spacePos = strpos($rest, ' ');
        $ref = $spacePos !== false ? substr($rest, 0, $spacePos) : $rest;
        $args = $spacePos !== false ? substr($rest, $spacePos) : '';

        foreach ($this->bin as $binPath) {
            if (basename($binPath) === $ref) {
                return trim($binPath . $args);
            }
        }

        if (isset($this->scripts[$ref])) {
            $target = $this->scripts[$ref];
            $targetParts = is_array($target) ? $target : [$target];
            $resolved = [];

            foreach ($targetParts as $part) {
                $result = $this->resolveCommand($part, $depth + 1);

                if ($result !== null) {
                    $resolved[] = $result;
                }
            }

            $inner = implode(' && ', $resolved);

            return $args !== '' ? trim($inner . $args) : $inner;
        }

        return $command;
    }

    private function isComposerCallback(string $command): bool
    {
        return (bool) preg_match('/^[A-Z][a-zA-Z0-9\\\\]+::[a-zA-Z]/', $command);
    }

    private function isModifier(string $command): bool
    {
        return $command === '@no_additional_args' || $command === '@additional_args';
    }
}
