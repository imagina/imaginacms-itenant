<?php

namespace Modules\Itenant\Http\Requests;

use Modules\Core\Internationalisation\BaseFormRequest;

class CreateOrganizationRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'modules'=> 'required|array',
        ];
    }

    public function translationRules()
    {
        return [
             //'title'=> ['required', new UniqueSlugRule('isite__organization_translations'), 'min:2',"alpha_dash:ascii"],
        ];
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

    public function getValidator(){
        return $this->getValidatorInstance();
    }
    
}
