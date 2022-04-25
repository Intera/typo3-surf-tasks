<?php

namespace Intera\Surf\Task\Generic;

use InvalidArgumentException;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareInterface;
use TYPO3\Surf\Domain\Service\ShellCommandServiceAwareTrait;

class CopyFilesTask extends Task implements ShellCommandServiceAwareInterface
{
    use ShellCommandServiceAwareTrait;

    protected Deployment $deployment;

    protected Node $node;

    public function execute(
        Node $node,
        Application $application,
        Deployment $deployment,
        array $options = []
    ) {
        $this->node = $node;
        $this->deployment = $deployment;

        if (empty($options['sourceDir'])) {
            throw new InvalidArgumentException('sourceDir option is missing.');
        }
        if (empty($options['targetDir'])) {
            throw new InvalidArgumentException('targetDir option is missing.');
        }
        if (empty($options['files'])) {
            $deployment->getLogger()->debug('Files option empty, nothing to copy.');
            return;
        }
        if (!is_array($options['files'])) {
            throw new InvalidArgumentException('files option must be an array.');
        }

        $sourceDir = rtrim($options['sourceDir'], '/');
        $targetDir = rtrim($options['targetDir'], '/');

        $this->shell->executeOrSimulate('test -d ' . escapeshellarg($sourceDir), $node, $deployment);
        $this->shell->executeOrSimulate('test -d ' . escapeshellarg($targetDir), $node, $deployment);

        foreach ($options['files'] as $source => $target) {
            $source = $sourceDir . '/' . $source;
            $target = $targetDir . '/' . $target;

            $isDir = $this->shell->executeOrSimulate('test -d ' . escapeshellarg($source), $node, $deployment, true);
            $this->shell->executeOrSimulate('test -d ' . escapeshellarg(dirname($target)), $node, $deployment);
            if ($isDir === false) {
                $deployment->getLogger()->debug('Working in file mode');
                $this->copyFile($source, $target);
            } else {
                $deployment->getLogger()->debug('Working in directory mode');
                $this->copyDirectory($source, $target);
            }
        }
    }

    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = []): void
    {
        $this->execute($node, $application, $deployment, $options);
    }

    protected function copyDirectory($sourceDirectory, $targetDirectory)
    {
        $this->shell->executeOrSimulate(
            'cp -r ' . escapeshellarg($sourceDirectory) . ' ' . escapeshellarg($targetDirectory),
            $this->node,
            $this->deployment
        );
    }

    protected function copyFile($sourceFile, $targetFile)
    {
        $this->shell->executeOrSimulate(
            'cp ' . escapeshellarg($sourceFile) . ' ' . escapeshellarg($targetFile),
            $this->node,
            $this->deployment
        );
    }
}
