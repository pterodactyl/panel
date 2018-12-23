<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Serializer
    |--------------------------------------------------------------------------
    |
    | The default serializer to be used when performing a transformation. It
    | may be left empty to use Fractal's default one. This can either be a
    | string or a League\Fractal\Serializer\SerializerAbstract subclass.
    |
    */

    'default_serializer' => League\Fractal\Serializer\JsonApiSerializer::class,

    /*
    |--------------------------------------------------------------------------
    | Auto Includes
    |--------------------------------------------------------------------------
    |
    | If enabled Fractal will automatically add the includes who's
    | names are present in the `include` request parameter.
    |
    */

    'auto_includes' => [
        'enabled' => true,
        'request_key' => 'include',
    ],
];
