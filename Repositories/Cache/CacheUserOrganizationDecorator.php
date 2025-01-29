<?php

namespace Modules\Itenant\Repositories\Cache;

use Modules\Itenant\Repositories\UserOrganizationRepository;
use Modules\Core\Icrud\Repositories\Cache\BaseCacheCrudDecorator;

class CacheUserOrganizationDecorator extends BaseCacheCrudDecorator implements UserOrganizationRepository
{
    public function __construct(UserOrganizationRepository $userorganization)
    {
        parent::__construct();
        $this->entityName = 'itenant.userorganization';
        $this->repository = $userorganization;
    }
}
