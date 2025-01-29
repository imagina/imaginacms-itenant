<?php

namespace Modules\Itenant\Http\Controllers\Api;

use Modules\Core\Icrud\Controllers\BaseCrudController;
//Model
use Modules\Itenant\Entities\Organization;
use Modules\Itenant\Repositories\OrganizationRepository;
use Illuminate\Http\Request;

use Modules\Itenant\Http\Requests\CreateOrganizationRequest;
use Modules\Itenant\Http\Requests\InstallModulesRequest;

use Modules\Itenant\Services\ModuleService;

class OrganizationApiController extends BaseCrudController
{
  public $model;
  public $modelRepository;

  private $log = "Itenant: OrganizationApiController|| ";

  public function __construct(
    Organization $model, 
    OrganizationRepository $modelRepository)
  {
    $this->model = $model;
    $this->modelRepository = $modelRepository;
  }

  /**
   * Create Organization Tenant
   */
  public function create(Request $request)
  {

    try {
    
      //\DB::beginTransaction();

      //Get data
      $data = $request->input('attributes');

      //Validate Request
      $this->validateRequestApi(new CreateOrganizationRequest((array) $data));

      $token = $request->header('Authorization');

      //Login with this method because with middleware errors appeared with passport
      $userLogged = app("Modules\Iprofile\Services\UserService")->loginWithToken($token);
      if (is_null($userLogged)) throw new \Exception('User Not Logged', 401);

      //Service
      //$response = $this->tenantService->createTenantInMultiDatabase($data);
      $baseService= app("Modules\Itenant\Services\BaseService");
      $response = $baseService->createTenantInMultiDatabase($data);

      //\DB::commit();//Commit to DataBase

      $response = ['data' => $response];
    } catch (\Exception $e) {
      //\DB::rollback();//Rollback to Data Base
      $status = $this->getStatusError($e->getCode());
      \Log::error($e);
      $response = ["errors" => $e->getMessage()];
    }

    return response()->json($response, $status ?? 200);
  }

  /**
   * Add Module - Tenant
   * New Version
   */
  public function installModules(Request $request)
  {

    try {
      
      //Get data
      $data = $request->input('attributes');

      //Validate Request
      $this->validateRequestApi(new InstallModulesRequest((array) $data));

      //Init Tenant
      \Log::info($this->log . "INITIALIZING TenantID: " . $data['organization_id']);
      tenancy()->initialize($data["organization_id"]);

      //Module Service with Params
      $moduleService = app()->makeWith(ModuleService::class, ['data' => $data, 'organization' => tenant(), 'includeModules' => true]);
      $moduleService->init();

      $response = ['data' => 'Process finished'];
    } catch (\Exception $e) {
      $status = $this->getStatusError($e->getCode());
      \Log::error($e);
      $response = ["errors" => $e->getMessage()];
    }

    return response()->json($response, $status ?? 200);
  }

}
