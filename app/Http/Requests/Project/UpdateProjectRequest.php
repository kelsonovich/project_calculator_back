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

    public function attributes()
    {
        return [
            'project.tasks.*.title'   => __('request.task_title'),
            'project.steps.*.title'   => __('request.steps_title'),
            'project.options.*.title' => __('request.options_title'),
        ];
    }

    public function messages()
    {
        return [
            'project.tasks.*.title.required'   => __('request.required'),
            'project.steps.*.title.required'   => __('request.required'),
            'project.options.*.title.required' => __('request.required'),

            'project.tasks.*.title.min'   => __('request.min_length'),
            'project.steps.*.title.min'   => __('request.min_length'),
            'project.options.*.title.min' => __('request.min_length'),

            'project.tasks.*.title.max'   => __('request.max_length'),
            'project.steps.*.title.max'   => __('request.max_length'),
            'project.options.*.title.max' => __('request.max_length'),
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
            'project.tasks.*.title'   => 'sometimes|required|min:5|max:255',
            'project.steps.*.title'   => 'sometimes|required|min:5|max:255',
            'project.options.*.title' => 'sometimes|required|min:5|max:255',
        ];
    }
}
