<?php


namespace Digitick\Foundation\Fuse\Command\Http\Exception;


class NotImplementedException extends ServerException
{
    const STATUS_CODE = 501;
}