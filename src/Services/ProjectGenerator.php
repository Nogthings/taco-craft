<?php

namespace TacoCraft\Services;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use TacoCraft\Templates\TemplateManager;
use TacoCraft\Utils\ConsoleStyle;

class ProjectGenerator
{
    private Filesystem $filesystem;

    private TemplateManager $templateManager;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
        $this->templateManager = new TemplateManager;
    }

    public function generate(array $config, ConsoleStyle $style): void
    {
        $projectPath = getcwd().'/'.$config['name'];

        // Verificar si el directorio existe
        if ($this->filesystem->exists($projectPath) && ! $config['force']) {
            throw new \RuntimeException(sprintf(
                'Directory "%s" already exists. Use --force to overwrite.',
                $config['name']
            ));
        }

        // Crear directorio del proyecto
        $style->writeln('📁 Creating project directory...');
        $this->filesystem->mkdir($projectPath);

        // Copiar template base
        $style->writeln('📋 Copying template files...');
        $this->templateManager->copyTemplate($config['stack'], $projectPath, $config);

        // Personalizar archivos
        $style->writeln('🔧 Customizing configuration...');
        $this->customizeFiles($projectPath, $config);

        // Instalar Laravel
        $style->writeln('📥 Installing Laravel...');
        $this->installLaravel($projectPath, $config, $style);

        // Configurar features adicionales
        if ($this->hasFeatures($config)) {
            $style->writeln('🎯 Installing additional features...');
            $this->installFeatures($projectPath, $config, $style);
        }

        // Generar certificados SSL
        $style->writeln('🔐 Generating SSL certificates...');
        $this->generateSSLCertificates($projectPath, $config);

        // Inicializar Git si se solicita
        if ($config['git']) {
            $style->writeln('📚 Initializing Git repository...');
            $this->initializeGit($projectPath);
        }

        // Crear scripts de inicio
        $style->writeln('🚀 Creating startup scripts...');
        $this->createStartupScripts($projectPath, $config);

        // Configurar permisos
        $this->setPermissions($projectPath);
    }

    private function customizeFiles(string $projectPath, array $config): void
    {
        $replacements = [
            '{{PROJECT_NAME}}' => $config['name'],
            '{{PROJECT_DOMAIN}}' => $config['domain'],
            '{{DEVELOPER_EMAIL}}' => $config['email'],
            '{{PHP_VERSION}}' => $config['php'],
            '{{DATABASE}}' => $config['database'],
            '{{CACHE_DRIVER}}' => $config['cache'],
            '{{THEME_COLOR}}' => $config['salsa'],
        ];

        // Archivos a personalizar
        $files = [
            'docker-compose.yml',
            'docker/nginx/default.conf',
            '.env.example',
            'README.md',
            'Makefile',
        ];

        foreach ($files as $file) {
            $filePath = $projectPath.'/'.$file;
            if ($this->filesystem->exists($filePath)) {
                $content = file_get_contents($filePath);
                if ($content !== false) {
                    $content = str_replace(
                        array_keys($replacements),
                        array_values($replacements),
                        $content
                    );
                    file_put_contents($filePath, $content);
                }
            }
        }
    }

    private function installLaravel(string $projectPath, array $config, ConsoleStyle $style): void
    {
        $srcPath = $projectPath.'/src';

        // Limpiar y crear directorio src
        if ($this->filesystem->exists($srcPath)) {
            $this->filesystem->remove($srcPath);
        }
        $this->filesystem->mkdir($srcPath);

        // Comando para instalar Laravel usando Composer local
        $command = [
            'composer', 'create-project',
            'laravel/laravel', $srcPath, '11.*',
            '--prefer-dist',
        ];

        // Cambiar al directorio padre para ejecutar el comando
        $originalDir = getcwd();
        chdir(dirname($srcPath));

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) use ($style) {
            $style->write($buffer);
        });

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to install Laravel: '.$process->getErrorOutput());
        }
    }

    private function installFeatures(string $projectPath, array $config, ConsoleStyle $style): void
    {
        $commands = [];

        // Configuraciones específicas para SAAS
        if ($config['stack'] === 'saas') {
            $this->installSaasFeatures($projectPath, $config, $style);

            return;
        }

        // Preparar comandos según features
        if ($config['auth']) {
            $commands[] = ['breeze:install', 'blade'];
        }

        if ($config['api']) {
            $commands[] = ['install:api'];
        }

        if ($config['horizon']) {
            $commands[] = ['horizon:install'];
        }

        if ($config['telescope']) {
            $commands[] = ['telescope:install'];
        }

        // Ejecutar comandos
        foreach ($commands as $cmd) {
            $this->runArtisanCommand($projectPath, $cmd, $style);
        }
    }

    private function runArtisanCommand(string $projectPath, array $command, ConsoleStyle $style): void
    {
        $fullCommand = [
            'docker-compose',
            '-f', $projectPath.'/docker-compose.yml',
            'run', '--rm', 'app',
            'php', 'artisan',
        ];

        $fullCommand = array_merge($fullCommand, $command);

        $process = new Process($fullCommand);
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            $style->warning('Failed to run: '.implode(' ', $command));
        }
    }

    private function generateSSLCertificates(string $projectPath, array $config): void
    {
        $sslPath = $projectPath.'/docker/nginx/ssl';
        $this->filesystem->mkdir($sslPath);

        $command = [
            'openssl', 'req', '-x509', '-nodes',
            '-days', '365',
            '-newkey', 'rsa:2048',
            '-keyout', $sslPath.'/'.$config['domain'].'.key',
            '-out', $sslPath.'/'.$config['domain'].'.crt',
            '-subj', sprintf(
                '/C=MX/ST=Mexico/L=Mexico City/O=TacoCraft/CN=%s',
                $config['domain']
            ),
        ];

        $process = new Process($command);
        $process->run();
    }

    private function initializeGit(string $projectPath): void
    {
        $commands = [
            ['git', 'init'],
            ['git', 'add', '.'],
            ['git', 'commit', '-m', '🌮 Initial commit - Created with TacoCraft'],
        ];

        foreach ($commands as $command) {
            $process = new Process($command, $projectPath);
            $process->run();
        }
    }

    private function createStartupScripts(string $projectPath, array $config): void
    {
        // start.sh
        $startScript = <<<'BASH'
#!/bin/bash
echo "🌮 Starting {{PROJECT_NAME}}..."
make install
make serve
echo "✅ Your application is ready at: https://{{PROJECT_DOMAIN}}"
BASH;

        $startScript = str_replace(
            ['{{PROJECT_NAME}}', '{{PROJECT_DOMAIN}}'],
            [$config['name'], $config['domain']],
            $startScript
        );

        file_put_contents($projectPath.'/start.sh', $startScript);
        chmod($projectPath.'/start.sh', 0755);

        // stop.sh
        $stopScript = <<<'BASH'
#!/bin/bash
echo "🛑 Stopping services..."
make down
echo "✅ Services stopped"
BASH;

        file_put_contents($projectPath.'/stop.sh', $stopScript);
        chmod($projectPath.'/stop.sh', 0755);
    }

    private function setPermissions(string $projectPath): void
    {
        // Asegurar permisos correctos
        $directories = [
            '/src/storage',
            '/src/bootstrap/cache',
        ];

        foreach ($directories as $dir) {
            $fullPath = $projectPath.$dir;
            if ($this->filesystem->exists($fullPath)) {
                chmod($fullPath, 0777);
            }
        }
    }

    private function installSaasFeatures(string $projectPath, array $config, ConsoleStyle $style): void
    {
        $style->writeln('🏢 Installing SAAS features...');

        $commands = [
            // Instalar autenticación (requerido para SAAS)
            ['breeze:install', 'blade'],
            // Instalar API (requerido para SAAS)
            ['install:api'],
            // Instalar Sanctum para API authentication
            ['vendor:publish', '--provider=Laravel\\Sanctum\\SanctumServiceProvider'],
        ];

        // Features opcionales pero recomendadas para SAAS
        if ($config['horizon']) {
            $commands[] = ['horizon:install'];
        }

        if ($config['telescope']) {
            $commands[] = ['telescope:install'];
        }

        // Ejecutar comandos
        foreach ($commands as $cmd) {
            $this->runArtisanCommand($projectPath, $cmd, $style);
        }

        // Instalar paquetes adicionales para SAAS
        $this->installSaasPackages($projectPath, $config, $style);

        // Configurar multi-tenancy
        $this->configureSaasMultiTenancy($projectPath, $config, $style);
    }

    private function installSaasPackages(string $projectPath, array $config, ConsoleStyle $style): void
    {
        $style->writeln('📦 Installing SAAS packages...');

        $packages = [
            'spatie/laravel-permission',
            'spatie/laravel-activitylog',
            'spatie/laravel-backup',
            'spatie/laravel-health',
            'league/flysystem-aws-s3-v3',
            'predis/predis',
            'intervention/image',
        ];

        foreach ($packages as $package) {
            $this->runComposerCommand($projectPath, ['require', $package], $style);
        }
    }

    private function configureSaasMultiTenancy(string $projectPath, array $config, ConsoleStyle $style): void
    {
        $style->writeln('🏢 Configuring multi-tenancy...');

        // Crear migration para tenants
        $this->runArtisanCommand($projectPath, [
            'make:migration', 'create_tenants_table',
        ], $style);

        // Crear modelo Tenant
        $this->runArtisanCommand($projectPath, [
            'make:model', 'Tenant', '-m',
        ], $style);

        // Crear middleware para tenant
        $this->runArtisanCommand($projectPath, [
            'make:middleware', 'TenantMiddleware',
        ], $style);
    }

    private function runComposerCommand(string $projectPath, array $command, ConsoleStyle $style): void
    {
        $fullCommand = [
            'docker-compose',
            '-f', $projectPath.'/docker-compose.yml',
            'run', '--rm', 'app',
            'composer',
        ];

        $fullCommand = array_merge($fullCommand, $command);

        $process = new Process($fullCommand);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $style->warning('Failed to run composer: '.implode(' ', $command));
        }
    }

    private function hasFeatures(array $config): bool
    {
        // Para SAAS siempre hay features que instalar
        if ($config['stack'] === 'saas') {
            return true;
        }

        $features = ['auth', 'api', 'horizon', 'telescope', 'teams', 'websockets'];

        foreach ($features as $feature) {
            if (! empty($config[$feature])) {
                return true;
            }
        }

        return false;
    }
}
