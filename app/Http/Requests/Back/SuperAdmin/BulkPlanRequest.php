<?php

namespace App\Http\Requests\Back\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class BulkPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'from_plan' => ['required', 'in:free,premium,enterprise,all'],
            'to_plan'   => ['required', 'in:free,premium,enterprise'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_plan.required' => 'Choisissez le plan source.',
            'to_plan.required'   => 'Choisissez le plan cible.',
            'to_plan.different'  => 'Le plan cible doit être différent du plan source.',
        ];
    }
}