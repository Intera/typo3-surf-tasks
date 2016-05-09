<?php
namespace Intera\Surf\Service;

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

/**
 * Service trait for replacing path placeholders.
 */
trait PathReplacementTrait
{

    /**
     * Replaces the path placeholders.
     *
     * @param string $string
     * @param Application $application
     * @param Deployment $deployment
     * @return string
     */
    protected function replacePathPlaceholders($string, Application $application, Deployment $deployment)
    {
        $replacePaths = [
            '{workspacePath}' => $deployment->getWorkspacePath($application),
            '{deploymentPath}' => $application->getDeploymentPath(),
            '{sharedPath}' => $application->getSharedPath(),
            '{releasePath}' => $deployment->getApplicationReleasePath($application),
            '{currentPath}' => $application->getReleasesPath() . '/current',
            '{previousPath}' => $application->getReleasesPath() . '/previous'
        ];

        return str_replace(array_keys($replacePaths), $replacePaths, $string);
    }
}
