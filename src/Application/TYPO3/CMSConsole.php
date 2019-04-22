<?php
declare(strict_types=1);

namespace Intera\Surf\Application\TYPO3;

use Intera\Surf\Task\Grunt\YarnTask;
use Intera\Surf\Task\HardlinkReleaseTask;
use TYPO3\Surf\Application\TYPO3\CMS as SurfCMS;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;
use TYPO3\Surf\Domain\Model\Workflow;
use TYPO3\Surf\Task\SymlinkReleaseTask;
use TYPO3\Surf\Task\TYPO3\CMS\CopyConfigurationTask;
use TYPO3\Surf\Task\TYPO3\CMS\CreatePackageStatesTask;
use TYPO3\Surf\Task\TYPO3\CMS\RunCommandTask;

/**
 * TYPO3 CMS deployment with the TYPO3 console.
 */
class CMSConsole extends SurfCMS
{
    /**
     * Initializes some sane default options.
     *
     * @param string $name
     */
    public function __construct($name = 'TYPO3 CMS')
    {
        parent::__construct($name);
        $this->setOption('keepReleases', 3);

        $this->setOption(
            'TYPO3\\Surf\\Task\\Transfer\\RsyncTask[rsyncExcludes]',
            [
                '.composercache',
                '.yarn-cache',
                '.git',
                '.ddev',
                '.idea',
                '/deployment',
                '/web/fileadmin',
                '/web/uploads',
                'node_modules',
                'Resources/Public/Build/Temp',
            ]
        );

        $this->setOption('composerCommandPath', 'composer');
        $this->setContext('Production');
    }

    /**
     * Register tasks for this application
     *
     * @param SimpleWorkflow|Workflow $workflow
     * @param Deployment $deployment
     */
    public function registerTasks(Workflow $workflow, Deployment $deployment)
    {
        parent::registerTasks($workflow, $deployment);

        if ($this->hasOption('disableRollback') && $this->getOption('disableRollback')) {
            $workflow->setEnableRollback(false);
        }

        $this->replaceSymlinkWithHardlinkRelease($workflow);

        $this->defineFixFolderStructureTask($workflow);
        $this->defineDatabaseUpdateTask($workflow);
        $this->defineFlushFilesCacheTask($workflow);

        $this->registerYarnTask($workflow);

        $this->removeObsoleteConfigTasks($workflow);
        $this->registerCustomTasks($workflow);
    }

    protected function registerYarnTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Intera\\Surf\\DefinedTask\\Grunt\\YarnTask',
            YarnTask::class,
            ['forceLocalMode' => true]
        );
        $workflow->afterTask(
            'TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask',
            'Intera\\Surf\\DefinedTask\\Grunt\\YarnTask'
        );
    }

    private function defineDatabaseUpdateTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema',
            RunCommandTask::class,
            ['command' => 'database:updateschema']
        );
    }

    private function defineFixFolderStructureTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Intera\\Surf\\DefinedTask\\FixFolderStructure',
            RunCommandTask::class,
            ['command' => 'install:fixfolderstructure']
        );
    }

    private function defineFlushFilesCacheTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\FlushFileCaches',
            RunCommandTask::class,
            [
                'command' => 'cache:flush',
                'arguments' => ['--files-only'],
            ]
        );
    }

    private function registerCustomTasks(Workflow $workflow)
    {
        $workflow->beforeTask(
            'TYPO3\\Surf\\Task\\TYPO3\\CMS\\SetUpExtensionsTask',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema'
        );

        $workflow->beforeTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema',
            'Intera\\Surf\\DefinedTask\\FixFolderStructure'
        );

        $workflow->afterTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\FlushFileCaches'
        );
    }

    private function removeObsoleteConfigTasks(Workflow $workflow)
    {
        $workflow->removeTask(CopyConfigurationTask::class);
        $workflow->removeTask(CreatePackageStatesTask::class);
    }

    private function replaceSymlinkWithHardlinkRelease(Workflow $workflow)
    {
        $workflow->removeTask(SymlinkReleaseTask::class);
        $workflow->addTask(HardlinkReleaseTask::class, 'switch');
    }
}
