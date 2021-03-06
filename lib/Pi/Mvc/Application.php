<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Pi\Mvc;

use Pi;
use Pi\Application\Engine\AbstractEngine;
use Laminas\Mvc\Application as LaminasApplication;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Service;
use Laminas\ServiceManager\ServiceManager;

/**
 * Application handler
 *
 * {@inheritDoc}
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Application extends LaminasApplication
{
    /**
     * Section: front, admin, feed, api
     *
     * @var string
     */
    protected $section;

    /**
     * Engine
     *
     * @var AbstractEngine
     */
    protected $engine;

    /**
     * Set listeners
     *
     * @param string[] $listeners
     *
     * @return $this
     */
    public function setListeners(array $listeners = [])
    {
        if ($listeners) {
            $this->defaultListeners = array_merge(
                $this->defaultListeners,
                $listeners
            );
        }

        return $this;
    }

    /**
     * Load application handler
     *
     * @param array $configuration
     *
     * @return $this
     */
    public static function init($configuration = [])
    {
        $smConfig       = isset($configuration['service_manager']) ? $configuration['service_manager'] : [];
        $serviceManager = new ServiceManager(new Service\ServiceManagerConfig($smConfig));
        $serviceManager->setService('ApplicationConfig', $configuration);
        $serviceManager->get('Configuration')->exchangeArray($configuration);

        $listenersFromAppConfig     = isset($configuration['listeners']) ? $configuration['listeners'] : [];
        $config                     = $serviceManager->get('Config');
        $listenersFromConfigService = isset($config['listeners']) ? $config['listeners'] : [];

        $listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));

        return $serviceManager->get('Application')->setListeners($listeners);
    }

    /**
     * Set section, called by Engine
     *
     * @param string $section
     *
     * @return $this
     */
    public function setSection($section = null)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section
     *
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Set application boot engine
     *
     * @param AbstractEngine $engine
     *
     * @return $this
     */
    public function setEngine(AbstractEngine $engine = null)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * Get application boot engine
     *
     * @return AbstractEngine
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**#@+
     * Syntactic sugar
     */
    /**
     * Get RouteMatch of MvcEvent
     *
     * @return \Laminas\Mvc\Router\RouteMatch
     */
    public function getRouteMatch()
    {
        return $this->event->getRouteMatch();
    }

    /**
     * Get router of MvcEvent
     *
     * @return \Laminas\Mvc\Router\RouteStackInterface
     */
    public function getRouter()
    {
        return $this->event->getRouter();
    }
    /**#@-*/

    /**#@+
     * Extended from Laminas\Mvc\Application
     */
    /**
     * {@inheritdoc}
     */
    protected function completeRequest(MvcEvent $event)
    {
        parent:: completeRequest($event);
        /**
         * Log route information
         */
        if (Pi::service()->hasService('log')) {
            if ($this->getRouteMatch()) {
                Pi::service('log')->info(
                    sprintf(
                        'Route: %s:%s-%s-%s.',
                        $this->getSection(),
                        $this->getRouteMatch()->getParam('module'),
                        $this->getRouteMatch()->getParam('controller'),
                        $this->getRouteMatch()->getParam('action')
                    )
                );
            } else {
                Pi::service('log')->err($event->getError());
            }
        }

        return $this;
    }
    /**#@-*/
}
