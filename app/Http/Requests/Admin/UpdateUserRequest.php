<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends StoreUserRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            && ($this->user()?->can('update', $user) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();

        /** @var User $user */
        $user = $this->route('user');

        $rules['email'] = [
            'required', 'string', 'email:rfc', 'max:180',
            Rule::unique(User::class, 'email')->ignoreModel($user),
        ];

        // Leaving the password blank keeps the current one.
        $rules['password'] = ['nullable', 'confirmed', Password::defaults()];

        return $rules;
    }
}
