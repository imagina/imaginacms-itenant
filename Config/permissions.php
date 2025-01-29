<?php

return [
    'itenant.domains' => [
        'manage' => 'itenant::domains.manage resource',
        'index' => 'itenant::domains.list resource',
        'create' => 'itenant::domains.create resource',
        'edit' => 'itenant::domains.edit resource',
        'destroy' => 'itenant::domains.destroy resource',
        'restore' => 'itenant::domains.restore resource',
    ],
    'itenant.organizations' => [
        'manage' => 'itenant::organizations.manage resource',
        'index' => 'itenant::organizations.list resource',
        'index-all' => 'isite::organizations.list resource',
        'create' => 'itenant::organizations.create resource',
        'edit' => 'itenant::organizations.edit resource',
        'destroy' => 'itenant::organizations.destroy resource',
        'restore' => 'itenant::organizations.restore resource',
    ],
    'itenant.userorganization' => [
        'manage' => 'itenant::userorganization.manage resource',
        'index' => 'itenant::userorganization.list resource',
        'create' => 'itenant::userorganization.create resource',
        'edit' => 'itenant::userorganization.edit resource',
        'destroy' => 'itenant::userorganization.destroy resource',
        'restore' => 'itenant::userorganization.restore resource',
    ],
    'itenant.categories' => [
        'manage' => 'itenant::categories.manage resource',
        'index' => 'itenant::categories.list resource',
        'create' => 'itenant::categories.create resource',
        'edit' => 'itenant::categories.edit resource',
        'destroy' => 'itenant::categories.destroy resource',
        'restore' => 'itenant::categories.restore resource',
    ],
// append




];
