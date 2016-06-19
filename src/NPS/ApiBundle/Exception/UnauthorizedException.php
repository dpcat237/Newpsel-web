<?php

namespace NPS\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class UnauthorizedException
 *
 * @package NPS\ApiBundle\Exception
 */
class UnauthorizedException extends HttpException
{
    /**
     * UnauthorizedException constructor.
     *
     * @param null $message
     */
    public function __construct($message = null)
    {
        $message = ($message)?:'No device key';
        parent::__construct(Response::HTTP_UNAUTHORIZED, $message, null, [], 0);
    }
}
