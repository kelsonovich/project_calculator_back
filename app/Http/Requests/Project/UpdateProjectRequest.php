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

    public function messages()
    {
        return [
            'tasks.title.required'   => __('request.tasks_title_required'),
            'steps.title.required'   => __('request.steps_title_required'),
            'options.title.required' => __('request.options_title_required'),
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'tasks.title'   => 'sometimes|required|min:5|max:255',
            'steps.title'   => 'sometimes|required|min:5|max:255',
            'options.title' => 'sometimes|required|min:5|max:255',
        ];
    }
}
