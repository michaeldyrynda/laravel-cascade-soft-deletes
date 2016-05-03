<?php

namespace Iatstuti\Database\Support;

use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

trait CascadeSoftDeletes
{
    /**
     * Boot the trait.
     *
     * Listen for the deleting event of a soft deleting model, and run
     * the delete operation for any configured relationship methods.
     *
     * @throws \RuntimeException
     */
    protected static function bootCascadeSoftDeletes()
    {
        static::deleting(function ($model) {
            if (! $model->implementsSoftDeletes()) {
                throw new LogicException(sprintf(
                    '%s does not implement Illuminate\Database\Eloquent\SoftDeletes',
                    get_called_class()
                ));
            }

            if ($invalidCascadingRelationships = $model->hasInvalidCascadingRelationships()) {
                throw new LogicException(sprintf(
                    '%s [%s] must return an object of type Illuminate\Database\Eloquent\Relations\Relation',
                    str_plural('Relationship', count($invalidCascadingRelationships)),
                    join(', ', $invalidCascadingRelationships)
                ));
            }

            foreach ($model->cascadeDeletes as $relationship) {
                $model->{$relationship}()->delete();
            }
        });
    }


    /**
     * Determine if the current model implements soft deletes.
     *
     * @return bool
     */
    protected function implementsSoftDeletes()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this));
    }


    /**
     * Determine if the current model has any invalid cascading relationships defined.
     *
     * @return array
     */
    protected function hasInvalidCascadingRelationships()
    {
        return collect($this->cascadeDeletes)->filter(function ($relationship) {
            return ! $this->{$relationship}() instanceof Relation;
        })->toArray();
    }
}
