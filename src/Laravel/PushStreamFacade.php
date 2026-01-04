<?php

namespace PushStream\Laravel;

use Illuminate\Support\Facades\Facade;

class PushStreamFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pushstream';
    }
}
