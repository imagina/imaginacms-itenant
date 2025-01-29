<?php

namespace Modules\Itenant\Repositories\Cache;

use Modules\Itenant\Repositories\OrganizationRepository;
use Modules\Core\Icrud\Repositories\Cache\BaseCacheCrudDecorator;

class CacheOrganizationDecorator extends BaseCacheCrudDecorator implements OrganizationRepository
{
    public function __construct(OrganizationRepository $organization)
    {
        parent::__construct();
        $this->entityName = 'itenant.organizations';
        $this->repository = $organization;
    }
}
