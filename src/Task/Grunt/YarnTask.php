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
 * Installs Frontend / Build dependencies using yarn and runs a Grunt build.
 */
class YarnTask extends Task implements ShellCommandServiceAwareInterface
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
     * @throws InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        if (empty($options['projectExtensionPath'])) {
            throw new InvalidConfigurationException(
                'projectExtensionPath option not specified for grunt yarn task.',
                1432127041
            );
        }

        $projectExtensionPath = rtrim(
            $this->replacePathPlaceholders($options['projectExtensionPath'], $application, $deployment),
            '/'
        );

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

        $gruntRootPath = $projectExtensionPath . '/Resources/Private/Build';
        $gruntRootPath = escapeshellarg($gruntRootPath);

        $frontendRootPath = $projectExtensionPath . '/Resources/Public/Contrib';
        $frontendRootPath = escapeshellarg($frontendRootPath);

        $command = '

			if [ ! -d ' . $gruntRootPath . ' ]; then
				echo "Grunt root path ' . $gruntRootPath . ' does not exist."
				exit 1;
			fi
			
			if [ ! -d ' . $frontendRootPath . ' ]; then
				echo "Frontend root path ' . $frontendRootPath . ' does not exist."
				exit 1;
			fi

			# Break on errors
			set -e

            cd ' . $frontendRootPath . ' && yarn install
            
			cd ' . $gruntRootPath . ' && yarn install
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
