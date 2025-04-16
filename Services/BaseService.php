<?php

namespace Modules\Itenant\Services;

use Modules\Itenant\Entities\Organization;
use Illuminate\Support\Facades\Storage;

//Services
use Modules\Itenant\Services\ModuleService;
use Modules\Itenant\Services\UserService;
use Modules\Itenant\Services\ThemeService;

/**
 * Class BaseService: Process to create the tenant in the multi database
 */
class BaseService
{

  private $log = "Itenant: BaseService|| ";

  private $baseTenantConnection;
  private $userService;
  private $userIdAdmin;


  /**
   * Construct Base
   */
  public function __construct(UserService $userService)
  {
      $this->userService = $userService;
      $this->baseTenantConnection = config('asgard.itenant.config.baseTenantConnection');
      $this->userIdAdmin = config('asgard.itenant.config.userIdAdmin');
  }

  /**
   * Create Tenant in Multi Database
   */
  public function createTenantInMultiDatabase($data)
  {

    \Log::info('----------------------------------------------------------');
    \Log::info($this->log."createTenantInMultiDatabase|INIT");
    \Log::info('----------------------------------------------------------');

    //Get User Registered in Central Database
    $data['user'] = \Auth::user();

    //All processes to create a Tenant
    $organization = $this->createTenant($data);
    //$organization = Organization::find(7); //Only testing

    //Init Tenant
    \Log::info($this->log."INITIALIZING TenantID: $organization->id");
    tenancy()->initialize($organization->id);

    //Init Installation in Tenant
    $moduleService = app()->makeWith(ModuleService::class, ['data' => $data, 'organization' => $organization, 'baseTenantConnection' => $this->baseTenantConnection]);
    $moduleService->installModules();

    //Process to the Theme (Ibuilder)
    $this->syncThemeData($data,$organization);

    //Process to data module in Background
    $this->seedersIlocations();

    //Post Install Commands Extras
    $this->postInstallCommands();

    //Logout Central User
    \Auth::logout();

    //Update User in Tenant with new data
    $this->userService->updateUser($data,$this->userIdAdmin);


    //Authenticate user tenant and get data auth
    $authData = $this->userService->authenticate($data,$this->userIdAdmin);

    //Get reedirect Url
    $reedirectUrl = $this->createRedirectUrl($organization,$authData);

    \Log::info('----------------------------------------------------------');
    \Log::info($this->log."createTenantInMultiDatabase||END| OrganizationId: $organization->id");
    \Log::info('----------------------------------------------------------');

    return [
      "redirectUrl" => $reedirectUrl
    ];

  }


  /**
   * Create Tenant
   */
  public function createTenant($data)
  {

    \Log::info($this->log."Create Tenant");

    $organization = $this->createOrganization($data);

    $this->setUserOrganization($data,$organization);

    $this->createStoreDisk($organization);

    $this->createDomain($organization);

    return $organization;
  }

  /**
   * Create Organization
   */
  private function createOrganization($data)
  {
    //TODO: Solo se valida el idioma por defecto para crear un tenant?
    $dataToCreate = [
      'user_id' => $data['user']->id,
      'title' => $data[locale()]['title'] ?? $data['user']->present()->fullname,
      'status' => $data['status'] ?? json_decode(setting('itenant::defaultTenantStatus', null, 'true')),
      'enable' => $data['enable'] ?? json_decode(setting('itenant::defaultTenantStatus', null, 'true')),
      'category_id' => $data['category_id'] ?? null,
    ];

    //add options data to save
    if(isset($data['business_description'])){
      $dataToCreate['options'] = ['business_description' => $data['business_description']];
    }

    //validate title doen't exist
    $orgRepository = app('Modules\Itenant\Repositories\OrganizationRepository');
    $organization = $orgRepository->getItem($dataToCreate['title'], json_decode(json_encode([
      'filter' => ['field' => 'title']
    ])));

    //Validate title is available
    if($organization) throw new \Exception(trans(
      'itenant::common.tenant.titleNotAvailable',
      ['title' => $dataToCreate['title']]
    ), 500);

    //Create Organization
    $organization = Organization::create($dataToCreate);

    \Log::info($this->log.'OrganizationId: '.$organization->id);

    return $organization;

  }

  /**
   * Set User Organization
   */
  private function setUserOrganization($data,&$organization)
  {

    $roleId = $data['user']->roles->first()->id;
    $organization->users()->sync([$data["user"]->id =>['role_id' => $roleId]]);
  }

  /**
   * Create Store Disk
   */
  private function createStoreDisk($organization)
  {
    //Create Disk
    Storage::disk('privatemedia')->makeDirectory('organization'.$organization->id);
    Storage::disk('local')->makeDirectory('/storage/organization'.$organization->id);
    Storage::disk('public')->makeDirectory('organization'.$organization->id);
    Storage::disk('publicmedia')->makeDirectory('organization'.$organization->id);
  }

  /**
   * Create Domain
   */
  private function createDomain(&$organization)
  {

    //Base Url Domain
    $configUrl = config('app.url');
    if (config("asgard.itenant.config.tenant.appUrl") && !empty(config("asgard.itenant.config.tenant.appUrl")))
      $configUrl = config("asgard.itenant.config.tenant.appUrl");

    //Create Domain
    $organization->domains()->create([
        'domain' => $data['organization']['domain'] ?? $data['domain'] ?? $organization->slug.'.'.parse_url(config('app.url'), PHP_URL_HOST),
        'type' => 'default',
    ]);

    \Log::info($this->log.'Domain: '.$organization->domain);

  }

  /**
  * Init Processes to Theme (Module Ibuilder)
  */
  private function syncThemeData(array $data, $organization)
  {

    $themeService = app()->makeWith(ThemeService::class, [
      'layoutId' => $data['layout_id'],
      'baseConnection' => $this->baseTenantConnection,
      'organization' => $organization
    ]);

    $themeService->init();

  }

  /**
   * Install Ilocations
   */
  public function seedersIlocations()
  {

    \Log::info($this->log . 'seedersIlocations');
    \Artisan::call('module:seed', ['module' => "Ilocations"]);

  }

  /**
   * Post Install Commands
   */
  private function postInstallCommands()
  {
    \Log::info($this->log . "postInstallCommands");

    $options = ['commandname' => 'passport:install', '--tenants' => [tenant()->id]];
    \Artisan::call('tenants:run', $options);
    //\Log::info(\Artisan::output());
  }

  /**
   * Get Reedirect Url
   */
  private function createRedirectUrl($organization,$authData)
  {

    $base = "https://" . $organization->domain . "/iadmin/#/";
    $redirectUrl = $base."?authbearer=" . str_replace("Bearer ", "", $authData['token'] . "&expiresatbearer=" . urlencode($authData['expiresAt']));

    return $redirectUrl;
  }


}
