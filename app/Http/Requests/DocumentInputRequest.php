<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentInputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('document_type');
        $definitions = config("documents.fields.{$type}", []);
        $fields = array_merge(config('documents.fields.common', []), $definitions);

        $rules = ['document_type' => ['required', 'in:'.implode(',', array_keys(config('documents.types', [])))]];

        foreach ($fields as $field) {
            $rules[$field['name']] = $field['rules'];
        }

        $rules['save_as_resident'] = ['sometimes', 'boolean'];

        return $rules;
    }
}
