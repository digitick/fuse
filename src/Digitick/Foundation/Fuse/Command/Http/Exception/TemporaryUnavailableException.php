<?php


namespace Digitick\Foundation\Fuse\Command\Http\Exception;


class TemporaryUnavailableException extends ServerException
{
    const STATUS_CODE = 503;
}