<?php

declare(strict_types=1);

namespace HalfShellStudios\ComposerLink\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

final class ComposerProcessRunner
{
    /**
     * @param non-empty-string|null $composerEnvPath Absolute path passed as COMPOSER (e.g. …/composer.local.json)
     */
    public function __construct(
        private readonly string $workingDirectory,
        private readonly string $composerBinary,
        private readonly bool $withAllDependencies,
        private readonly ?string $composerEnvPath = null,
    ) {
    }

    /**
     * @param list<string> $extraArgs
     */
    public function install(array $extraArgs = []): int
    {
        return $this->run(array_merge(['install'], $extraArgs));
    }

    public function updatePackage(string $packageName): int
    {
        $args = ['update', $packageName];
        if ($this->withAllDependencies) {
            $args[] = '--with-all-dependencies';
        }

        return $this->run($args);
    }

    /**
     * @param list<string> $packageNames
     */
    public function updatePackages(array $packageNames): int
    {
        if ($packageNames === []) {
            return 0;
        }

        $args = ['update', ...$packageNames];
        if ($this->withAllDependencies) {
            $args[] = '--with-all-dependencies';
        }

        return $this->run($args);
    }

    /**
     * @param list<string> $args Arguments after "composer"
     */
    public function runRaw(array $args): int
    {
        return $this->run($args);
    }

    /**
     * @param list<string> $args
     */
    private function run(array $args): int
    {
        $command = array_merge([$this->composerBinary], $args);
        $env = $this->inheritedEnv();
        if ($this->composerEnvPath !== null) {
            $env['COMPOSER'] = $this->composerEnvPath;
        }

        $process = new Process($command, $this->workingDirectory, $env);
        $exit = $process->run(static function (string $type, string $buffer): void {
            if ($type === Process::OUT) {
                echo $buffer;
            } else {
                fwrite(STDERR, $buffer);
            }
        });
        if ($exit !== 0) {
            throw new RuntimeException(
                'Composer failed (' . $exit . '): ' . $process->getCommandLine() . "\n"
                . $process->getErrorOutput() . $process->getOutput()
            );
        }

        return $exit;
    }

    /**
     * @return array<string, string>
     */
    private function inheritedEnv(): array
    {
        $env = [];
        foreach (array_merge($_SERVER, $_ENV) as $k => $v) {
            if (is_string($k) && is_string($v)) {
                $env[$k] = $v;
            }
        }

        return $env;
    }
}
