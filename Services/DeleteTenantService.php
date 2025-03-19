<?php

namespace Modules\Itenant\Services;

use Modules\Itenant\Entities\Organization;
use Modules\Itenant\Entities\UserOrganization;
use Illuminate\Support\Facades\Storage;

/**
 * Class DeleteTenantService: Handles complete deletion of a tenant
 */
class DeleteTenantService
{
  private $log = "Itenant: DeleteTenantService || ";

  /**
   * Delete Tenant and all related data from the central database
   */
  public function deleteTenantInMultiDatabase($organizationId)
  {
    \Log::info('----------------------------------------------------------');
    \Log::info($this->log . "deleteTenantFromCentral|INIT OrganizationId: $organizationId");
    \Log::info('----------------------------------------------------------');

    try {
      // Find the organization
      $organization = Organization::find($organizationId);
      if (!$organization) {
        throw new \Exception("Organization not found with ID: $organizationId", 404);
      }

      // Retrieve tenant DB credentials
      $tenantData = json_decode($organization->data, true);
      if (!isset($tenantData['tenancy_db_name'])) {
        throw new \Exception("Missing tenant database details for Organization ID: $organizationId", 500);
      }

      $dbName = $tenantData['tenancy_db_name'];

      // Drop the Tenant Database
      \Log::info($this->log . "Dropping Tenant Database: $dbName");
      $this->dropTenantDatabase($dbName);

      // Delete Storage Directories
      \Log::info($this->log . "Deleting Storage Directories...");
      $this->deleteStoreDisk($organization);

      // Delete Domains
      \Log::info($this->log . "Deleting Tenant Domains...");
      $organization->domains()->delete();

      // Delete User Organization Relations
      \Log::info($this->log . "Deleting User Organization Relations...");
      UserOrganization::where('organization_id', $organizationId)->delete();

      // Delete Organization Record
      \Log::info($this->log . "Deleting Organization Record...");
      $organization->delete();

      \Log::info('----------------------------------------------------------');
      \Log::info($this->log . "deleteTenantFromCentral|SUCCESS | OrganizationId: $organizationId");
      \Log::info('----------------------------------------------------------');

      return [
        "status" => "success",
        "message" => "Tenant deleted successfully",
      ];

    } catch (\Exception $e) {
      \Log::error($this->log . "deleteTenantFromCentral|ERROR: " . $e->getMessage());
      return [
        "status" => "error",
        "message" => $e->getMessage(),
      ];
    }
  }

  /**
   * Drop the tenant's database
   */
  private function dropTenantDatabase($dbName)
  {
    \Log::info($this->log . "Executing DROP DATABASE for: $dbName");
    \DB::statement("DROP DATABASE IF EXISTS `$dbName`");
    \Log::info($this->log . "Database $dbName deleted successfully.");
  }

  /**
   * Delete Store Disk for a Tenant
   */
  private function deleteStoreDisk($organization)
  {
    \Log::info($this->log . "Deleting storage folders for organization: " . $organization->id);

    Storage::disk('privatemedia')->deleteDirectory('organization' . $organization->id);
    Storage::disk('local')->deleteDirectory('/storage/organization' . $organization->id);
    Storage::disk('public')->deleteDirectory('organization' . $organization->id);
    Storage::disk('publicmedia')->deleteDirectory('organization' . $organization->id);
  }
}
