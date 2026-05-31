<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates incoming passkey rename requests.
 */
class RenamePasskeyRequest extends FormRequest
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
        $maxLength = (int) config('vaultic.device_name_max_length', 100);

        return [
            'name' => ['required', 'string', 'min:1', 'max:' . $maxLength],
        ];
    }
}
