<?php

namespace NPS\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use NPS\CoreBundle\Controller\CoreController;
use NPS\CoreBundle\Helper\NotificationHelper;

/**
 * BaseController
 */
abstract class BaseController extends CoreController
{
}