<?php
declare(strict_types=1);

namespace Intera\Surf\Application\TYPO3;

use Intera\Surf\Task\Grunt\YarnTask;
use Intera\Surf\Task\HardlinkReleaseTask;
use TYPO3\Surf\Application\TYPO3\CMS as SurfCMS;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;
use TYPO3\Surf\Domain\Model\Workflow;
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
                '.git',
                'web/fileadmin',
                'web/uploads',
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

        $this->defineMarkInstalledTask($workflow);
        $this->defineDumpSettingsTask($workflow);
        $this->defineDatabaseUpdateTask($workflow);
        $this->defineFlushFilesCacheTask($workflow);

        $this->registerYarnTask($workflow);

        $this->removeObsoleteConfigTasks($workflow);
        $this->registerCustomTasks($workflow);
    }

    private function defineDatabaseUpdateTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema',
            'TYPO3\\Surf\\Task\\TYPO3\\CMS\\RunCommandTask',
            ['command' => 'database:updateschema']
        );
    }

    private function defineDumpSettingsTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\DumpConfiguration',
            RunCommandTask::class,
            [
                'command' => 'settings:dump',
                'arguments' => ['--no-dev'],
            ]
        );
    }
    private function defineFlushFilesCacheTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\FlushFileCaches',
            'TYPO3\\Surf\\Task\\TYPO3\\CMS\\RunCommandTask',
            [
                'command' => 'cache:flush',
                'arguments' => ['--files-only'],
            ]
        );
    }

    private function defineMarkInstalledTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\MarkInstalled',
            'TYPO3\\Surf\\Task\\LocalShellTask',
            ['command' => 'touch {workspacePath}/.installed']
        );
    }

    private function registerCustomTasks(Workflow $workflow)
    {
        $workflow->afterTask(
            'TYPO3\\Surf\\Task\\Package\\GitTask',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\MarkInstalled'
        );
        $workflow->afterStage(
            'transfer',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\DumpConfiguration'
        );
        $workflow->beforeTask(
            'TYPO3\\Surf\\Task\\TYPO3\\CMS\\SetUpExtensionsTask',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema'
        );
        $workflow->afterTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\FlushFileCaches'
        );
    }

    private function registerYarnTask(Workflow $workflow)
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

    private function removeObsoleteConfigTasks(Workflow $workflow)
    {
        $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\CopyConfigurationTask');
        $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\CreatePackageStatesTask');
    }

    private function replaceSymlinkWithHardlinkRelease(Workflow $workflow)
    {
        $workflow->removeTask('TYPO3\\Surf\\Task\\SymlinkReleaseTask');
        $workflow->addTask(HardlinkReleaseTask::class, 'switch');
    }
}
