<?php

namespace App\Domains\User\Enums;

/**
 * Estados de cuenta de un Usuario en TravelFlow CMS.
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';

    /**
     * Determina si la cuenta está habilitada para autenticarse y operar.
     */
    public function canAuthenticate(): bool
    {
        return $this === self::ACTIVE;
    }
}
