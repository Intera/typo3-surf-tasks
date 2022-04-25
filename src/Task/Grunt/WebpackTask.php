<?php

namespace Intera\Surf\Task\Grunt;

use Intera\Surf\Service\PathReplacementTrait;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;

/**
 * Installs Frontend / Build dependencies using yarn and runs a yarn build.
 */
class WebpackTask extends Task implements ShellCommandServiceAwareInterface
{
    use PathReplacementTrait;
    use ShellCommandServiceAwareTrait;

    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $webpackNode = $deployment->getNode('localhost');
        $webpackRootPath = '{workspacePath}';

        if (!empty($options['webpackRootPath'])) {
            $webpackRootPath = $options['webpackRootPath'];
        }

        if (!empty($options['webackUseRemoteNode'])) {
            $webpackNode = $node;
        }

        $webpackRootPath = rtrim(
            $this->replacePathPlaceholders($webpackRootPath, $application, $deployment, $node),
            '/'
        );

        $command = [
            'cd ' . escapeshellarg($webpackRootPath),
            'yarn install',
            'yarn production',
        ];

        $this->shell->executeOrSimulate($command, $webpackNode, $deployment);
    }

    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        $this->execute($node, $application, $deployment, $options);
    }
}
