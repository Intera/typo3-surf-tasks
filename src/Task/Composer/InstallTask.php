<?php
namespace De\Intera\Surf\Task\Composer;

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * Installs the composer packages based on a composer.json file in the projects root folder
 */
class InstallTask extends \TYPO3\Surf\Task\Composer\InstallTask
{
    /**
     * Executes a composer install in the configured directory.
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        if (!empty($options['composerRootPath'])) {
            $composerRootPath = $options['composerRootPath'];
        } elseif (isset($options['useApplicationWorkspace']) && $options['useApplicationWorkspace'] === true) {
            $composerRootPath = $deployment->getWorkspacePath($application);
        } else {
            $composerRootPath = $deployment->getApplicationReleasePath($application);
        }

        if (isset($options['nodeName'])) {
            $node = $deployment->getNode($options['nodeName']);
            if ($node === null) {
                throw new \TYPO3\Surf\Exception\InvalidConfigurationException(sprintf('Node "%s" not found', $options['nodeName']), 1369759412);
            }
        }

        if (isset($options['forceLocalMode']) && $options['forceLocalMode']) {
            $node = $deployment->getNode('localhost');
        }

        if ($this->composerManifestExists($composerRootPath, $node, $deployment)) {
            $command = $this->buildComposerInstallCommand($composerRootPath, $options);
            $command .= ' --quiet';
            $this->shell->executeOrSimulate($command, $node, $deployment);
        }
    }

    /**
     * Build the composer command to "install --no-dev" in the given $path.
     *
     * @param string $manifestPath
     * @param array $options
     * @return string
     * @throws \TYPO3\Surf\Exception\TaskExecutionException
     */
    protected function buildComposerInstallCommand($manifestPath, array $options)
    {
        if (!isset($options['composerCommandPath'])) {
            throw new \TYPO3\Surf\Exception\TaskExecutionException('Composer command not found. Set the composerCommandPath option.', 1349163257);
        }
        $arguments = '--no-ansi --no-interaction --no-dev --no-progress';
        if (isset($options['ignorePlatformRequirements']) && $options['ignorePlatformRequirements']) {
            $arguments .= ' --ignore-platform-reqs';
        }
        return sprintf('cd %s && %s install ' . $arguments, escapeshellarg($manifestPath), escapeshellcmd($options['composerCommandPath']));
    }
}