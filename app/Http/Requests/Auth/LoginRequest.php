<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Backward compatibility: old clients post `email`, new form posts `login`.
        if (! $this->filled('login') && $this->filled('email')) {
            $this->merge(['login' => $this->input('email')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function loginColumn(): string
    {
        $login = trim((string) $this->input('login', ''));

        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    /**
     * @return array<string, string>
     */
    public function credentials(): array
    {
        return [
            $this->loginColumn() => trim((string) $this->input('login', '')),
            'password' => (string) $this->input('password', ''),
        ];
    }
}
