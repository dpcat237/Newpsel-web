<?php

namespace NPS\ApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class UserExistsException
 *
 * @package NPS\ApiBundle\Exception
 */
class UserExistsException extends HttpException
{
    /**
     * UnauthorizedException constructor.
     *
     * @param null $message
     */
    public function __construct($message = null)
    {
        $message = ($message)?:'User with this email exists';
        parent::__construct(Response::HTTP_CONFLICT, $message, null, [], 0);
    }
}
