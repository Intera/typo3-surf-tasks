<?php
namespace Intera\Surf\Application\TYPO3;

use TYPO3\Surf\Application\TYPO3\CMS as SurfCMS;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Workflow;

class CMS extends SurfCMS
{
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
            \Intera\Surf\Task\Composer\InstallTask::class, array(
                'nodeName' => 'localhost',
                'useApplicationWorkspace' => true
            )
        );
        $workflow->afterTask('TYPO3\\Surf\\Task\\Package\\GitTask', 'TYPO3\\Surf\\DefinedTask\\Composer\\LocalInstallTask', $this);
    }
}