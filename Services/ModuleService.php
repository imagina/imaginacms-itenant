<?php

namespace Modules\Itenant\Services;


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
    public function __construct(array $data,object $organization, $includeModules = false)   
    {
        $this->data = $data;
        $this->organization = $organization;
        $this->includeModules = $includeModules;

        $this->baseTenantConnection = config('asgard.itenant.config.baseTenantConnection');
        $this->optionalModules = config('asgard.itenant.config.optionalModules');

        $this->moduleRepository = app("Modules\Isite\Repositories\ModuleRepository");
    }

    /**
     * Init Installation Modules
     */
    public function init()
    {

        \Log::info($this->log . "INIT");

        //Get Modules to ignore and not install OR Only modules to install
        $modules = $this->getModules($this->data['modules']);

        if(!empty($modules)){

            //Get Tables to migrate and sync
            $tables = $this->getMainTenancyTables(array_column($modules, 'dbPrefix'));

            //Sync tables from base tenant with the tenant created
            foreach ($tables as $tableName) {
                $this->syncTableToTenant($tableName);
            }

            //Copy Media Data
            $this->copyMediaData($modules);

            //Processes to enabled or disabled
            $this->setEnabledModules($modules);

            //Clear Cache Modules | CASE: Only when is installing a Module
            $this->clearCacheModules();
        
        }

        //Process to install module in Background | CASE: Only when creating tenant first time
        $this->seedersIlocations();

        \Log::info($this->log . "END");

    }

     /**
     * Get the main tenancy tables to Migrate
     * @param $prefixes (Tables to not include when migrate)
     */	
    private function getMainTenancyTables($prefixes)
    {

        $include = $this->includeModules;

        // Get all tables from baseTenant
        $tables = \DB::connection($this->baseTenantConnection)->select('SHOW TABLES');

        // Only name tables
        $tables = array_map('current', $tables);

        // Filter the tables based on the include flag
        $filteredTables = array_filter($tables, function ($key) use ($prefixes, $include) {
            foreach ($prefixes as $prefix) {
                if (str_contains($key, $prefix . '__')) {
                    return $include; // Include or exclude based on the flag
                }
            }
            return !$include; // Include or exclude based on the flag
        }, ARRAY_FILTER_USE_BOTH);

        // Retrieve foreign key relationships
        $foreignKeyRelations = \DB::connection($this->baseTenantConnection)->select("
            SELECT TABLE_NAME, REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [config("database.connections.$this->baseTenantConnection.database")]);

        // Create a dependency map
        $dependencyMap = [];
        foreach ($foreignKeyRelations as $relation) {
            $dependencyMap[$relation->TABLE_NAME][] = $relation->REFERENCED_TABLE_NAME;
        }

        // Order tables by dependencies
        $orderedTables = [];
        $visited = [];

        // Helper function for DFS
        $visit = function ($table) use (&$visit, &$orderedTables, &$visited, $dependencyMap) {
            if (isset($visited[$table])) return;
            $visited[$table] = true;

            // Visit dependencies first
            if (isset($dependencyMap[$table])) {
                foreach ($dependencyMap[$table] as $dependency) {
                    $visit($dependency);
                }
            }

            // Add the table to the ordered list
            $orderedTables[] = $table;
        };

        // Perform DFS for all tables
        foreach ($filteredTables as $table) {
            $visit($table);
        }

        // Return the ordered list of tables
        return array_values(array_intersect($orderedTables, $filteredTables));
    }

    /**
     * Get Modules to Install or to Ignore
     */
    private function getModules($modules)
    {   
    
        //Modules to Install
        if($this->includeModules){

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
        }else{
            //Mdoules to ignore
            $modulesFirstLevel = $this->moveToFirstLevel($this->optionalModules);
            // Filter out the existing modules
            return array_diff_key($modulesFirstLevel, array_flip($modules));
        }
    }

    /**
     * Move dependencies to first level
     */
    private function moveToFirstLevel($modules)
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

    /**
     * Sync Table to Tenant
     * @param $tableName (Table to sync)
     */
    private function syncTableToTenant($tableName)
    {   
        if(!\Schema::hasTable($tableName)) {

            
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $moduleName = explode("__", $tableName);

            // Create table schema
            $createTableSql = \DB::connection($this->baseTenantConnection)->select("SHOW CREATE TABLE `$tableName`")[0]->{'Create Table'};
            \DB::statement($createTableSql);

            // Reset auto increment Only case Ilocations
            if($moduleName[0]=='ilocations') \DB::statement("ALTER TABLE `$tableName` AUTO_INCREMENT = 1");

            //Ilocations will be seeder later || No copy data to Media
            if($moduleName[0]!='ilocations' && $moduleName[0]!='media'){

                //insert data table
                $data = \DB::connection($this->baseTenantConnection)->table($tableName)->get();

                if ($data->isNotEmpty()){
                    // Convert the data into an array with keys (column names preserved)
                    $formattedData = $data->map(function ($item) {
                        $itemArray = (array) $item; // Convert each item to an associative array
                        
                        //Set organization id in table if exists and contain data
                        if(isset($itemArray['organization_id']) && !is_null($itemArray['organization_id'])){
                            $itemArray['organization_id'] = $this->organization->id;
                        }
                        return $itemArray;

                    })->toArray();

                    \DB::table($tableName)->insert($formattedData);
                }

            }

            //Update organization Id
            if($tableName == 'itenant__organizations') \DB::table($tableName)->update(['id' => $this->organization->id]);
            
            \Log::info("$this->log syncTableToTenant: $tableName");
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Copy Media Data
     */
    private function copyMediaData($modulesData)
    {
        \Log::info($this->log.'copyMediaData');

        //Get only module names with first letter in uppercase
        $moduleNames = array_map('ucfirst',array_keys($modulesData));

        // Remember if is not include (Case Creating) | Include is Installing
        $regexp = !$this->includeModules ? 'not regexp' : 'regexp';
     
        // Search Imageables in Base Tenant DB with filter $regexp
        $imageables = \DB::connection($this->baseTenantConnection)->table('media__imageables')
        ->where('imageable_type', $regexp, implode('|', array_map(function ($moduleName) {
                return 'Modules\\\\' . $moduleName . '\\\\';
            }, $moduleNames)
        ))->get();

        if ($imageables->isNotEmpty()) 
        {

            $imageablesIds = $imageables->pluck('file_id')->toArray();

            // Search Files in Base Tenant DB
            $files = \DB::connection($this->baseTenantConnection)->table('media__files')
            ->whereIn('id', $imageablesIds)->get();

            //Validation insert data
            if ($files->isNotEmpty()) 
            {
                // Convert the data into an array with keys (column names preserved)
                $filesFormatted = $files->map(function ($item) {
                    $itemArray = (array) $item; // Convert each item to an associative array
                    $itemArray['organization_id'] = $this->organization->id;  // Set organization id in table
                    return $itemArray;
                })->toArray();

                // Insert new files and get the new IDs
                $newFileIds = [];
                foreach ($filesFormatted as $file) {
                    $newFileId = \DB::table('media__files')->insertGetId($file);
                    $newFileIds[$file['id']] = $newFileId;
                }

                // Update imageables with new file IDs and insert data
                $imageables = $imageables->map(function ($imageable) use ($newFileIds) {
                    if (isset($newFileIds[$imageable->file_id])) {
                        $imageable->file_id = $newFileIds[$imageable->file_id];
                    }
                    return (array)$imageable;
                })->toArray();

                // Insert updated imageables
                \DB::table('media__imageables')->insert($imageables);

            }

        }

    }
    
    /* 
     * Modules to set Enabled or Disabled incluiding permissions
     */
    private function setEnabledModules($modules)
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
    private function setStatusPermissions($modules)
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
                    $allPermissions[$entity.".$action"] = $status;
                }
            }
        }
        //Get Role with all permissions in this DB
        $params = ['filter' => ['field' => 'slug']];
        $role = app("Modules\Iprofile\Repositories\RoleApiRepository")->getItem('admin',json_decode(json_encode($params)));

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
        if($this->includeModules){
            \Log::info($this->log . "clearCacheModules");
            \Illuminate\Support\Facades\Cache::flush('*isite_module_all_modules'.(tenant()->id ?? '').'*');
        }
    }

    /**
    * Install Ilocations
    */
    public function seedersIlocations()
    {
        //Only when is ignore modules case | Creating tenant first time
        if(!$this->includeModules){
            \Log::info($this->log.'seedersIlocations');
            \Artisan::call('module:seed', ['module' => "Ilocations"]);
        }
    }

    
   

}
