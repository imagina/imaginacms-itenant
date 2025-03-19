<?php

namespace Modules\Itenant\Http\Requests;

use Modules\Core\Internationalisation\BaseFormRequest;

class CreateOrganizationRequest extends BaseFormRequest
{
  public function rules()
  {
    return [
      'modules' => 'required|array',
      'layout_id' => 'required|integer'
    ];
  }

  public function translationRules()
  {
    return [
      'title' => 'required|min:1',
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
    return [
      'title.required' => trans('itenant::common.messages.title is required'),
    ];
  }

  public function getValidator()
  {
    return $this->getValidatorInstance();
  }

}
