<?php

namespace App\Http\Requests\Project\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
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
            'title' => __('request.company_title'),
        ];
    }

    public function messages()
    {
        return [
            'title.required' => __('request.required'),
            'title.min'      => __('request.min_length'),
            'title.max'      => __('request.max_length'),
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
            'title' => 'required|min:4|max:255',
        ];
    }
}
