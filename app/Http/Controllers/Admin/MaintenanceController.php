<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Composer\InstalledVersions;
use Symfony\Component\Process\Process;

class MaintenanceController extends Controller
{
    private ?array $processEnvironment = null;

    public function index()
    {
        abort_unless(
            auth()->check() && auth()->user()->isSuperAdmin(),
            403
        );

        $audit = [
            'generated_at' => now('Europe/Paris'),

            'app' => [
                'name' => config('app.name'),
                'environment' => app()->environment(),
                'debug' => (bool) config('app.debug'),
                'timezone' => config('app.timezone'),
                'url' => config('app.url'),
            ],

            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'binary' => PHP_BINARY,
            ],

            'laravel' => [
                'version' => app()->version(),
            ],

            'git' => $this->gitAudit(),

            'composer' => [
                'version' => $this->runCommand(
                    'composer --version --no-ansi',
                    15
                ),

                'packages' => [
                    'laravel/framework' => $this->composerPackage(
                        'laravel/framework'
                    ),

                    'phpunit/phpunit' => $this->composerPackage(
                        'phpunit/phpunit'
                    ),

                    'laravel/tinker' => $this->composerPackage(
                        'laravel/tinker'
                    ),
                ],

                'outdated' => $this->composerOutdated(),
                'audit' => $this->composerAudit(),
            ],

            'node' => [
                'version' => $this->runCommand(
                    'node --version',
                    10
                ),

                'npm' => $this->runCommand(
                    'npm --version',
                    10
                ),
            ],

            'npm' => [
                'available' => file_exists(
                    base_path('package.json')
                ),

                'outdated' => $this->npmOutdated(),
                'audit' => $this->npmAudit(),
            ],

            'process_environment' => [
                'path' => $this->processEnvironment()['PATH']
                    ?? null,

                'home' => $this->processEnvironment()['HOME']
                    ?? null,

                'composer_home' => $this
                    ->processEnvironment()['COMPOSER_HOME']
                    ?? null,

                'npm_cache' => $this
                    ->processEnvironment()['npm_config_cache']
                    ?? null,
            ],
        ];

        return view(
            'admin.maintenance.index',
            compact('audit')
        );
    }

    private function gitAudit(): array
    {
        return [
            'branch' => $this->runCommand(
                'git rev-parse --abbrev-ref HEAD',
                10
            ),

            'status' => $this->runCommand(
                'git status --porcelain',
                10
            ),
        ];
    }

    private function composerPackage(string $package): array
    {
        $lockedPackage = $this->lockedComposerPackage(
            $package
        );

        $isDevDependency = $this
            ->composerPackageIsDevDependency($package);

        $isInstalled = InstalledVersions::isInstalled(
            $package
        );

        if ($isInstalled) {
            $version = InstalledVersions::getPrettyVersion(
                $package
            );

            if (! $version) {
                $version = InstalledVersions::getVersion(
                    $package
                );
            }

            return [
                'command' => 'Composer\\InstalledVersions',

                'success' => true,
                'exit_code' => 0,

                'output' => '',
                'error_output' => '',

                'package' => $package,

                'version' => $version
                    ?: ($lockedPackage['version'] ?? 'Version inconnue'),

                'installed' => true,
                'dev_only' => $isDevDependency,
                'expected_absence' => false,
            ];
        }

        /*
         * Une dépendance require-dev est volontairement absente
         * lorsqu’un déploiement utilise composer install --no-dev.
         *
         * Ce n’est pas une erreur de production. On affiche la version
         * présente dans composer.lock sans exiger son installation.
         */
        if ($isDevDependency && $lockedPackage) {
            $lockedVersion = $lockedPackage['version']
                ?? null;

            return [
                'command' => 'Lecture de composer.lock',

                'success' => true,
                'exit_code' => 0,

                'output' => '',
                'error_output' => '',

                'package' => $package,

                'version' => $lockedVersion
                    ? 'Non installé en production — verrouillé : '
                        .$lockedVersion
                    : 'Non installé en production',

                'installed' => false,
                'dev_only' => true,
                'expected_absence' => true,
            ];
        }

        /*
         * Si une dépendance obligatoire apparaît dans composer.lock,
         * mais n’est pas chargée par Composer, le répertoire vendor
         * n’est probablement pas synchronisé avec composer.lock.
         */
        if ($lockedPackage) {
            return [
                'command' => 'Composer\\InstalledVersions / composer.lock',

                'success' => false,
                'exit_code' => 1,

                'output' => '',

                'error_output' => 'Le paquet est présent dans '
                    .'composer.lock mais n’est pas installé dans vendor. '
                    .'Exécute composer install '
                    .'--no-dev --optimize-autoloader.',

                'package' => $package,
                'version' => 'Non installé',

                'installed' => false,
                'dev_only' => false,
                'expected_absence' => false,
            ];
        }

        return [
            'command' => 'Composer\\InstalledVersions / composer.lock',

            'success' => false,
            'exit_code' => 1,

            'output' => '',

            'error_output' => 'Le paquet n’est ni installé '
                .'ni présent dans composer.lock.',

            'package' => $package,
            'version' => 'Indisponible',

            'installed' => false,
            'dev_only' => $isDevDependency,
            'expected_absence' => false,
        ];
    }

    private function composerPackageIsDevDependency(
        string $package
    ): bool {
        $composerJson = $this->readJsonFile(
            base_path('composer.json')
        );

        $require = $composerJson['require'] ?? [];
        $requireDev = $composerJson['require-dev'] ?? [];

        return array_key_exists($package, $requireDev)
            && ! array_key_exists($package, $require);
    }

    private function lockedComposerPackage(
        string $package
    ): ?array {
        $composerLock = $this->readJsonFile(
            base_path('composer.lock')
        );

        foreach (
            [
                'packages',
                'packages-dev',
            ] as $section
        ) {
            foreach (
                $composerLock[$section] ?? [] as $lockedPackage
            ) {
                if (
                    ($lockedPackage['name'] ?? null)
                    !== $package
                ) {
                    continue;
                }

                return [
                    'name' => $package,

                    'version' => $lockedPackage['pretty_version']
                        ?? $lockedPackage['version']
                        ?? null,

                    'section' => $section,

                    'dev_only' => $section === 'packages-dev',
                ];
            }
        }

        return null;
    }

    private function readJsonFile(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            return [];
        }

        $decoded = json_decode(
            $contents,
            true
        );

        if (
            json_last_error() !== JSON_ERROR_NONE
            || ! is_array($decoded)
        ) {
            return [];
        }

        return $decoded;
    }

    private function composerOutdated(): array
    {
        $result = $this->runJsonCommand(
            'composer outdated '
                .'--direct '
                .'--format=json '
                .'--no-ansi '
                .'--no-interaction',
            60
        );

        $result['packages'] = array_values(
            $result['json']['installed'] ?? []
        );

        return $result;
    }

    private function composerAudit(): array
    {
        $result = $this->runJsonCommand(
            'composer audit '
                .'--format=json '
                .'--no-ansi '
                .'--no-interaction',
            60
        );

        $advisories = $result['json']['advisories']
            ?? [];

        $count = 0;

        foreach ($advisories as $items) {
            if (! is_array($items)) {
                continue;
            }

            $count += array_is_list($items)
                ? count($items)
                : 1;
        }

        $result['advisory_count'] = $count;

        $result['abandoned'] = $result['json']['abandoned']
            ?? [];

        return $result;
    }

    private function npmOutdated(): array
    {
        if (! file_exists(base_path('package.json'))) {
            return [
                'skipped' => true,
                'reason' => 'Aucun fichier package.json trouvé.',
                'packages' => [],
            ];
        }

        $result = $this->runJsonCommand(
            'npm outdated --json',
            60
        );

        $packages = [];

        foreach (
            $result['json'] ?? [] as $name => $details
        ) {
            if (! is_array($details)) {
                continue;
            }

            $packages[] = [
                'name' => $name,

                'current' => $details['current']
                    ?? null,

                'wanted' => $details['wanted']
                    ?? null,

                'latest' => $details['latest']
                    ?? null,

                'type' => $details['type']
                    ?? null,
            ];
        }

        $result['packages'] = $packages;

        return $result;
    }

    private function npmAudit(): array
    {
        if (! file_exists(base_path('package.json'))) {
            return [
                'skipped' => true,
                'reason' => 'Aucun fichier package.json trouvé.',
            ];
        }

        return $this->runJsonCommand(
            'npm audit --json',
            60
        );
    }

    private function runJsonCommand(
        string $command,
        int $timeout = 30
    ): array {
        $result = $this->runCommand(
            $command,
            $timeout
        );

        $raw = trim(
            $result['output']
                ?: $result['error_output']
        );

        $result['json'] = [];
        $result['json_error'] = null;

        if ($raw === '') {
            return $result;
        }

        $decoded = json_decode(
            $raw,
            true
        );

        if (
            json_last_error() === JSON_ERROR_NONE
            && is_array($decoded)
        ) {
            $result['json'] = $decoded;
        } else {
            $result['json_error'] = json_last_error_msg();
        }

        return $result;
    }

    private function runCommand(
        string $command,
        int $timeout = 30
    ): array {
        try {
            $process = Process::fromShellCommandline(
                $command,
                base_path(),
                $this->processEnvironment(),
                null,
                $timeout
            );

            $process->run();

            return [
                'command' => $command,

                'success' => $process->isSuccessful(),

                'exit_code' => $process->getExitCode(),

                'output' => trim(
                    $process->getOutput()
                ),

                'error_output' => trim(
                    $process->getErrorOutput()
                ),
            ];
        } catch (\Throwable $exception) {
            return [
                'command' => $command,
                'success' => false,
                'exit_code' => null,
                'output' => '',
                'error_output' => $exception->getMessage(),
            ];
        }
    }

    private function processEnvironment(): array
    {
        if ($this->processEnvironment !== null) {
            return $this->processEnvironment;
        }

        $phpBinaryDirectory = dirname(
            PHP_BINARY
        );

        $phpInstallationDirectory = dirname(
            $phpBinaryDirectory
        );

        $homeDirectory = storage_path(
            'app/maintenance-home'
        );

        $composerHome = storage_path(
            'app/maintenance-composer-home'
        );

        $npmCache = storage_path(
            'app/maintenance-npm-cache'
        );

        $this->ensureDirectory(
            $homeDirectory
        );

        $this->ensureDirectory(
            $composerHome
        );

        $this->ensureDirectory(
            $npmCache
        );

        $paths = [
            $phpBinaryDirectory,
            $phpInstallationDirectory.'/bin',
            $phpInstallationDirectory.'/sbin',
            '/opt/homebrew/bin',
            '/opt/homebrew/sbin',
            '/usr/local/bin',
            '/usr/local/sbin',
            '/usr/bin',
            '/bin',
            '/usr/sbin',
            '/sbin',
            getenv('PATH') ?: '',
        ];

        $paths = array_values(
            array_unique(
                array_filter($paths)
            )
        );

        $this->processEnvironment = [
            'PATH' => implode(':', $paths),

            'HOME' => $homeDirectory,

            'COMPOSER_HOME' => $composerHome,

            'COMPOSER_ALLOW_SUPERUSER' => '1',

            'COMPOSER_NO_INTERACTION' => '1',

            'npm_config_cache' => $npmCache,
            'NPM_CONFIG_CACHE' => $npmCache,

            'CI' => '1',
        ];

        return $this->processEnvironment;
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir(
            $path,
            0775,
            true
        );
    }
}
