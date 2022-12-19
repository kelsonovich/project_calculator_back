<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
//            'title'          => 'required|max:255',
            'title'          => 'max:255',
            'description'    => 'max:255',
            'start'          => 'date',
            'hours_per_week' => 'integer',
        ];
    }
}
