<?php

namespace Modules\Itenant\Entities;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class CategoryTranslation extends Model
{   
    use Sluggable;

    public $timestamps = false;
    protected $table = 'itenant__category_translations';

    protected $fillable = [
        'title',
        'description',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

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
