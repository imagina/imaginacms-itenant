<?php

namespace Modules\Itenant\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IAContent implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, SerializesModels;

  private $modulesConfig;
  private $organization;
  private $modules;

  public function __construct($organization, $params = [])
  {
    $this->organization = $organization;
    $this->modules = $params['modules'] ?? [];
    $this->modulesConfig = [
      'iblog' => [
        'category' => [
          'repository' => 'Modules\Iblog\Repositories\CategoryRepository',
          'translatedAttributes' => 'title,description, slug',
          'generate_img' => true,
          'module' => 'Blog',
          'module_type' => 'post-category',
          'extraPrompt' => 'generate high-level thematic or organizational categories suitable for the module (e.g., "Recipes", "Health Tips", "Traditional Dishes").'
        ],
        'post' => [
          'repository' => 'Modules\Iblog\Repositories\PostRepository',
          'translatedAttributes' => 'title, description, slug, summary',
          'generate_img' => true,
          'module' => 'Blog',
          'module_type' => 'post',
          'extraPrompt' => 'generate individual content items (e.g., blog posts, articles).'
        ],
      ],
      'icommerce' => [
        'category' => [
          'repository' => 'Modules\Icommerce\Repositories\CategoryRepository',
          'translatedAttributes' => 'title, h1_title, description, slug',
          'generate_img' => true,
          'module' => 'Ecommerce',
          'module_type' => 'product-category',
          'extraPrompt' => 'generate commercial product groupings (e.g., "Appetizers", "Beverages").'
        ],
        'post' => [
          'repository' => 'Modules\Icommerce\Repositories\ProductRepository',
          'translatedAttributes' => 'name, description, slug, summary',
          'generate_img' => true,
          'module' => 'Ecommerce',
          'module_type' => 'product',
          'extraPrompt' => 'generate specific items or services'
        ],
      ]
    ];
  }

  public function handle()
  {
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
      $repository = app($entityConfig['repository']);
      $records = $repository->getItemsBy([]);
      if (!$records->count()) continue;

      //instance request Data
      $requestData = array_merge($entityConfig, [
        "category" => $this->organization->category->title ?? '',
        "description" => $this->organization->options->business_description ?? '',
        "quantity" => $records->count()
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
        $repository->updateBy($record->id, [
          'id' => $record->id,
          'es' => $newContent[$index]['es'],
          'en' => $newContent[$index]['en']
        ]);
      }
    }
  }
}
