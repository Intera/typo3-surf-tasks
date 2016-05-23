<?php
namespace Intera\Surf\Task\Deployutils;

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * Abstract task for calling commands of the deployutils Extension.
 */
abstract class AbstractDeployCommandTask extends \TYPO3\Surf\Task\TYPO3\CMS\AbstractCliTask
{
    /**
     * Retuns the additional arguments that should be passed to the cli_dispatch.phpsh script.
     *
     * @return array
     */
    abstract protected function getCliArguments();

    /**
     * Execute this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return void
     * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        /* @var \TYPO3\Surf\Application\TYPO3\CMS $application */
        $this->ensureApplicationIsTypo3Cms($application);

        if (!$this->packageExists('deployutils', $node, $application, $deployment)) {
            $deployment->getLogger()->warning(
                'The Extension "deployutils" was not found! Make sure one is available in your project, or remove' .
                ' this task (' . __CLASS__ . ') from your deployment configuration!'
            );
        }

        $cliArguments = array_merge(['typo3/cli_dispatch.phpsh', 'extbase'], $this->getCliArguments());

        $this->executeCliCommand($cliArguments, $node, $application, $deployment, $options);
    }
}