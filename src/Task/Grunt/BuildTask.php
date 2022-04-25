<?php

namespace Intera\Surf\Task\Grunt;

use Intera\Surf\Service\PathReplacementTrait;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Executes a Grunt / bower build in a configurable directory.
 */
class BuildTask extends Task implements ShellCommandServiceAwareInterface
{
    use PathReplacementTrait;
    use ShellCommandServiceAwareTrait;

    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        if (empty($options['gruntRootPath'])) {
            throw new InvalidConfigurationException(
                'gruntRootPath option not specified for grunt build task.',
                1432127041
            );
        }

        if (isset($options['nodeName'])) {
            $node = $deployment->getNode($options['nodeName']);
            if ($node === null) {
                throw new InvalidConfigurationException(
                    sprintf('Node "%s" not found', $options['nodeName']),
                    1432127050
                );
            }
        }

        if (isset($options['forceLocalMode']) && $options['forceLocalMode']) {
            $node = $deployment->getNode('localhost');
        }

        $gruntRootPath = $this->replacePathPlaceholders($options['gruntRootPath'], $application, $deployment, $node);
        $gruntRootPath = escapeshellarg($gruntRootPath);
        $exitCodeIfRootDoesNotExist = !empty($options['skipMissingDirectory']) ? 0 : 1;
        $command = '

			if [ ! -d ' . $gruntRootPath . ' ]; then
				echo "Grunt root path ' . $gruntRootPath . ' does not exist."
				exit ' . $exitCodeIfRootDoesNotExist . ';
			fi

			# Break on errors
			set -e

			cd ' . $gruntRootPath . '
			if [ -f package.json ]; then npm install; fi
			if [ -f bower.json ]; then bower install; fi
			if [ -f Gruntfile.js ]; then grunt; fi
		';

        $this->shell->executeOrSimulate($command, $node, $deployment);
    }

    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        $this->execute($node, $application, $deployment, $options);
    }
}
