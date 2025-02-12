<?php

namespace Modules\Itenant\Http\Requests;

use Modules\Core\Internationalisation\BaseFormRequest;

class ManageModulesRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'modules'=> 'required|array',
            'organization_id' => 'required|integer',
            'enabled' => 'integer|in:0,1',
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
