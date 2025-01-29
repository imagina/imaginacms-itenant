<?php

namespace Modules\Itenant\Http\Controllers\Api;

use Modules\Core\Icrud\Controllers\BaseCrudController;
//Model
use Modules\Itenant\Entities\Category;
use Modules\Itenant\Repositories\CategoryRepository;

class CategoryApiController extends BaseCrudController
{
  public $model;
  public $modelRepository;

  public function __construct(Category $model, CategoryRepository $modelRepository)
  {
    $this->model = $model;
    $this->modelRepository = $modelRepository;
  }
}
