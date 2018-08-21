<?php

namespace raystech\medialibrary\Facades;

use Illuminate\Support\Facades\Facade;

class medialibrary extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'medialibrary';
    }
}
