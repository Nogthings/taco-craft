<?php

namespace TacoCraft\Utils;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleStyle extends SymfonyStyle
{
    private array $colors = [
        'verde' => [
            'primary' => '#10B981',
            'secondary' => '#059669',
        ],
        'roja' => [
            'primary' => '#EF4444',
            'secondary' => '#DC2626',
        ],
    ];

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);
    }

    public function banner(): void
    {
        $banner = <<<'ASCII'
        
 _____                  ____            __ _   
|_   _|_ _  ___ ___    / ___|_ __ __ _ / _| |_ 
  | |/ _` |/ __/ _ \  | |   | '__/ _` | |_| __|
  | | (_| | (_| (_) | | |___| | | (_| |  _| |_ 
  |_|\__,_|\___\___/   \____|_|  \__,_|_|  \__|
                                                
    🌮 Craft Laravel Projects with Mexican Flavor 🌮
    
ASCII;

        $this->writeln($banner);
    }

    public function salsaVerde(string $message): void
    {
        $this->writeln(sprintf('<fg=green>%s</>', $message));
    }

    public function salsaRoja(string $message): void
    {
        $this->writeln(sprintf('<fg=red>%s</>', $message));
    }

    public function taco(string $message): void
    {
        $this->writeln(sprintf('🌮 %s', $message));
    }

    public function progressStart(int $max = 0): void
    {
        parent::progressStart($max);
    }

    public function progressStartWithMessage(string $message, int $max = 0): void
    {
        $this->writeln(sprintf('🌮 %s', $message));
        parent::progressStart($max);
    }

    public function section(string $message): void
    {
        $this->newLine();
        $this->writeln(sprintf('<comment>%s</comment>', $message));
        $this->writeln(str_repeat('─', strlen($message)));
    }

    public function success(string|array $message): void
    {
        if (is_string($message)) {
            $message = sprintf('✅ %s', $message);
        } elseif (is_array($message)) {
            $message[0] = sprintf('✅ %s', $message[0]);
        }

        parent::success($message);
    }

    public function warning(string|array $message): void
    {
        if (is_string($message)) {
            $message = sprintf('⚠️  %s', $message);
        }

        parent::warning($message);
    }

    public function error(string|array $message): void
    {
        if (is_string($message)) {
            $message = sprintf('❌ %s', $message);
        }

        parent::error($message);
    }
}
