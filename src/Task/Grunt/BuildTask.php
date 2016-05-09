<?php
namespace Intera\Surf\Task\Grunt;

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;

/**
 * Executes a Grunt build in a configurable directory.
 */
class BuildTask extends Task implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    /**
     * Executes a composer install in the configured directory.
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        if (empty($options['gruntRootPath'])) {
            throw new \TYPO3\Surf\Exception\InvalidConfigurationException(
                'gruntRootPath option not specified for grunt build task.',
                1432127041
            );
        }

        if (isset($options['nodeName'])) {
            $node = $deployment->getNode($options['nodeName']);
            if ($node === null) {
                throw new \TYPO3\Surf\Exception\InvalidConfigurationException(
                    sprintf('Node "%s" not found', $options['nodeName']),
                    1432127050
                );
            }
        }

        if (isset($options['forceLocalMode']) && $options['forceLocalMode']) {
            $node = $deployment->getNode('localhost');
        }

        $gruntRootPath = escapeshellarg($options['gruntRootPath']);
        $command = '

			if [ ! -d ' . $gruntRootPath . ' ]; then
				echo "Grunt root path $gruntRootPath does not exist."
				exit 1;
			fi

			cd ' . $gruntRootPath . ' &&
			npm install &&
			grunt
		';

        $this->shell->executeOrSimulate($command, $node, $deployment);
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
