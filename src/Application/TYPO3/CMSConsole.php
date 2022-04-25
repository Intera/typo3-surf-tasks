<?php
declare(strict_types=1);

namespace Intera\Surf\Application\TYPO3;

use Intera\Surf\Task\Grunt\YarnTask;
use Intera\Surf\Task\HardlinkReleaseTask;
use TYPO3\Surf\Application\TYPO3\CMS as SurfCMS;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;
use TYPO3\Surf\Domain\Model\Workflow;
use TYPO3\Surf\Task\ShellTask;
use TYPO3\Surf\Task\SymlinkReleaseTask;
use TYPO3\Surf\Task\TYPO3\CMS\FlushCachesTask;
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
    public function __construct(string $name = 'TYPO3 CMS')
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
        $this->setOption('webDirectory', 'web');
        $this->setContext('Production');
    }

    /**
     * Register tasks for this application
     *
     * @param SimpleWorkflow|Workflow $workflow
     */
    public function registerTasks(Workflow $workflow, Deployment $deployment): void
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

        $this->registerCustomTasks($workflow);
    }

    protected function getOptionValueOr(string $option, $fallback)
    {
        if (!$this->hasOption($option)) {
            return $fallback;
        }
        return $this->getOption($option) ?: $fallback;
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
            'Intera\\Surf\\DefinedTask\\Grunt\\YarnTask',
            $this
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
            ['command' => 'cache:flush']
        );
    }

    private function registerCustomTasks(Workflow $workflow)
    {
        $workflow->beforeTask(
            'TYPO3\\Surf\\Task\\TYPO3\\CMS\\SetUpExtensionsTask',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema',
            $this
        );

        $workflow->beforeTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema',
            'Intera\\Surf\\DefinedTask\\FixFolderStructure',
            $this
        );

        $workflow->afterTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\UpdateDBSchema',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\FlushFileCaches',
            $this
        );
    }

    private function replaceSymlinkWithHardlinkRelease(Workflow $workflow)
    {
        $workflow->removeTask(SymlinkReleaseTask::class);
        $workflow->addTask(HardlinkReleaseTask::class, 'switch', $this);

        // We also have to replace the cache flush task because it normally runs in the
        // release directory and we need to run it in the current directory.
        $workflow->removeTask(FlushCachesTask::class);

        $phpBinaryPathAndFilename = $this->getOptionValueOr('phpBinaryPathAndFilename', 'php');
        $flushCacheCommand = $phpBinaryPathAndFilename
            . ' {currentPath}/' . $this->getOption('scriptFileName')
            . ' cache:flush';
        $commands = [
            'rm {currentPath}/var/cache -Rf',
            $flushCacheCommand,
        ];
        $workflow->defineTask(
            'Intera\\Surf\\DefinedTask\\FlushCachesTask',
            ShellTask::class,
            ['command' => $commands]
        );
        $workflow->afterStage('switch', 'Intera\\Surf\\DefinedTask\\FlushCachesTask', $this);
    }
}
