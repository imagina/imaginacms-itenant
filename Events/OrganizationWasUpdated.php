<?php

namespace Modules\Itenant\Events;

class OrganizationWasUpdated
{
    public $params;

    /*
    *  updatedWithBindings Params - From Entity
    */
    public function __construct($params = null)
    {
        $this->params = $params;
    }
}
