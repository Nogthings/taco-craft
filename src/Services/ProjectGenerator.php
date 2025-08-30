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

        $style->title('Generating project: '.$config['name']);

        try {
            // Validar requisitos previos
            $this->validateRequirements($style);

            // Verificar si el directorio existe
            if ($this->filesystem->exists($projectPath) && ! $config['force']) {
                throw new \RuntimeException(sprintf(
                    'Directory "%s" already exists. Use --force to overwrite.',
                    $config['name']
                ));
            }

            // Crear directorio del proyecto
            $style->writeln('📁 Creating project directory...');
            $this->createProjectDirectory($projectPath, $style);

            // Copiar template base
            $style->writeln('📋 Copying template files...');
            $this->templateManager->copyTemplate($config['stack'], $projectPath, $config);

            // Personalizar archivos
            $style->writeln('🔧 Customizing configuration...');
            $this->customizeFiles($projectPath, $config);

            // Preparar para Laravel (solo crear estructura, no ejecutar comandos)
            $style->writeln('📥 Preparing Laravel installation...');
            $this->prepareForLaravel($projectPath, $config, $style);

            // Configurar features adicionales
            if ($this->hasFeatures($config)) {
                $style->writeln('🎯 Preparing additional features...');
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

            $style->success('Project generated successfully!');
            $style->text('');
            $style->text('🚀 Next steps:');
            $style->text('1. cd '.$config['name']);
            $style->text('2. ./start.sh (or start.bat on Windows)');
            $style->text('3. ./setup.sh (or setup.ps1 on Windows) after containers are running');
            $style->text('4. Visit http://localhost');
            $style->text('');
            $style->text('📚 Check the README.md file in your project for detailed instructions.');

        } catch (\Exception $e) {
            $style->error('Error generating project: '.$e->getMessage());
            
            // Limpiar directorio si hay error
            if ($this->filesystem->exists($projectPath)) {
                $style->text('Cleaning up...');
                $this->filesystem->remove($projectPath);
            }
            
            throw $e;
        }
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
        // Para el template default, solo instalamos paquetes básicos
        // Los comandos artisan se ejecutarán después cuando el usuario inicie el proyecto
        
        if ($config['stack'] === 'saas') {
            $this->installSaasFeatures($projectPath, $config, $style);
            return;
        }

        // Para otros templates, solo preparamos los archivos de configuración
        $this->prepareFeatureConfigurations($projectPath, $config, $style);
    }

    private function runArtisanCommand(string $projectPath, array $command, ConsoleStyle $style): void
    {
        $srcPath = $projectPath.'/src';
        
        // Verificar que Laravel esté instalado
        if (!$this->filesystem->exists($srcPath.'/artisan')) {
            $style->warning('Laravel not installed yet, skipping: '.implode(' ', $command));
            return;
        }

        $fullCommand = ['php', 'artisan'];
        $fullCommand = array_merge($fullCommand, $command);

        $process = new Process($fullCommand, $srcPath);
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            $style->warning('Failed to run: '.implode(' ', $command).'. Error: '.$process->getErrorOutput());
        }
    }

    private function prepareFeatureConfigurations(string $projectPath, array $config, ConsoleStyle $style): void
    {
        $style->text('Preparing feature configurations...');
        
        // Crear archivo de configuración para features que se instalarán después
        $featuresConfig = [
            'auth' => $config['auth'] ?? false,
            'api' => $config['api'] ?? false,
            'horizon' => $config['horizon'] ?? false,
            'telescope' => $config['telescope'] ?? false,
        ];
        
        $configContent = "<?php\n\nreturn ".var_export($featuresConfig, true).";\n";
        
        $this->filesystem->dumpFile(
            $projectPath.'/tacocraft-features.php',
            $configContent
        );
        
        $style->text('Feature configurations prepared. Run setup script after starting containers.');
    }

    private function validateRequirements(ConsoleStyle $style): void
    {
        $requirements = [
            'docker' => 'Docker is required to run TacoCraft projects',
            'docker-compose' => 'Docker Compose is required to orchestrate containers',
        ];

        foreach ($requirements as $command => $message) {
            $process = new Process(['which', $command]);
            $process->run();

            if (!$process->isSuccessful()) {
                // Try 'where' command for Windows
                $process = new Process(['where', $command]);
                $process->run();
                
                if (!$process->isSuccessful()) {
                    throw new \RuntimeException($message);
                }
            }
        }
    }

    private function createProjectDirectory(string $projectPath, ConsoleStyle $style): void
    {
        try {
            $this->filesystem->mkdir($projectPath, 0755);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to create project directory: '.$e->getMessage());
        }
    }

    private function prepareForLaravel(string $projectPath, array $config, ConsoleStyle $style): void
    {
        $srcPath = $projectPath.'/src';

        // Crear directorio src si no existe
        if (!$this->filesystem->exists($srcPath)) {
            $this->filesystem->mkdir($srcPath, 0755);
        }

        // Crear archivo de configuración para la instalación posterior
        $laravelConfig = [
            'version' => '11.*',
            'prefer_dist' => true,
            'install_command' => 'composer create-project laravel/laravel . 11.* --prefer-dist',
        ];

        $configContent = "<?php\n\nreturn ".var_export($laravelConfig, true).";\n";
        
        $this->filesystem->dumpFile(
            $projectPath.'/tacocraft-laravel.php',
            $configContent
        );

        $style->text('Laravel installation prepared. Run start script to install.');
    }

    private function generateSSLCertificates(string $projectPath, array $config): void
    {
        $sslPath = $projectPath.'/docker/nginx/ssl';
        
        try {
            $this->filesystem->mkdir($sslPath, 0755);
        } catch (\Exception $e) {
            // Continue if directory creation fails
            return;
        }

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

        // Si falla OpenSSL, crear certificados dummy
        if (!$process->isSuccessful()) {
            $this->createDummySSLCertificates($sslPath, $config['domain']);
        }
    }

    private function createDummySSLCertificates(string $sslPath, string $domain): void
    {
        // Crear certificados dummy para desarrollo
        $dummyCert = "-----BEGIN CERTIFICATE-----\nDUMMY CERTIFICATE FOR DEVELOPMENT\n-----END CERTIFICATE-----\n";
        $dummyKey = "-----BEGIN PRIVATE KEY-----\nDUMMY PRIVATE KEY FOR DEVELOPMENT\n-----END PRIVATE KEY-----\n";

        file_put_contents($sslPath.'/'.$domain.'.crt', $dummyCert);
        file_put_contents($sslPath.'/'.$domain.'.key', $dummyKey);
    }

    private function generateSSLCertificatesOld(string $projectPath, array $config): void
    {
        $sslPath = $projectPath.'/docker/nginx/ssl';
        
        try {
            $this->filesystem->mkdir($sslPath, 0755);
        } catch (\Exception $e) {
            // Continue if directory creation fails
            return;
        }

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
echo "🚀 Starting {{PROJECT_NAME}}..."
echo "=============================="

# Iniciar contenedores
echo "📦 Starting Docker containers..."
docker-compose up -d

# Esperar a que los contenedores estén listos
echo "⏳ Waiting for containers to be ready..."
sleep 10

# Verificar si Laravel está instalado
if [ ! -f "src/artisan" ]; then
    echo "📥 Installing Laravel..."
    docker-compose exec app composer create-project laravel/laravel . --prefer-dist
fi

echo "✅ Project started successfully!"
echo ""
echo "🌐 Your application is available at:"
echo "   - HTTP: http://{{PROJECT_DOMAIN}}"
echo "   - HTTPS: https://{{PROJECT_DOMAIN}}"
echo ""
echo "📋 Next steps:"
echo "   1. Run ./setup.sh to install Laravel features"
echo "   2. Configure your .env file in src/ directory"
echo "   3. Start developing!"
echo ""
echo "📧 MailHog (email testing): http://localhost:8025"
echo "📊 MinIO Console: http://localhost:9001"
BASH;

        $startScript = str_replace(
            ['{{PROJECT_NAME}}', '{{PROJECT_DOMAIN}}'],
            [$config['name'], $config['domain']],
            $startScript
        );

        file_put_contents($projectPath.'/start.sh', $startScript);
        chmod($projectPath.'/start.sh', 0755);

        // start.bat para Windows
        $startBat = <<<'BAT'
@echo off
echo 🚀 Starting {{PROJECT_NAME}}...
echo ==============================
echo.

REM Iniciar contenedores
echo 📦 Starting Docker containers...
docker-compose up -d

REM Esperar a que los contenedores estén listos
echo ⏳ Waiting for containers to be ready...
timeout /t 10 /nobreak > nul

REM Verificar si Laravel está instalado
if not exist "src\artisan" (
    echo 📥 Installing Laravel...
    docker-compose exec app composer create-project laravel/laravel . --prefer-dist
)

echo ✅ Project started successfully!
echo.
echo 🌐 Your application is available at:
echo    - HTTP: http://{{PROJECT_DOMAIN}}
echo    - HTTPS: https://{{PROJECT_DOMAIN}}
echo.
echo 📋 Next steps:
echo    1. Run setup.ps1 to install Laravel features
echo    2. Configure your .env file in src\ directory
echo    3. Start developing!
echo.
echo 📧 MailHog (email testing): http://localhost:8025
echo 📊 MinIO Console: http://localhost:9001
echo.
pause
BAT;

        $startBat = str_replace(
            ['{{PROJECT_NAME}}', '{{PROJECT_DOMAIN}}'],
            [$config['name'], $config['domain']],
            $startBat
        );

        file_put_contents($projectPath.'/start.bat', $startBat);

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
                try {
                    chmod($fullPath, 0775);
                    // También establecer permisos recursivamente
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::SELF_FIRST
                    );
                    
                    foreach ($iterator as $item) {
                        if ($item->isDir()) {
                            chmod($item->getRealPath(), 0775);
                        } else {
                            chmod($item->getRealPath(), 0664);
                        }
                    }
                } catch (\Exception $e) {
                    // Continue if permission setting fails
                }
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
