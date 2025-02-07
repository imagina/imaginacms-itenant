<?php

namespace Modules\Itenant\Console;

use Illuminate\Console\Command;

use Modules\Itenant\Entities\Organization;

class ModuleDbUpdate extends Command
{

  protected $signature = 'itenant:module-db-update {--tenants=}';
  protected $description = 'Run module migrations and seeders for all organizations';

  private $log = "Itenant::Command||ModuleDbUpdate|";

  /**
   * Execute the console command.
   */
  public function handle()
  {

    $this->info($this->log.'INIT');

    //Get Options
    $tenantIdsOption = $this->option('tenants');
    $tenantIds = $tenantIdsOption ? explode(',', $tenantIdsOption) : [];

    //Get Specific organizations or get all
    $organizations = !empty($tenantIds) ?
      Organization::whereIn('id', $tenantIds)->get() :
      Organization::all();

    //Process to Organizations
    $organizations->each(function ($organization) {
      $this->updateProcess($organization);
    });

    $this->info($this->log.'END');
  }

  /**
   *  Processes
   */
  private function updateProcess(object $organization)
  {

     //La organizacion 1 es la base | Omitir por ahora hasta que se hagan mas pruebas en organizaciones diferentes, verifique funcionamiento etc
    if ($organization->id == 1) {
      $this->info("Skipping migration for OrganizationId: {$organization->id}");
      return;
    }

    //Initialize
    tenancy()->initialize($organization);

    $this->info("============================================================");
    $this->info("INIT OrganizationId: {$organization->id}");
    $this->info("============================================================");

    // Run module migrations
    $this->info("Migrating modules");
    $this->call('module:migrate');

    // Run module Seeders
    $this->info("Seeders modules");
    $this->call('module:seed');

    $this->info("============================================================");
    $this->info("END OrganizationId: {$organization->id}");
    $this->info("============================================================");

    // Reset the connection
    tenancy()->end();

  }

}
