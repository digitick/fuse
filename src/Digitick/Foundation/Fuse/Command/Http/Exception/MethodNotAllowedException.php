<?php


namespace Digitick\Foundation\Fuse\Command\Http\Exception;


class MethodNotAllowedException extends ClientException
{
    const STATUS_CODE = 405;
}