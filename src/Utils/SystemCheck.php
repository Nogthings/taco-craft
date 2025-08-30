<?php

namespace TacoCraft\Utils;

use Symfony\Component\Process\Process;

class SystemCheck
{
    private ConsoleStyle $style;

    private array $requirements = [
        'docker' => [
            'command' => 'docker --version',
            'name' => 'Docker',
            'url' => 'https://docs.docker.com/get-docker/',
        ],
        'docker-compose' => [
            'command' => 'docker-compose --version',
            'name' => 'Docker Compose',
            'url' => 'https://docs.docker.com/compose/install/',
        ],
        'git' => [
            'command' => 'git --version',
            'name' => 'Git',
            'url' => 'https://git-scm.com/downloads',
        ],
    ];

    public function __construct(ConsoleStyle $style)
    {
        $this->style = $style;
    }

    public function verify(): bool
    {
        $results = [];
        $allPassed = true;

        foreach ($this->requirements as $key => $requirement) {
            $process = Process::fromShellCommandline($requirement['command']);
            $process->run();

            $installed = $process->isSuccessful();
            $version = $installed ? trim($process->getOutput()) : 'Not installed';

            $results[] = [
                $requirement['name'],
                $installed ? '✅' : '❌',
                $version,
            ];

            if (! $installed) {
                $allPassed = false;
            }
        }

        $this->style->table(
            ['Requirement', 'Status', 'Version'],
            $results
        );

        if (! $allPassed) {
            $this->style->error('Some requirements are missing!');
            $this->style->writeln('Please install the missing requirements:');

            foreach ($this->requirements as $key => $requirement) {
                $process = Process::fromShellCommandline($requirement['command']);
                $process->run();

                if (! $process->isSuccessful()) {
                    $this->style->writeln(sprintf(
                        '  - %s: %s',
                        $requirement['name'],
                        $requirement['url']
                    ));
                }
            }
        }

        return $allPassed;
    }
}
