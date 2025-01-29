<?php

return [


  'defaultTenantStatus' => [
    'value' => true,
    'name' => 'itenant::defaultTenantStatus',
    'type' => 'select',
    'groupName' => 'general',
    'groupTitle' => 'itenant::common.settingGroups.general',
    'colClass' => 'col-6',
    'props' => [
      'label' => 'itenant::common.settings.tenant.defaultTenantStatus',
      'useInput' => false,
      'useChips' => false,
      'multiple' => false,
      'hideDropdownIcon' => true,
      'newValueMode' => 'add-unique',
      'options' => [
        ['label' => 'Activo', 'value' => true],
        ['label' => 'Inactivo', 'value' => false]
      ]
    ]
  ],

  'tenantWithCentralData' => [
    'value' => [],
    'name' => 'itenant::tenantWithCentralData',
    'groupName' => 'singledatabase',
    'groupTitle' => 'itenant::common.settingGroups.singledatabase',
    "onlySuperAdmin" => true,
    'type' => 'select',
    'columns' => 'col-6',
    'props' => [
      'label' => 'itenant::common.settings.tenant.tenantWithCentralData',
      'useInput' => false,
      'useChips' => true,
      'multiple' => true,
      'hideDropdownIcon' => true,
      'newValueMode' => 'add-unique',
      'options' => [
        ['label' => 'itenant::common.settings.tenant.entities.setting', 'value' => 'setting'],
        ['label' => 'itenant::common.settings.tenant.entities.page', 'value' => 'page'],
        ['label' => 'itenant::common.settings.tenant.entities.slider', 'value' => 'slider'],
        ['label' => 'itenant::common.settings.tenant.entities.slide', 'value' => 'slide'],
        ['label' => 'itenant::common.settings.tenant.entities.menu', 'value' => 'menu'],
        ['label' => 'itenant::common.settings.tenant.entities.menuitem', 'value' => 'menuitem'],
      ]
    ]
  ],


];
