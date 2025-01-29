<?php

namespace Modules\Itenant\Entities;

use Modules\Core\Icrud\Entities\CrudModel;

class Domain extends CrudModel
{
  
  protected $table = 'itenant__domains';
  public $transformer = 'Modules\Itenant\Transformers\DomainTransformer';
  public $repository = 'Modules\Itenant\Repositories\DomainRepository';
  public $requestValidation = [
      'create' => 'Modules\Itenant\Http\Requests\CreateDomainRequest',
      'update' => 'Modules\Itenant\Http\Requests\UpdateDomainRequest',
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
  
  protected $fillable = [
    'domain',
    'organization_id',
  ];


}
