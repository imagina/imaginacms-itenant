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

  private $migrateService;

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

    $this->migrateService = app()->makeWith(MigrateService::class, [
      'baseConnection' => $this->baseTenantConnection,
      'includeModules' => $this->includeModules,
      'organization' => $this->organization
    ]);

  }

  /**
   * Installation Modules
   * used by: Endpoint CreateTenant, ManageModules Method
   */
  public function installModules()
  {

    \Log::info('------------------------------------------------');
    \Log::info($this->log . "installModules|INIT");
    \Log::info('------------------------------------------------');

    //Get Modules to "ignore and not install" OR "Only modules to install" | (With dependences)
    $modules = $this->getModules($this->data['modules']);

    //Extra Validation
    if (empty($modules))
      throw new \Exception('There are no modules to process (Modules already installed, if you need to enable them again or disable them, use the [enabled] attribute)',500);

    //Processes to migrate
    $this->migrateService->migrateAndCopyDataFromModules($modules);

    //Processes to enabled or disabled (This includes permissions processes)
    $this->setEnabledModules($modules);

    //Clear Cache Modules
    $this->clearCacheModules();

    \Log::info('------------------------------------------------');
    \Log::info($this->log . "installModules|END");
    \Log::info('------------------------------------------------');

  }

  /**
   * Manage modules process with enable attribute or not
   * used by: Endpoint ManageModules
   */
  public function manageModules()
  {
    \Log::info($this->log."Manage Modules");

    //Only active or desactive module
    if(isset($this->data['enabled'])){

      //Get All Modules to set enable or disabled
      $modules = $this->getModules($this->data['modules']);

      //Check all modules
      foreach ($modules as $key => $module) {
        //Module not installed
        if (isset($module['installed']) && !$module['installed']) {
          $this->migrateService->migrateAndCopyDataFromModules([$key => $module]);
        }
      }

      //Processes to enabled or disabled (All Modules)
      $this->setEnabledModules($modules);
      $this->clearCacheModules();

    }else{

      //Only install modules
      $this->installModules();

    }

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
        'alias' => ['where' => 'in', 'value' => $modules]
      ]];

      //Caso en que solo sea proceso de instalacion
      if(!isset($this->data['enabled'])){
        $params['filter']['enabled'] = 0;
        $params['filter']['installed'] = 0;
      }

      //Get Modules
      $modulesData = $this->moduleRepository->getItemsBy(json_decode(json_encode($params)));

      //Get only this attrs
      $modulesAttributes = $modulesData->pluck('installed', 'alias')->toArray();

      //Intersect to get dependences
      $matchedModules = array_intersect_key($this->optionalModules, $modulesAttributes);

      // Add 'installed' to matchedModules
      foreach ($matchedModules as $alias => &$details) {
          $details['installed'] = $modulesAttributes[$alias];
      }

      //move and return data
      return $this->moveToFirstLevel($matchedModules);

    } else {
      //Modules to ignore
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

    //Esto es para el caso de Manage modulos, se valida con la variable que se envia y no con el include modules
    $enabled = isset($this->data['enabled']) ? $this->data['enabled'] : (int)$this->includeModules;
    $installed = (int)$this->includeModules;

    //Get only module names
    $modulesIndex = array_keys($modules);

    //set enabled or disabled
    \DB::table('isite__modules')->whereIn('alias', $modulesIndex)->update(['enabled' => $enabled,'installed'=> $installed]);

    $this->setStatusPermissions($modules);
  }

  /**
   * Set Status in Permissions to Modules
   */
  private function setStatusPermissions(array $modules)
  {
    \Log::info($this->log . "setStatusPermissions");

    $status = isset($this->data['enabled']) ? $this->data['enabled'] : $this->includeModules;

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
          $allPermissions[$entity . ".$action"] = (boolean)$status;
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
