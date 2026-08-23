<?php

namespace App\Http\Requests\Project;


use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
       'name'=>['required','string','max:255'],
       'description'=>['required','string'],
       'status'=>['required','in:active,on_hold,completed'],

        ];
    }
}
