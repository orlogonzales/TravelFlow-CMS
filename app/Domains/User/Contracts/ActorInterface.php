<?php

namespace App\Domains\User\Contracts;

use App\Domains\User\Enums\ActorType;

/**
 * Contrato de Actor para operaciones y futura auditoría funcional de TF CMS.
 *
 * Permite identificar responsabilidades de forma uniforme tanto para usuarios
 * humanos autenticados como para procesos automáticos del sistema.
 */
interface ActorInterface
{
    /**
     * Tipo de actor que ejecuta la operación.
     */
    public function getActorType(): ActorType;

    /**
     * Identificador único del actor (o null para actores puramente del sistema).
     */
    public function getActorId(): ?string;

    /**
     * Nombre legible o descriptor del actor.
     */
    public function getActorName(): string;
}
