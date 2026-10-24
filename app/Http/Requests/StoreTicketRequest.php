<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    /**
     * Anyone can submit a ticket (no login in this demo).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'min:3', 'max:255'],
            // Upper limit keeps prompts (and LLM cost) bounded.
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * Use the form's labels in error messages ("The problem description field…")
     * instead of column names ("The body field…").
     */
    public function attributes(): array
    {
        return [
            'customer_email' => 'email',
            'subject' => 'subject',
            'body' => 'problem description',
        ];
    }
}
