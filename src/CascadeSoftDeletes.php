<?php

namespace Iatstuti\Database\Support;

use Illuminate\Database\Eloquent\Model;
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
     * @throws \LogicException
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
                    '%s [%s] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation',
                    str_plural('Relationship', count($invalidCascadingRelationships)),
                    join(', ', $invalidCascadingRelationships)
                ));
            }

            $delete = $model->forceDeleting ? 'forceDelete' : 'delete';

            foreach ($model->getActiveCascadingDeletes() as $relationship) {
                if ($model->{$relationship} instanceof Model) {
                    $model->{$relationship}->{$delete}();
                } else {
                    foreach ($model->{$relationship} as $child) {
                        $child->{$delete}();
                    }
                }
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
        return method_exists($this, 'runSoftDelete');
    }


    /**
     * Determine if the current model has any invalid cascading relationships defined.
     *
     * A relationship is considered invalid when the method does not exist, or the relationship
     * method does not return an instance of Illuminate\Database\Eloquent\Relations\Relation.
     *
     * @return array
     */
    protected function hasInvalidCascadingRelationships()
    {
        return array_filter($this->getCascadingDeletes(), function ($relationship) {
            return ! method_exists($this, $relationship) || ! $this->{$relationship}() instanceof Relation;
        });
    }


    /**
     * Fetch the defined cascading soft deletes for this model.
     *
     * @return array
     */
    protected function getCascadingDeletes()
    {
        return isset($this->cascadeDeletes) ? (array) $this->cascadeDeletes : [];
    }


    /**
     * For the cascading deletes defined on the model, return only those that are not null.
     *
     * @return array
     */
    protected function getActiveCascadingDeletes()
    {
        return array_filter($this->getCascadingDeletes(), function ($relationship) {
            return ! is_null($this->{$relationship});
        });
    }
}
