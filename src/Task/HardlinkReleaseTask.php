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

    /**
     * Executes this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return void
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $releaseIdentifier = $deployment->getReleaseIdentifier();
        $releasesPath = $application->getReleasesPath();
        $this->shell->executeOrSimulate(
            'cd ' . $releasesPath . ' && rm -Rf ./previous && if [ -e ./current ]; then mv ./current ./previous; fi'
            . ' && cp -al ./' . $releaseIdentifier . ' ./current && rm -f ./next',
            $node,
            $deployment
        );
        $deployment->getLogger()->notice(
            '<success>Node "' . $node->getName() . '" ' . ($deployment->isDryRun(
            ) ? 'would be' : 'is') . ' live!</success>'
        );
    }

    /**
     * Rollback this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return void
     */
    public function rollback(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $releasesPath = $application->getReleasesPath();
        $this->shell->execute(
            'cd ' . $releasesPath . ' && rm -Rf ./current && mv ./previous ./current',
            $node,
            $deployment,
            true
        );
    }

    /**
     * Simulate this task
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @return void
     */
    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->execute($node, $application, $deployment, $options);
    }
}
