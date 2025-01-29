<?php

namespace Modules\Itenant\Http\Controllers\Api;

use Modules\Core\Icrud\Controllers\BaseCrudController;
//Model
use Modules\Itenant\Entities\OrganizationUser;
use Modules\Itenant\Repositories\OrganizationUserRepository;

class OrganizationUserApiController extends BaseCrudController
{
  public $model;
  public $modelRepository;

  public function __construct(OrganizationUser $model, OrganizationUserRepository $modelRepository)
  {
    $this->model = $model;
    $this->modelRepository = $modelRepository;
  }
}
