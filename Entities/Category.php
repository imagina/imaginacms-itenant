<?php

namespace Modules\Itenant\Entities;

use Astrotomic\Translatable\Translatable;
use Modules\Core\Icrud\Entities\CrudModel;

class Category extends CrudModel
{
  use Translatable;

  protected $table = 'itenant__categories';
  public $transformer = 'Modules\Itenant\Transformers\CategoryTransformer';
  public $repository = 'Modules\Itenant\Repositories\CategoryRepository';
  public $requestValidation = [
      'create' => 'Modules\Itenant\Http\Requests\CreateCategoryRequest',
      'update' => 'Modules\Itenant\Http\Requests\UpdateCategoryRequest',
    ];
  //Instance external/internal events to dispatch with extraData
  public $dispatchesEventsWithBindings = [
    //eg. ['path' => 'path/module/event', 'extraData' => [/*...optional*/]]
    'created' => [],
    'creating' => [],
    'updated' => [],
    'updating' => [],
    'deleting' => [],
    'deleted' => []
  ];
  public $translatedAttributes = [
    'title',
    'description',
    'slug',
    'meta_title',
    'meta_description',
    'meta_keywords',
    'translatable_options'
  ];

  protected $fillable = [
      'parent_id',
      'show_menu',
      'featured',
      'internal',
      'status',
      'sort_order',
      'options',
  ];

  /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
      'options' => 'array',
  ];

  public function organizations()
  {
      return $this->hasMany(Organization::class);
  }

  public function getLftName()
  {
      return 'lft';
  }

  public function getRgtName()
  {
      return 'rgt';
  }

  public function getDepthName()
  {
      return 'depth';
  }

  public function getParentIdName()
  {
      return 'parent_id';
  }

}
