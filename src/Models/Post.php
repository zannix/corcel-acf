<?php

namespace Tbruckmaier\Corcelacf\Models;

use Corcel\Model\Post as CorcelPost;
use Illuminate\Support\Arr;

class Post extends BaseField
{
    use Traits\SerializedSometimes;

    /**
     * Check if internal value is serialized
     *
     * @return bool
     */
    public function getIsSerializedAttribute() : bool
    {
        $value = $this->data->get($this->localKey);

        try {
            unserialize($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * When only a single post can be selected, we use a relationship to fetch
     * it
     *
     * @return CorcelPost
     */
    public function relationSingle()
    {
        return $this->hasOne(CorcelPost::class, 'ID', 'internal_value');
    }

    /**
     * Get the related post instances (depending on is_serialized)
     *
     * @return mixed
     */
    public function getValueAttribute()
    {
        if ($this->is_serialized) {
            // it would be nice if we could implement this as a hasMany()
            // relation, but laravel does not support whereIn() in relationships
            return $this->getSortedRelation(CorcelPost::class, $this->internal_value);
        }

        return $this->relationSingle;
    }
}
