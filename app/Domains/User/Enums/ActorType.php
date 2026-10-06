<?php

namespace App\Domains\User\Enums;

/**
 * Tipología formal de actores del sistema TravelFlow CMS.
 *
 * Evita la creación de usuarios humanos ficticios para representar procesos autónomos o servicios técnicos.
 */
enum ActorType: string
{
    case HUMAN_USER = 'HUMAN_USER';
    case SYSTEM = 'SYSTEM';
    case SERVICE = 'SERVICE';
    case INTEGRATION = 'INTEGRATION';

    /**
     * Retorna si el actor es un ser humano real autenticado.
     */
    public function isHuman(): bool
    {
        return $this === self::HUMAN_USER;
    }
}
