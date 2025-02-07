<?php

namespace Modules\Itenant\Services;

use Modules\Core\Icrud\Services\MigrateService;


class ModuleService
{

  private $log = "Itenant: ModuleService|| ";
  private $baseTenantConnection;

  private $organization;
  private $data;
  private $optionalModules;
  private $includeModules;
  private $moduleRepository;

  /**
   * @param $data (From Request and from Base Service)
   * @param $organization (Organization created)
   * @param $includeModules (Flag that determines if the modules that are obtained are the ones to be ignored or installed)
   */
  public function __construct(array $data, object $organization, $baseTenantConnection = null, $includeModules = false)
  {
    $this->data = $data;
    $this->organization = $organization;
    $this->includeModules = $includeModules;

    $this->baseTenantConnection = $baseTenantConnection ?? config('asgard.itenant.config.baseTenantConnection');
    $this->optionalModules = config('asgard.itenant.config.optionalModules');

    $this->moduleRepository = app("Modules\Isite\Repositories\ModuleRepository");
  }

  /**
   * Init Installation Modules
   */
  public function init()
  {

    \Log::info('------------------------------------------------');
    \Log::info($this->log . "INIT");
    \Log::info('------------------------------------------------');

    //Get Modules to ignore and not install OR Only modules to install
    $modules = $this->getModules($this->data['modules']);

    if (!empty($modules)) {

      $migrateService = app()->makeWith(MigrateService::class, [
        'baseConnection' => $this->baseTenantConnection,
        'includeModules' => $this->includeModules,
        'organization' => $this->organization
      ]);

      //Get Tables to migrate and sync
      $tables = $migrateService->getMainTables(array_column($modules, 'dbPrefix'));

      //Sync tables from base tenant with the tenant created
      foreach ($tables as $tableName) {
        $migrateService->syncTable($tableName);
      }

      //Copy Media Data
      $migrateService->copyMediaData($modules);

      //Copy Fillable Data
      $migrateService->copyFillableData($modules);

      //Processes to enabled or disabled
      $this->setEnabledModules($modules);

      //Clear Cache Modules | CASE: Only when is installing a Module
      $this->clearCacheModules();
    }


    \Log::info('------------------------------------------------');
    \Log::info($this->log . "END");
    \Log::info('------------------------------------------------');

  }


  /**
   * Get Modules to Install or to Ignore
   */
  private function getModules(array $modules)
  {

    //Modules to Install
    if ($this->includeModules) {

      //Check if the modules to install are disabled
      $params = ['filter' => [
        'alias' => ['where' => 'in', 'value' => $modules],
        'enabled' => 0
      ]];
      $modulesData = $this->moduleRepository->getItemsBy(json_decode(json_encode($params)));

      //Get Alias
      $modulesAlias = $modulesData->pluck('alias')->toArray();
      //Intersect to get dependences
      $matchedModules = array_intersect_key($this->optionalModules, array_flip($modulesAlias));

      return $this->moveToFirstLevel($matchedModules);
    } else {
      //Mdoules to ignore
      $modulesFirstLevel = $this->moveToFirstLevel($this->optionalModules);
      // Filter out the existing modules
      return array_diff_key($modulesFirstLevel, array_flip($modules));
    }
  }

  /**
   * Move dependencies to first level
   */
  private function moveToFirstLevel(array $modules)
  {
    // Extraer dependencias a primer nivel
    foreach ($modules as $module => $details) {
      if (isset($details['dependencies'])) {
        foreach ($details['dependencies'] as $dependency => $dependencyDetails) {
          if (!isset($modules[$dependency])) {
            $modules[$dependency] = $dependencyDetails;
          }
        }
        unset($modules[$module]['dependencies']);
      }
    }

    return $modules;
  }

  /*
     * Modules to set Enabled or Disabled incluiding permissions
     */
  private function setEnabledModules(array $modules)
  {
    \Log::info($this->log . "setEnabledModules");

    $enabled = (int)$this->includeModules;

    //Get only module names
    $modulesIndex = array_keys($modules);

    //set enabled or disabled
    \DB::table('isite__modules')->whereIn('alias', $modulesIndex)->update(['enabled' => $enabled]);

    $this->setStatusPermissions($modules);
  }

  /**
   * Set Status in Permissions to Modules
   */
  private function setStatusPermissions(array $modules)
  {
    \Log::info($this->log . "setStatusPermissions");

    $status = $this->includeModules;

    //Get only module names
    $modulesIndex = array_keys($modules);

    //Get Modules
    $params = ['filter' => ['alias' => $modulesIndex]];
    $modulesData = $this->moduleRepository->getItemsBy(json_decode(json_encode($params)));

    $allPermissions = [];

    //Check modules with permissions
    foreach ($modulesData as $module) {
      foreach ($module->permissions ?? [] as $entity => $permissions) {
        foreach ($permissions as $action => $permission) {
          $allPermissions[$entity . ".$action"] = $status;
        }
      }
    }
    //Get Role with all permissions in this DB
    $params = ['filter' => ['field' => 'slug']];
    $role = app("Modules\Iprofile\Repositories\RoleApiRepository")->getItem('admin', json_decode(json_encode($params)));

    //Update Permissions in Role
    $role->permissions = array_merge($role->permissions, $allPermissions);
    $role->save();
  }

  /**
   * Clear Cache Modules
   */
  private function clearCacheModules()
  {
    //Only when is installing a Module
    if ($this->includeModules) {
      \Log::info($this->log . "clearCacheModules");
      \Illuminate\Support\Facades\Cache::flush('*isite_module_all_modules' . (tenant()->id ?? '') . '*');
    }
  }


}
