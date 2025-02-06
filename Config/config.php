<?php

return [

    /**
     * Base Tenant | Connection DB
     */
    "baseTenantConnection" => 'baseTenant',

    /**
     * Modules optionals
     */
    "optionalModules" => [
        'icommerce' => [
            'dbPrefix' => 'icommerce',
            'dependencies' => [
                'icommerceagree' => [],
                'icommercecheckmo' => [],
                'wishlistable' => ['dbPrefix' => 'wishlistable'],
            ]
        ],
        'iblog' => ['dbPrefix' => 'iblog'],
        'slider' => ['dbPrefix' => 'slider'],
        'ibuilder' => ['dbPrefix' => 'ibuilder'],
        'icommercepayu' => []
    ],

    /**
     * Base Tenant | Admin Id
     */
    "userIdAdmin" => 1,

    /*
    |--------------------------------------------------------------------------
    | Tenant Extra Configurations | OJO ESTE CREO Q ES SERÁ NECESARIO
    |--------------------------------------------------------------------------
    */
    'tenant' => [
        //App URL (Used to domain)
        'appUrl' => ''
    ],

];
