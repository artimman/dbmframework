<?php

declare(strict_types=1);

namespace App\Config;

class ConstantConfig
{
    // General settings
    public const PATH_DATA_CONTENT = BASE_DIRECTORY . 'data' . DS . 'content' . DS;

    public const ARRAY_FLASH_MESSAGE = [
        'messageInfo' => [
            'bg' => 'alert-info',
        ],
        'messageWarning' => [
            'bg' => 'alert-warning',
        ],
        'messageDanger' => [
            'bg' => 'alert-danger',
        ],
        'messageSuccess' => [
            'bg' => 'alert-success',
        ],
    ];

    // Authentication - User roles
    public const USER_ROLES = [
        'A' => 'ADMIN',
        'U' => 'USER',
        'G' => 'GUEST',
    ];

    //-INSTALL_POINT_ADD_CONSTANT
}
