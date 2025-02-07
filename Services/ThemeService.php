<?php

namespace Modules\Itenant\Services;

use Modules\Core\Icrud\Services\MigrateService;

class ThemeService
{

  private $log = "Itenant: ThemeService|| ";

  private $modulesPrefix = ['ibuilder'] ;
  private $migrateService;
  private $layoutId;

  /**
   * Contruct
   */
  public function __construct(int $layoutId,$baseConnection, object $organization)
  {
    $this->layoutId = $layoutId;
    $this->baseConnection = $baseConnection;
    $this->organization = $organization;

    $this->migrateService = app()->makeWith(MigrateService::class, [
      'baseConnection' => $this->baseConnection,
      'includeModules' => true,//Is installation
      'organization' => $this->organization
    ]);

  }

  /**
   * Init method with all processes
   */
  public function init()
  {

    \Log::info('------------------------------------------------');
    \Log::info($this->log . "INIT");
    \Log::info('------------------------------------------------');

    //Create Ibuilder Tables
    $this->createTables();

    //Validation existLayout
    $existLayout = \DB::table('ibuilder__layouts')->where('id', $this->layoutId)->first();

    //Continue with Processes
    if(is_null($existLayout)){

      //Process to create Layout Base
      $optionsLayout = $this->createLayout($this->layoutId);

      //Create block to this layout | Block relations | Media Block
      $this->createBlocks($this->layoutId);

      $this->addbuildables();

      //Main Layout has anothers layouts
      if(!is_null($optionsLayout))
      {
        \Log::info($this->log . 'createLayouts|Layout has Options Layouts');
        $optionsLayout2 = json_decode($optionsLayout);
        foreach ($optionsLayout2 as $key => $layoutId) {
            $result = $this->createLayout($layoutId);
            $this->createBlocks($layoutId);
        }
      }


    }

    \Log::info('------------------------------------------------');
    \Log::info($this->log . "END");
    \Log::info('------------------------------------------------');

  }

  /**
   * Create Ibuilder Tables if not exists
   */
  private function createTables()
  {

    if (!\Schema::hasTable("ibuilder__layouts"))
    {
      \Log::info($this->log . 'createTables');

      //Get Tables to migrate and sync
      $tables = $this->migrateService->getMainTables($this->modulesPrefix);

      //Sync tables from base
      foreach ($tables as $tableName) {
        $this->migrateService->syncTable($tableName,false); //False to not Copy data for this case
      }

    }

  }

  /**
   * Create Layouts with Translations
   */
  private function createLayout(int $layoutId)
  {

    \Log::info($this->log . 'createLayouts|LayoutId: '.$layoutId);

    //Get data from Base Connection
    $baseLayout = \DB::connection($this->baseConnection)->table('ibuilder__layouts')->where('id', $layoutId)->first();
    $baseLayoutTranslations = \DB::connection($this->baseConnection)->table('ibuilder__layout_translations')->where('layout_id', $layoutId)->get();

    //Set infor to base layout
    if($baseLayout->type=='home') $baseLayout = $this->setDataToBaseLayout($baseLayout);

    //baseLayoutTranslations To Array
    $baseLayoutTranslationsArray = $baseLayoutTranslations->map(function($item) { return (array) $item; })->toArray();

    //Insert Data
    \Log::info($this->log . 'createLayouts|Inserting Layouts..');
    \DB::table('ibuilder__layouts')->insert((array)$baseLayout);
    \Log::info($this->log . 'createLayouts|Inserting Layouts Translations..');
    \DB::table('ibuilder__layout_translations')->insert($baseLayoutTranslationsArray);

    //Return Base Layout
    return $baseLayout->options;

  }

  /**
   * Set Information to Base Layout
   */
  private function setDataToBaseLayout(object $baseLayout)
  {

    \Log::info($this->log . 'setDataToBaseLayout');

    $baseLayout->organization_id = $this->organization->id;
    $baseLayout->default = 1;
    $baseLayout->status = 1;

    return $baseLayout;
  }

  /**
   * Create Blocks with Translations and Layout Blocks relation
   */
  private function createBlocks(int $layoutId)
  {

    \Log::info($this->log . 'createBlocks');

    //Get Blocks from Relation Layout Blocks in Base Connection
    $baseBlocksFromRelation = \DB::connection($this->baseConnection)->table('ibuilder__layout_blocks')->where('layout_id', $layoutId)->get();
    $blockIds = $baseBlocksFromRelation->pluck('block_id');

    //Get Infor Blocks in Base Connection
    $baseBlocks = \DB::connection($this->baseConnection)->table('ibuilder__blocks')->whereIn('id', $blockIds)->get();
    $baseBlocksTranslations = \DB::connection($this->baseConnection)->table('ibuilder__block_translations')->whereIn('block_id', $blockIds)->get();

    //Convert to Array to insert and set organization id | Blocks
    $baseBlocksArray = $baseBlocks->map(function($item) {
      $itemArray = (array) $item;
      if (array_key_exists('organization_id', $itemArray))
        $itemArray['organization_id'] = $this->organization->id;
      return $itemArray;
    })->toArray();

    //Convert to Array Translations to insert
    $baseBlocksTranslationsArray = $baseBlocksTranslations->map(function($item) { return (array) $item; })->toArray();

    //Insert| Blocks and Block translations
    \Log::info($this->log . 'createBlocks|Inserting Blocks..');
    \DB::table('ibuilder__blocks')->insert($baseBlocksArray);
    \Log::info($this->log . 'createBlocks|Inserting Blocks Translations..');
    \DB::table('ibuilder__block_translations')->insert($baseBlocksTranslationsArray);

    //Convert to Array to insert and set organization id | Layout Blocks
    $baseBlocksFromRelationArray = $baseBlocksFromRelation->map(function($item) {
      $itemArray = (array) $item;
      if (array_key_exists('organization_id', $itemArray))
        $itemArray['organization_id'] = $this->organization->id;
      return $itemArray;
    })->toArray();

    //Insert | Layout Blocks Relation
    \Log::info($this->log . 'createBlocks|Inserting Layout Blocks..');
    \DB::table('ibuilder__layout_blocks')->insert($baseBlocksFromRelationArray);

    //BlocksIds to search the infor and copy
    $this->copyMediaBlocks($blockIds);

    //BlocksIds to search the infor and copy
    $this->copyFillableBlocks($blockIds);

  }

  /**
   * Procces to copy blocks to media
   */
  private function copyMediaBlocks($blockIds)
  {

    \Log::info($this->log . 'copyMediaBlocks');

    $queryWhere = function($query) use ($blockIds) {
      $query->where('imageable_type', 'Modules\\Ibuilder\\Entities\\Block')
            ->whereIn('imageable_id', $blockIds);
    };

    $this->migrateService->copyMediaData(null,$queryWhere);


  }

  /**
   *  Procces to copy blocks to ifillable
   */
  private function copyFillableBlocks($blockIds)
  {

    \Log::info($this->log . 'copyFillableBlocks');

    $queryWhere = function($query) use ($blockIds) {
      $query->where('entity_type', 'Modules\\Ibuilder\\Entities\\Block')
            ->whereIn('entity_id', $blockIds);
    };

    $this->migrateService->copyFillableData(null,$queryWhere);

  }

  /**
   * Add Buildables
   */
  private function addBuildables()
  {
    \Log::info($this->log . 'addBuildables');

    $data = [
      "entity_id" => 1,
      "entity_type" => "Modules\Page\Entities\Page",
      "type" => "home",
      "organization_id" => $this->organization->id
    ];
    \DB::table('ibuilder__buildables')->insert($data);

  }

}
