<?php

namespace Intera\Surf\Task\Generic;

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;

class CreateSharedDirectoriesTask extends Task implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        if (!isset($options['directories']) || !is_array($options['directories']) || $options['directories'] === []) {
            return;
        }

        $commands = ['cd ' . $application->getSharedPath()];
        foreach ($options['directories'] as $path) {
            $commands[] = 'mkdir -p ' . $path;
        }

        $this->shell->executeOrSimulate($commands, $node, $deployment);
    }

    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        $this->execute($node, $application, $deployment, $options);
    }
}
