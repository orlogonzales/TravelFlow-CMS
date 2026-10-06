<?php

namespace Database\Factories;

use App\Domains\User\Enums\PersonaStatus;
use App\Domains\User\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    protected $model = Persona::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'tipo_documento' => 'DNI',
            'numero_documento' => fake()->unique()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'estado' => PersonaStatus::ACTIVO,
        ];
    }
}
