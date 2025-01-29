<?php

namespace Modules\Itenant\Repositories\Cache;

use Modules\Itenant\Repositories\DomainRepository;
use Modules\Core\Icrud\Repositories\Cache\BaseCacheCrudDecorator;

class CacheDomainDecorator extends BaseCacheCrudDecorator implements DomainRepository
{
    public function __construct(DomainRepository $domain)
    {
        parent::__construct();
        $this->entityName = 'itenant.domains';
        $this->repository = $domain;
    }
}
