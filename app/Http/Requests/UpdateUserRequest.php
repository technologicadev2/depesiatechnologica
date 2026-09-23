<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('id');
        return [
            
            'username' => 'required|string|max:20|unique:users,username,' . $userId,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|integer|exists:roles,id',
        ];
    }
    public function messages()
    {
        return [
            'username.unique' => 'Ce username d\'utilisateur existe déjà.',
        ];
    }
}