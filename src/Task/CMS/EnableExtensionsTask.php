<?php
namespace Intera\Surf\Task\CMS;

use TYPO3\Surf\Application\TYPO3\CMS as TYPO3CMSApplication;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;
use TYPO3\Surf\Task\TYPO3\CMS\AbstractCliTask;

/**
 * This task enables all required extensions using the deployutils Extension.
 */
class EnableExtensionsTask extends AbstractCliTask
{
    /**
     * Execute this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @throws InvalidConfigurationException
     * @return void
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        if (!$application instanceof TYPO3CMSApplication) {
            throw new InvalidConfigurationException(
                'The EnableExtensionsTaks can only cused for TYPO3 CMS applications.'
            );
        }

        $deployutilsPackageExists = $this->packageExists('deployutils', $node, $application, $deployment, $options);
        if (!$deployutilsPackageExists) {
            throw new InvalidConfigurationException(
                'Extension "deployutils" is not found! Make sure it is available in your project, ' .
                'or remove this task in your deployment configuration!',
                1454009509
            );
        }

        $this->executeCliCommand(
            [
                'typo3/cli_dispatch.phpsh',
                'extbase',
                'deploylocal:loadextensions',
            ],
            $node,
            $application,
            $deployment,
            $options
        );
    }
}
