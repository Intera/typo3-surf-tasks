<?php
namespace Intera\Surf\Task\Deployutils;

use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * This task makes a web request to the deployutils Extension to clear the opcache.
 */
class ClearOpcacheTask extends \TYPO3\Surf\Task\Test\HttpTestTask
{
    /**
     * Execute this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return void
     * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        if (!$node->getOption('webBaseUrl')) {
            throw new \InvalidArgumentException(
                'The webBaseUrl option must be set in all nodes for which the ClearOpcacheTask should be executed.'
            );
        }

        if (empty($options['deployutilsToken'])) {
            throw new \InvalidArgumentException('The deployutilsToken option is missing.');
        }


        $url = rtrim($node->getOption('webBaseUrl'), '/') .
            '/typo3conf/ext/deployutils/Resources/Php/ClearOpcache.php?token=';

        $deployment->getLogger()->debug('Calling opcache clearing script at: ' . $url . 'xxx');

        $url = $url . urlencode($options['deployutilsToken']);

        $result = $this->executeLocalCurlRequest($url, 5);

        $this->assertExpectedStatus(['expectedStatus' => 200], $result);
        $this->assertExpectedRegexp(['expectedRegexp' => '/success/'], $result);
    }
}
