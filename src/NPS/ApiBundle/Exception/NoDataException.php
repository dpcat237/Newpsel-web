<?php

namespace NPS\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class NoDataException
 *
 * @package NPS\ApiBundle\Exception
 */
class NoDataException extends HttpException
{
    /**
     * UnauthorizedException constructor.
     *
     * @param null $message
     */
    public function __construct($message = null)
    {
        $message = ($message)?:'No required data';
        parent::__construct(Response::HTTP_PRECONDITION_FAILED, $message, null, [], 0);
    }
}
