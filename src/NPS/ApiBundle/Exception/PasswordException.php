<?php

namespace NPS\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class PasswordException
 *
 * @package NPS\ApiBundle\Exception
 */
class PasswordException extends HttpException
{
    /**
     * UnauthorizedException constructor.
     *
     * @param null $message
     */
    public function __construct($message = null)
    {
        $message = ($message) ?: 'Wrong password';
        parent::__construct(Response::HTTP_PRECONDITION_FAILED, $message, null, [], 0);
    }
}
