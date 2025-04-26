<?php

namespace Modules\Itenant\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

class IAContent implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  private $modulesConfig;
  private $organization;
  private $modules;
  private $log;

  public function __construct($params = [])
  {
    $this->organization = $params['organization'];
    $this->modules = $params['modules'] ?? [];
    $this->modulesConfig = [
      'iblog' => [
        'category' => [
          'translatedAttributes' => 'title,description, slug',
          'mediaZones' => ['mainimage' => 1],
          'module' => 'Blog',
          'module_type' => 'post-category',
          'extraPrompt' => 'generate high-level thematic or organizational categories suitable for the module (e.g., "Recipes", "Health Tips", "Traditional Dishes").',
          'repository' => 'Modules\Iblog\Repositories\CategoryRepository',
          'requestParams' => ['include' => ['files'], 'filter' => [
            'slug' => ['where' => 'notIn', 'value' => ['blog', 'servicios', 'categoria-principal']]
          ]],
        ],
        'post' => [
          'repository' => 'Modules\Iblog\Repositories\PostRepository',
          'requestParams' => ['include' => ['files']],
          'translatedAttributes' => 'title, description, slug, summary',
          'mediaZones' => ['mainimage' => 1],
          'module' => 'Blog',
          'module_type' => 'post',
          'extraPrompt' => 'generate individual content items (e.g., blog posts, articles).'
        ],
      ],
      'icommerce' => [
        'category' => [
          'repository' => 'Modules\Icommerce\Repositories\CategoryRepository',
          'requestParams' => ['include' => ['files']],
          'translatedAttributes' => 'title, h1_title, description, slug',
          'mediaZones' => ['mainimage' => 1],
          'module' => 'Ecommerce',
          'module_type' => 'product-category',
          'extraPrompt' => 'generate commercial product groupings (e.g., "Appetizers", "Beverages").',
        ],
        'product' => [
          'repository' => 'Modules\Icommerce\Repositories\ProductRepository',
          'requestParams' => ['include' => ['files']],
          'translatedAttributes' => 'name, description, slug, summary',
          'mediaZones' => ['mainimage' => 1, 'gallery' => 3],
          'module' => 'Ecommerce',
          'module_type' => 'product',
          'extraPrompt' => 'generate specific items or services'
        ],
      ]
    ];
    $this->log = 'Itenant::IAcontent --> ';
  }

  public function handle()
  {
    \Log::info('------------------------------------------------');
    \Log::info($this->log . "START");
    \Log::info('------------------------------------------------');
    foreach ($this->modules as $moduleName) {
      if (isset($this->modulesConfig[$moduleName])) {
        $this->updateModuleData($moduleName, $this->modulesConfig[$moduleName]);
      }
    }
  }

  public function updateModuleData($moduleName, $config)
  {

    $client = new \GuzzleHttp\Client();
    foreach ($config as $entityName => $entityConfig) {
      \Log::info($this->log . "INIT|$moduleName-$entityName...");

      //Request the records to update
      $repository = app($entityConfig['repository']);
      $requestParams = json_decode(json_encode($entityConfig['requestParams'] ?? []));
      $records = $repository->getItemsBy($requestParams);
      if (!$records->count()) continue;

      //instance request Data
      $requestData = array_merge($entityConfig, [
        "category" => $this->organization->category->title ?? '',
        "description" => $this->organization->options->business_description ?? '',
        "quantity" => $records->count(),
        'generate_img' => isset($entityConfig['mediaZones']) ? array_sum($entityConfig['mediaZones']) : 0,
      ]);

      //Request
      $request = $client->request('GET',
        "https://nflow3.imaginacolombia.com/webhook/ia/content",
        ['body' => json_encode($requestData), 'headers' => ['Content-Type' => 'application/json']]
      );
      $requestResponse = json_decode($request->getBody()->getContents());
      $newContent = json_decode(json_encode($requestResponse->data), true);

      $translatableFields = array_map('trim', explode(',', $entityConfig['translatedAttributes']));
      foreach ($records as $index => $record) {
        //get the media data
        $mediaData = !isset($entityConfig['mediaZones']) ? [] :
          $this->saveImagesByRecord($entityConfig['mediaZones'], $newContent[$index]['images']);
        //update the content
        $repository->updateBy($record->id, array_merge([
          'id' => $record->id,
          'es' => $newContent[$index]['es'],
          'en' => $newContent[$index]['en']
        ], $mediaData));
      }
      \Log::info($this->log . "Finish|$moduleName-$entityName...");
    }
  }

  public function saveImagesByRecord($mediaZones, $images)
  {
    if (!count($images)) return [];
    //init values
    $zoneFileIds = [];
    $imageIndex = 0;
    $disk = $images[0]['provider'] ?? 'External';
    $fileService = app("Modules\Media\Services\FileService");

    //insert and save ids by zone
    foreach ($mediaZones as $zoneName => $count) {
      $zoneFileIds[$zoneName] = [];
      for ($i = 0; $i < $count && isset($images[$imageIndex]); $i++, $imageIndex++) {
        $file = $fileService->storeHotLinked($images[$imageIndex]['url'], $disk);
        $zoneFileIds[$zoneName][] = $file->id;
      }
    }

    //organice by groups
    $organizedMedia = ['medias_single' => [], 'medias_multi' => []];
    foreach ($zoneFileIds as $zone => $ids) {
      if (count($ids) === 1) {
        $organizedMedia['medias_single'][$zone] = $ids[0];
      } elseif (count($ids) > 1) {
        $organizedMedia['medias_multi'][$zone] = $ids;
      }
    }

    //Response
    return $organizedMedia;
  }
}
