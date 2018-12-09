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
 * Installs Frontend / Build dependencies using yarn and runs a Grunt build.
 */
class WebpackTask extends Task implements ShellCommandServiceAwareInterface
{
    use PathReplacementTrait;
    use ShellCommandServiceAwareTrait;

    /**
     * Executes a composer install in the configured directory.
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     */
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
            $this->replacePathPlaceholders($webpackRootPath, $application, $deployment),
            '/'
        );

        $command = [
            'cd ' . escapeshellarg($webpackRootPath),
            'yarn install',
            'yarn production',
        ];

        /** @noinspection PhpParamsInspection */
        $this->shell->executeOrSimulate($command, $webpackNode, $deployment);
    }

    /**
     * Simulate this task (e.g. by logging commands it would execute)
     *
     * @param  Node $node
     * @param  Application $application
     * @param  Deployment $deployment
     * @param  array $options
     * @return void
     */
    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->execute($node, $application, $deployment, $options);
    }
}
