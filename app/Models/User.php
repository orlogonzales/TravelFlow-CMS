<?php

namespace App\Models;

use App\Domains\User\Models\User as DomainUser;

/**
 * Proxy de compatibilidad hacia el modelo de dominio User de TF CMS.
 */
class User extends DomainUser
{
    //
}
