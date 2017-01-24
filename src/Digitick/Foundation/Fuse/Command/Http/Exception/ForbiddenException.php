<?php


namespace Digitick\Foundation\Fuse\Command\Http\Exception;


class ForbiddenException extends ClientException
{
    const STATUS_CODE = 403;
}