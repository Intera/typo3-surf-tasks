<?php
namespace Intera\Surf\Task\Deployutils;

/**
 * This task calls the deployutils extension to clear the TYPO3 caches.
 */
class ClearCacheTask extends AbstractDeployCommandTask
{
    /**
     * Retuns the additional arguments that should be passed to the cli_dispatch.phpsh script.
     *
     * @return array
     */
    protected function getCliArguments() {
        return ['deploylocal:flushcache'];
    }
}