<?php


namespace Digitick\Foundation\Fuse\Command\Http\Exception;


class InternalErrorException extends ServerException
{
    const STATUS_CODE = 500;
}