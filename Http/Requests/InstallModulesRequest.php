<?php

namespace Modules\Itenant\Http\Requests;

use Modules\Core\Internationalisation\BaseFormRequest;

class InstallModulesRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'modules'=> 'required|array',
            'organization_id' => 'required|integer',
        ];
    }

    public function translationRules()
    {
        return [];
    }

    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [];
    }

    public function translationMessages()
    {
        return [];
    }

    public function getValidator()
    {
        return $this->getValidatorInstance();
    }
}
