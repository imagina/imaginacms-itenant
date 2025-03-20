<?php

namespace Modules\Itenant\Services;

use Modules\Itenant\Entities\Organization;
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
      // Delete Storage Directories
      \Log::info($this->log . "Deleting Storage Directories...");
      $this->deleteStoreDisk($organizationId);

      // Find the organization
      $organization = Organization::find($organizationId);
      if (!$organization) {
        throw new \Exception("Organization not found with ID: $organizationId", 404);
      }

      // Retrieve tenant DB credentials
      $dbName = $organization->tenancy_db_name;
      if (!$dbName) {
        throw new \Exception("Missing tenant database details for Organization ID: $organizationId", 500);
      }

      // Drop the Tenant Database
      \Log::info($this->log . "Dropping Tenant Database: $dbName");
      $this->dropTenantDatabase($dbName);

      // Delete Domains
      \Log::info($this->log . "Deleting Tenant Domains...");
      $organization->domains()->forceDelete();

      // Delete User Organization Relations
      \Log::info($this->log . "Deleting User Organization Relations...");
      $organization->users()->detach();

      // Delete Organization Record
      \Log::info($this->log . "Deleting Organization Record...");
      $organization->forceDelete();

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
  private function deleteStoreDisk($organizationId)
  {
    \Log::info($this->log . "Deleting storage folders for organization: " . $organizationId);

    Storage::disk('privatemedia')->deleteDirectory('organization' . $organizationId);
    Storage::disk('local')->deleteDirectory('/storage/organization' . $organizationId);
    Storage::disk('public')->deleteDirectory('organization' . $organizationId);
    Storage::disk('publicmedia')->deleteDirectory('organization' . $organizationId);
  }
}
