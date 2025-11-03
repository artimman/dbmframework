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

    public const ARRAY_STATUS = [
        'A' => "active",
        'I' => "inactive",
        'N' => 'new',
    ];

    //-INSTALL_POINT_ADD_CONSTANT
}
