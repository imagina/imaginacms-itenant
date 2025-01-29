<?php

namespace Modules\Itenant\Entities;

use Modules\Core\Icrud\Entities\CrudModel;

class UserOrganization extends CrudModel
{
 
  protected $table = 'itenant__user_organization';
  public $transformer = 'Modules\Itenant\Transformers\UserOrganizationTransformer';
  public $repository = 'Modules\Itenant\Repositories\UseOrganizationrRepository';
  public $requestValidation = [
      'create' => 'Modules\Itenant\Http\Requests\CreateUserOrganizationRequest',
      'update' => 'Modules\Itenant\Http\Requests\UpdateUserOrganizationRequest',
    ];
  //Instance external/internal events to dispatch with extraData
  public $dispatchesEventsWithBindings = [
    //eg. ['path' => 'path/module/event', 'extraData' => [/*...optional*/]]
    'created' => [],
    'creating' => [],
    'updated' => [],
    'updating' => [],
    'deleting' => [],
    'deleted' => []
  ];
 
  protected $fillable = [
    'organization_id',
    'user_id',
    'role_id',
    'permissions'
  ];
  
}
