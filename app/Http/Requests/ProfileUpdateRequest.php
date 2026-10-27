<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\EmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                // EmailRule, not a bare 'email': registration and guest
                // booking both resolve the domain, and this screen did not.
                // An address refused at sign-up could be set here afterwards,
                // which made the stricter rule decorative.
                EmailRule::strict(),
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
