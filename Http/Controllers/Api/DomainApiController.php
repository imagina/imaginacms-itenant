<?php

namespace Modules\Itenant\Http\Controllers\Api;

use Modules\Core\Icrud\Controllers\BaseCrudController;
//Model
use Modules\Itenant\Entities\Domain;
use Modules\Itenant\Repositories\DomainRepository;

class DomainApiController extends BaseCrudController
{
  public $model;
  public $modelRepository;

  public function __construct(Domain $model, DomainRepository $modelRepository)
  {
    $this->model = $model;
    $this->modelRepository = $modelRepository;
  }
}
