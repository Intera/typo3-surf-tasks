<?php
namespace Intera\Surf\Application\TYPO3;

use TYPO3\Surf\Application\TYPO3\CMS as SurfCMS;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Workflow;

/**
 * Improves the TYPO3 CMS application with some extra features like:
 * Using a more customizable Composer task, registering a grunt build task.
 */
class CMS extends SurfCMS
{
    /**
     * Initializes some sane default options.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->options = array_merge(
            $this->options,
            [
                'composerCommandPath' => 'composer',
                'composerFlags' => '--ignore-platform-reqs --prefer-dist',
                'keepReleases' => 3,
                'rsyncExcludes' => [
                    'deployment',
                    '.git',
                    '.gitignore',
                    '.gitattributes',
                    'node_modules',
                    'surf.php',
                    'dynamicReturnTypeMeta.json'
                ]
            ]
        );
    }

    /**
     * Register tasks for this application
     *
     * @param Workflow $workflow
     * @param Deployment $deployment
     * @return void
     */
    public function registerTasks(Workflow $workflow, Deployment $deployment)
    {
        parent::registerTasks($workflow, $deployment);
        $workflow->removeTask('TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask');
        $workflow->defineTask(
            'TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask',
            \Intera\Surf\Task\Composer\InstallTask::class,
            [
                'nodeName' => 'localhost',
                'useApplicationWorkspace' => true
            ]
        );
        $workflow->afterTask(
            'TYPO3\\Surf\\Task\\Package\\GitTask',
            'TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask',
            $this
        );

        $this->registerFrontendBuildTasks($workflow);
        $this->registerDeployutilsCacheClearingTask($workflow);
        $this->registerDeployutilsOpcacheClearingTask($workflow);
    }

    /**
     * Registers the task for clearing the TYPO3 cache using the deployutils extension.
     *
     * @param Workflow $workflow
     */
    protected function registerDeployutilsCacheClearingTask(Workflow $workflow)
    {
        $workflow->afterStage(
            'switch',
            \Intera\Surf\Task\Deployutils\ClearCacheTask::class,
            $this
        );
    }

    /**
     * Registers the deployutils task for clearing the opcache.
     *
     * This task should run AFTER the clearing of the TYPO3 caches, this seems to work better.
     *
     * @param Workflow $workflow
     */
    protected function registerDeployutilsOpcacheClearingTask(Workflow $workflow)
    {
        if (!$this->hasOption('deployutilsToken')) {
            return;
        }

        $workflow->afterStage(
            'switch',
            \Intera\Surf\Task\Deployutils\ClearOpcacheTask::class,
            $this
        );
    }

    /**
     * Registers a Grunt build task if the project Extension path was specified in the options.
     *
     * @param Workflow $workflow
     */
    protected function registerFrontendBuildTasks(Workflow $workflow)
    {
        if (!$this->hasOption('projectExtensionPath')) {
            return;
        }

        $projectExtensionPath = rtrim($this->getOption('projectExtensionPath'), '/');

        $workflow->defineTask(
            'Intera\\Surf\\DefinedTask\\Grunt\\BuildTask',
            \Intera\Surf\Task\Grunt\BuildTask::class,
            [
                'forceLocalMode' => true,
                'gruntRootPath' => $projectExtensionPath . '/Resources/Private/Build',
                'skipMissingDirectory' => true
            ]
        );
        $workflow->afterTask(
            'TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask',
            'Intera\\Surf\\DefinedTask\\Grunt\\BuildTask',
            $this
        );
    }
}
