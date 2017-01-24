<?php


namespace Digitick\Foundation\Fuse\Command;


interface CacheableCommand
{
    public function getCacheKey ();
    public function getTtl ();
}