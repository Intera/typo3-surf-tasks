<?php
namespace Intera\Surf\Task\Composer;

/**
 * Installs the composer packages based on a composer.json file in the projects root folder
 */
class InstallTask extends \TYPO3\Surf\Task\Composer\InstallTask
{
    /**
     * Build the composer command to "install --no-dev" in the given $path.
     *
     * @param string $manifestPath
     * @param array $options
     * @return array
     * @throws \TYPO3\Surf\Exception\TaskExecutionException
     */
    protected function buildComposerInstallCommands($manifestPath, array $options)
    {
        $command = parent::buildComposerInstallCommands($manifestPath, $options);
        $composerFlags = isset($options['composerFlags']) ? $options['composerFlags'] . ' ' : '';
        if (!empty($options['ignorePlatformRequirements'])) {
            $composerFlags .= '--ignore-platform-reqs ';
        }
        return str_replace('2>&1', $composerFlags . '2>&1', $command);
    }
}