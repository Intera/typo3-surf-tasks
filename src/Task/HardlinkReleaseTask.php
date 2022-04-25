<?php

namespace Intera\Surf\Task;

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;

/**
 * A hardlink task for switching over the current directory to the new release.
 */
class HardlinkReleaseTask extends Task implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $releaseIdentifier = $deployment->getReleaseIdentifier();
        $releasesPath = $application->getReleasesPath();
        $this->shell->executeOrSimulate(
            [
                'cd ' . $releasesPath,
                'rm -f ./next',
                'cp -al ./' . $releaseIdentifier . ' ./next',
                'rm -Rf ./previous',
                'if [ -e ./current ]; then mv ./current ./previous; fi',
                'mv ./next ./current',
            ],
            $node,
            $deployment
        );
        $deployment->getLogger()->notice(
            '<success>Node "' . $node->getName() . '" '
            . ($deployment->isDryRun() ? 'would be' : 'is') . ' live!</success>'
        );
    }

    public function rollback(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        $releasesPath = $application->getReleasesPath();
        $this->shell->execute(
            [
                'cd ' . $releasesPath,
                'rm -Rf ./current',
                'mv ./previous ./current',
            ],
            $node,
            $deployment,
            true
        );
    }

    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        $this->execute($node, $application, $deployment, $options);
    }
}
