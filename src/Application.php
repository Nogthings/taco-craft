<?php

namespace TacoCraft;

use Symfony\Component\Console\Application as BaseApplication;
use TacoCraft\Commands\NewCommand;

class Application extends BaseApplication
{
    const VERSION = '1.0.0';

    const NAME = 'TacoCraft';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $this->addCommands([
            new NewCommand,
        ]);
    }

    public function getLongVersion(): string
    {
        return sprintf(
            '<info>%s</info> version <comment>%s</comment> - Made with 🌮 and ❤️ in Culiacan, Mexico',
            self::NAME,
            self::VERSION
        );
    }
}
