<?php

declare(strict_types=1);

namespace App\Enum;

enum RoleEnum: string
{
    case ADMIN = 'ADMIN';
    case USER = 'USER';
    case GUEST = 'GUEST';
}
