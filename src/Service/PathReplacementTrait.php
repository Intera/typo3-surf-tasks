<?php

namespace Intera\Surf\Service;

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * Service trait for replacing path placeholders.
 */
trait PathReplacementTrait
{
    /**
     * Replaces the path placeholders.
     */
    protected function replacePathPlaceholders(
        string $string,
        Application $application,
        Deployment $deployment,
        Node $node
    ): string {
        $replacePaths = [
            '{workspacePath}' => $deployment->getWorkspacePath($application),
            '{deploymentPath}' => escapeshellarg($node->getDeploymentPath()),
            '{sharedPath}' => escapeshellarg($node->getSharedPath()),
            '{releasePath}' => escapeshellarg($deployment->getApplicationReleasePath($node)),
            '{currentPath}' => escapeshellarg($node->getReleasesPath() . '/current'),
            '{previousPath}' => escapeshellarg($node->getReleasesPath() . '/previous'),
        ];

        return str_replace(array_keys($replacePaths), $replacePaths, $string);
    }
}
