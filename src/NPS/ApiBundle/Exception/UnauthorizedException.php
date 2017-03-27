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
     * @param string $append
     */
    public function __construct($append = '')
    {
        $message = 'No device key or is wrong. '.$append;
        parent::__construct(Response::HTTP_UNAUTHORIZED, $message, null, [], 0);
    }
}
