<?php

namespace NPS\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class PremiumPermissionsException
 *
 * @package NPS\ApiBundle\Exception
 */
class PremiumPermissionsException extends HttpException
{
    /**
     * UnauthorizedException constructor.
     *
     * @param null $message
     */
    public function __construct($message = null)
    {
        $message = ($message)?:"Doesn't have required premium permissions";
        parent::__construct(Response::HTTP_UNAUTHORIZED, $message, null, [], 0);
    }
}
