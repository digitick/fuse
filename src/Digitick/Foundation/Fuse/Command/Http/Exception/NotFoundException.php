<?php


namespace Digitick\Foundation\Fuse\Command\Http\Exception;


class NotFoundException extends ClientException
{
    const STATUS_CODE = 404;
}