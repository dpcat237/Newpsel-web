<?php

namespace NPS\CoreBundle\Listener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Predispatch
 */
class PredispatchListener
{
    private $router;
    private $container;

    /**
     * DIY constructor class
     * @param Router             $router    [description]
     * @param ContainerInterface $container [description]
     */
    public function __construct($router, $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * This is executed before the controller action. If the method 'preExecute'
     * exists in a controller, it will be run BEFORE any action is run
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $controllers = $event->getController();
            if (is_array($controllers)) {

                $controller = $controllers[0];

                if (is_object($controller) && method_exists($controller, 'preExecute')) {
                    $controller->preExecute();
                }
            }
        }
    }
}
