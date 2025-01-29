<?php

namespace Modules\Itenant\Repositories\Cache;

use Modules\Itenant\Repositories\CategoryRepository;
use Modules\Core\Icrud\Repositories\Cache\BaseCacheCrudDecorator;

class CacheCategoryDecorator extends BaseCacheCrudDecorator implements CategoryRepository
{
    public function __construct(CategoryRepository $category)
    {
        parent::__construct();
        $this->entityName = 'itenant.categories';
        $this->repository = $category;
    }
}
