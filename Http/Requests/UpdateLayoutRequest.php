<?php

namespace Modules\Itenant\Http\Requests;

use Modules\Core\Internationalisation\BaseFormRequest;

class UpdateLayoutRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'layout_id' => 'required|integer',
            'organization_id' => 'nullable|integer',
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

        $validator = $this->getValidatorInstance();

        $validator->sometimes('organization_id', 'required|integer', function () {
          return is_null(tenant());
        });

        return $validator;
    }

}
