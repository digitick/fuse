<?php


namespace Digitick\Foundation\Fuse\Command\Http\Exception;


class BadRequestException extends ClientException
{
    const STATUS_CODE = 400;
}