<?php

namespace YlsIdeas\CockroachDb\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \YlsIdeas\CockroachDb\CockroachDb
 */
class CockroachDb extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cockroachdb-laravel';
    }
}
