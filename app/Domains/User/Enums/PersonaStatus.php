<?php

namespace App\Domains\User\Enums;

/**
 * Estados biográficos/operativos de una Persona en TravelFlow CMS.
 */
enum PersonaStatus: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
    case ARCHIVADO = 'archivado';
}
