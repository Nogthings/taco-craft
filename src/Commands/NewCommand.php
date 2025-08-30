<?php

namespace TacoCraft\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use TacoCraft\Services\ProjectGenerator;
use TacoCraft\Utils\ConsoleStyle;
use TacoCraft\Utils\SystemCheck;

class NewCommand extends Command
{
    protected static ?string $defaultName = 'new';

    protected static string $defaultDescription = 'Create a new Laravel project with Docker';

    private ProjectGenerator $generator;

    private ConsoleStyle $style;

    public function __construct()
    {
        parent::__construct();
        $this->generator = new ProjectGenerator;
    }

    protected function configure(): void
    {
        $this
            ->setName('new')
            ->setDescription(self::$defaultDescription)
            ->addArgument('name', InputArgument::REQUIRED, 'The name of your project')
            ->addOption('stack', 's', InputOption::VALUE_OPTIONAL, 'The stack to use (default, api, inertia, livewire, saas)', 'default')
            ->addOption('php', null, InputOption::VALUE_OPTIONAL, 'PHP version', '8.3')
            ->addOption('database', 'd', InputOption::VALUE_OPTIONAL, 'Database (mysql, pgsql, sqlite)', 'mysql')
            ->addOption('cache', 'c', InputOption::VALUE_OPTIONAL, 'Cache driver (redis, memcached)', 'redis')
            ->addOption('queue', null, InputOption::VALUE_NONE, 'Include queue worker')
            ->addOption('horizon', null, InputOption::VALUE_NONE, 'Include Laravel Horizon')
            ->addOption('telescope', null, InputOption::VALUE_NONE, 'Include Laravel Telescope')
            ->addOption('auth', 'a', InputOption::VALUE_NONE, 'Include authentication scaffolding')
            ->addOption('teams', 't', InputOption::VALUE_NONE, 'Include team support')
            ->addOption('api', null, InputOption::VALUE_NONE, 'Include API support with Sanctum')
            ->addOption('websockets', 'w', InputOption::VALUE_NONE, 'Include WebSocket support')
            ->addOption('minio', null, InputOption::VALUE_NONE, 'Include MinIO for S3-compatible storage')
            ->addOption('mailhog', null, InputOption::VALUE_NONE, 'Include MailHog for email testing')
            ->addOption('spicy', null, InputOption::VALUE_NONE, '🌶️ Include all the extras!')
            ->addOption('salsa', null, InputOption::VALUE_OPTIONAL, 'Theme color (verde, roja)', 'verde')
            ->addOption('git', 'g', InputOption::VALUE_NONE, 'Initialize git repository')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force create even if directory exists')
            ->setHelp($this->getHelpText());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->style = new ConsoleStyle($input, $output);

        // Show banner
        $this->style->banner();

        // Verify system requirements
        $this->style->section('🔍 Checking system requirements...');

        $systemCheck = new SystemCheck($this->style);
        if (! $systemCheck->verify()) {
            return Command::FAILURE;
        }

        $this->style->success('System requirements verified!');

        // Basic settings
        $config = $this->getProjectConfig($input);

        // Confirm configuration
        if (! $this->confirmConfiguration($config, $input)) {
            $this->style->warning('Project creation cancelled.');

            return Command::SUCCESS;
        }

        // Create project
        $this->style->section('🌮 Cooking your Laravel project...');

        try {
            $this->generator->generate($config, $this->style);

            $this->style->newLine();
            $this->style->success([
                '¡Órale! Your project is ready! 🎉',
                '',
                'Next steps:',
                sprintf('  cd %s', $config['name']),
                '  ./start.sh',
                '',
                'Or manually:',
                '  make install',
                '  make serve',
                '',
                sprintf('Your app will be available at: https://%s', $config['domain']),
                '',
                '¡Buen provecho! 🌮',
            ]);

            // Easter egg
            if ($config['spicy']) {
                $this->style->comment('🌶️ Spicy mode activated! Extra hot features included!');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->style->error([
                'Error creating project:',
                $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }

    private function getProjectConfig(InputInterface $input): array
    {
        $helper = $this->getHelper('question');
        $name = $input->getArgument('name');

        // Get project config
        $config = [
            'name' => $name,
            'stack' => $input->getOption('stack'),
            'php' => $input->getOption('php'),
            'database' => $input->getOption('database'),
            'cache' => $input->getOption('cache'),
            'salsa' => $input->getOption('salsa'),
            'spicy' => $input->getOption('spicy'),
        ];

        // Interactive mode if no options were specified
        if (! $input->getOption('force') && ! $this->hasAnyOption($input)) {
            $this->style->section('📋 Project Configuration');

            // Local domain
            $domainQuestion = new Question(
                sprintf('Local domain (default: <comment>%s.local</comment>): ', $name),
                $name.'.local'
            );
            $config['domain'] = $helper->ask($input, $this->style, $domainQuestion);

            // Developer email
            $emailQuestion = new Question('Developer email: ');
            $emailQuestion->setValidator(function ($email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Invalid email address');
                }

                return $email;
            });
            $config['email'] = $helper->ask($input, $this->style, $emailQuestion);

            // Stack
            $stackQuestion = new ChoiceQuestion(
                'Choose your stack:',
                ['default', 'api', 'inertia', 'livewire', 'saas'],
                'default'
            );
            $stackQuestion->setErrorMessage('Stack %s is invalid.');
            $config['stack'] = $helper->ask($input, $this->style, $stackQuestion);

            // Features
            $this->style->section('🎯 Select features');

            $features = [
                'auth' => 'Authentication scaffolding',
                'api' => 'API with Sanctum',
                'teams' => 'Team support',
                'queue' => 'Queue worker',
                'horizon' => 'Laravel Horizon',
                'telescope' => 'Laravel Telescope',
                'websockets' => 'WebSocket support',
                'minio' => 'MinIO storage',
                'mailhog' => 'MailHog for emails',
            ];

            foreach ($features as $key => $description) {
                $question = new ConfirmationQuestion(
                    sprintf('Include %s? (y/N) ', $description),
                    false
                );
                $config[$key] = $helper->ask($input, $this->style, $question);
            }
        } else {
            // Use values from options
            $config['domain'] = $name.'.local';
            $config['email'] = 'developer@'.$config['domain'];

            foreach (['auth', 'api', 'teams', 'queue', 'horizon', 'telescope', 'websockets', 'minio', 'mailhog', 'git'] as $option) {
                $config[$option] = $input->getOption($option);
            }
        }

        // If spicy mode, activate all features
        if ($config['spicy']) {
            foreach (['auth', 'api', 'teams', 'queue', 'horizon', 'telescope', 'websockets', 'minio', 'mailhog'] as $feature) {
                $config[$feature] = true;
            }
        }

        $config['git'] = $input->getOption('git');
        $config['force'] = $input->getOption('force');

        return $config;
    }

    private function confirmConfiguration(array $config, InputInterface $input): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        $this->style->section('📋 Configuration Summary');

        $this->style->table(
            ['Setting', 'Value'],
            [
                ['Project Name', $config['name']],
                ['Domain', $config['domain']],
                ['Stack', $config['stack']],
                ['PHP Version', $config['php']],
                ['Database', $config['database']],
                ['Cache', $config['cache']],
                ['Features', $this->getEnabledFeatures($config)],
            ]
        );

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with this configuration? (Y/n) ', true);

        return $helper->ask($input, $this->style, $question);
    }

    private function getEnabledFeatures(array $config): string
    {
        $features = [];
        $featureMap = [
            'auth' => '🔐 Auth',
            'api' => '🚀 API',
            'teams' => '👥 Teams',
            'queue' => '📦 Queue',
            'horizon' => '🎯 Horizon',
            'telescope' => '🔭 Telescope',
            'websockets' => '📡 WebSockets',
            'minio' => '💾 MinIO',
            'mailhog' => '📧 MailHog',
        ];

        foreach ($featureMap as $key => $label) {
            if (! empty($config[$key])) {
                $features[] = $label;
            }
        }

        return empty($features) ? 'None' : implode(', ', $features);
    }

    private function hasAnyOption(InputInterface $input): bool
    {
        $options = ['auth', 'api', 'teams', 'queue', 'horizon', 'telescope', 'websockets', 'minio', 'mailhog'];

        foreach ($options as $option) {
            if ($input->getOption($option)) {
                return true;
            }
        }

        return false;
    }

    private function getHelpText(): string
    {
        return <<<'HELP'
The <info>new</info> command creates a new Laravel project with Docker support:

  <info>tacocraft new my-app</info>

You can specify the stack and features:

  <info>tacocraft new my-app --stack=inertia --auth --api</info>

For a fully loaded project (spicy mode 🌶️):

  <info>tacocraft new my-app --spicy</info>

Available stacks:
  - <comment>default</comment>: Traditional Laravel with Blade
  - <comment>api</comment>: API-only with Sanctum
  - <comment>inertia</comment>: Laravel + Inertia.js + Vue 3
  - <comment>livewire</comment>: Laravel + Livewire + Alpine.js
  - <comment>saas</comment>: Full SAAS stack with MinIO, Redis, MySQL, Nginx optimized for MVP

HELP;
    }
}
