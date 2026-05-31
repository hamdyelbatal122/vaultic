<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates incoming authentication options requests.
 */
class AuthenticationOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['nullable', 'string', 'max:255'],
            'guard'      => ['nullable', 'string', 'max:50'],
        ];
    }
}
