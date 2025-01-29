<?php

namespace Modules\Itenant\Entities;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class OrganizationTranslation extends Model
{
    use Sluggable;

    public $timestamps = false;
    protected $fillable = [
        'title',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'translatable_options',
    ];

    protected $table = 'itenant__organization_translations';

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable() : array
    {
        return [
            'slug' => [
                'source' => 'title',
            ],
        ];
    }

}
