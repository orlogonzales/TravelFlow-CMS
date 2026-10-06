<?php

namespace App\Http\Requests\Admin;

use App\Domains\User\Enums\PersonaStatus;
use App\Domains\User\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonaRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('personas.editar') ?? false;
    }

    /**
     * Sanitización y normalización razonable antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombres' => is_string($this->nombres) ? trim($this->nombres) : $this->nombres,
            'apellidos' => is_string($this->apellidos) ? trim($this->apellidos) : $this->apellidos,
            'tipo_documento' => is_string($this->tipo_documento) ? trim($this->tipo_documento) : $this->tipo_documento,
            'numero_documento' => is_string($this->numero_documento) ? trim($this->numero_documento) : $this->numero_documento,
            'email' => is_string($this->email) ? trim(strtolower($this->email)) : $this->email,
            'telefono' => is_string($this->telefono) ? trim($this->telefono) : $this->telefono,
        ]);
    }

    /**
     * Reglas de validación para la actualización de Personas.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'tipo_documento' => ['nullable', 'string', 'max:20'],
            'numero_documento' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'estado' => ['required', 'string', Rule::enum(PersonaStatus::class)],
        ];
    }

    /**
     * Comprobación de duplicidad documental (tipo_documento + numero_documento)
     * excluyendo a la persona actual que se está editando.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tipo = $this->input('tipo_documento');
            $num = $this->input('numero_documento');
            $persona = $this->route('persona');
            $personaId = $persona instanceof Persona ? $persona->id : (int) $persona;

            if (!empty($tipo) && !empty($num)) {
                $exists = Persona::where('tipo_documento', $tipo)
                    ->where('numero_documento', $num)
                    ->where('id', '!=', $personaId)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'numero_documento',
                        'Ya existe otra persona registrada con este tipo y número de documento.'
                    );
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.max' => 'Los nombres no pueden exceder 100 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.max' => 'Los apellidos no pueden exceder 100 caracteres.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.max' => 'El correo electrónico no puede exceder 150 caracteres.',
            'numero_documento.max' => 'El número de documento no puede exceder 50 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder 50 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.enum' => 'El estado proporcionado no es válido.',
        ];
    }
}
