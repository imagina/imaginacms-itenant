<?php

namespace Modules\Itenant\Services;

use Modules\Itenant\Entities\Organization;
use Illuminate\Support\Facades\Storage;

//Services
use Modules\Itenant\Services\ModuleService;
use Modules\Itenant\Services\UserService;

/**
 * Class BaseService: Process to create the tenant in the multi database
 */
class BaseService
{

  private $log = "Itenant: BaseService|| ";
  private $authApi;

  private $userService;
  private $userIdAdmin;


  /**
   * Construct Base
   */
  public function __construct(UserService $userService)
  {
      $this->userService = $userService;
      $this->userIdAdmin = config('asgard.itenant.config.userIdAdmin');
  }

  /**
   * Create Tenant in Multi Database
   */
  public function createTenantInMultiDatabase($data)
  {

    \Log::info($this->log."createTenantInMultiDatabase|START");

    //Get User Registered in Central Database
    $data['user'] = \Auth::user();
   
    //All processes to create a Tenant
    $organization = $this->createTenant($data);
    //$organization = Organization::find(10); //Only testing

    //Init Tenant | TODO: Maybe move this inside module service
    \Log::info($this->log."INITIALIZING TenantID: $organization->id");
    tenancy()->initialize($organization->id);

    //Init Installation in Tenant
    $moduleService = app()->makeWith(ModuleService::class, ['data' => $data, 'organization' => $organization]);
    $moduleService->init();

    //Post Install Commands Extras
    $this->postInstallCommands();

    //Logout Central User
    \Auth::logout();

    //Update User in Tenant with new data
    $this->userService->updateUser($data,$this->userIdAdmin);

    
    //TODO: Update rol in Central DB

    //Authenticate user tenant and get data auth
    $authData = $this->userService->authenticate($data,$this->userIdAdmin);

    //Get reedirect Url
    $reedirectUrl = $this->createRedirectUrl($organization,$authData);

    
    \Log::info($this->log."createTenantInMultiDatabase||FINISHED| => OrganizationId: $organization->id");

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

    $dataToCreate = [
      'user_id' => $data['user']->id,
      'title' => $data[locale()]['title'] ?? $data['user']->present()->fullname,
      'status' => $data['status'] ?? json_decode(setting('itenant::defaultTenantStatus', null, 'true')),
      'layout_id' => $data['layout_id'] ?? json_decode(setting('itenant::defaultLayout', null, null)),
      'enable' => $data['enable'] ?? json_decode(setting('itenant::defaultTenantStatus', null, 'true')),
      'category_id' => $data['category_id'] ?? null,
    ];
    
    //Create Organization
    $organization = Organization::create($dataToCreate);

    $this->setUserOrganization($data,$organization);

    $this->createStoreDisk($organization);

    $this->createDomain($organization);

    //Log Infor
    \Log::info('----------------------------------------------------------');
    \Log::info('Created Organization Id: '.$organization->id.' | Domain: '.$organization->domain);
    \Log::info('----------------------------------------------------------');

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