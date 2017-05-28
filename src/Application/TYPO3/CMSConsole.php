<?php
declare(strict_types=1);

namespace Intera\Surf\Application\TYPO3;

use Intera\Surf\Task\Grunt\YarnTask;
use Intera\Surf\Task\HardlinkReleaseTask;
use TYPO3\Surf\Application\TYPO3\CMS as SurfCMS;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;
use TYPO3\Surf\Domain\Model\Workflow;

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
        $this->setOption('TYPO3\\Surf\\Task\\TYPO3\\CMS\\SetUpExtensionsTask[scriptFileName]', 'typo3cms');
        $this->setOption('TYPO3\\Surf\\Task\\TYPO3\\CMS\\FlushCachesTask[scriptFileName]', 'typo3cms');
        $this->setOption('TYPO3\\Surf\\Task\\TYPO3\\CMS\\SymlinkDataTask[applicationRootDirectory]', 'web');
        $this->setOption('TYPO3\\Surf\\Task\\TYPO3\\CMS\\SymlinkDataTask[directories]', ['web/typo3conf/l10n']);

        $this->setOption(
            'TYPO3\\Surf\\Task\\Transfer\\RsyncTask[rsyncExcludes]',
            [
                '.git',
                'web/fileadmin',
                'web/typo3conf/l10n',
                'web/uploads',
                'node_modules',
                'Resources/Public/Build/Temp',
            ]
        );
        $this->setOption('applicationWebDirectory', 'web');
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

        $this->registerEnvAwareTask($workflow);

        $this->registerCopyIndexPhpTask($workflow);

        $this->registerYarnTask($workflow);

        $this->moveCacheFlushingToFinalizeStage($workflow);
    }

    protected function moveCacheFlushingToFinalizeStage(Workflow $workflow)
    {
        $workflow->removeTask('TYPO3\\Surf\\Task\\TYPO3\\CMS\\FlushCachesTask');
        $workflow->forStage('finalize', 'TYPO3\\Surf\\Task\\TYPO3\\CMS\\FlushCachesTask');
    }

    protected function registerCopyIndexPhpTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\CopyIndexPhp',
            'TYPO3\\Surf\\Task\\ShellTask',
            [
                'command' => [
                    'rm {releasePath}/web/index.php',
                    'cp {releasePath}/vendor/typo3/cms/index.php {releasePath}/web/index.php',
                ],
            ]
        );
        $workflow->afterStage('transfer', 'Helhum\\TYPO3\\Distribution\\DefinedTask\\CopyIndexPhp');
    }

    protected function registerEnvAwareTask(Workflow $workflow)
    {
        $workflow->defineTask(
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\EnvAwareTask',
            'TYPO3\\Surf\\Task\\ShellTask',
            [
                'command' => [
                    'cp {sharedPath}/.env {releasePath}/.env',
                    'cd {releasePath}',
                ],
            ]
        );
        $workflow->beforeTask(
            'TYPO3\\Surf\\Task\\TYPO3\\CMS\\CreatePackageStatesTask',
            'Helhum\\TYPO3\\Distribution\\DefinedTask\\EnvAwareTask'
        );
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

    protected function replaceSymlinkWithHardlinkRelease(Workflow $workflow)
    {
        $workflow->removeTask('TYPO3\\Surf\\Task\\SymlinkReleaseTask');
        $workflow->addTask(HardlinkReleaseTask::class, 'switch');
    }
}
