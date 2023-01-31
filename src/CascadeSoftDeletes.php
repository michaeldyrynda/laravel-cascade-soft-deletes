<?php

namespace Dyrynda\Database\Support;

use Illuminate\Database\Eloquent\Relations\Relation;

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
            $model->validateCascadingSoftDelete();

            $model->runCascadingDeletes();
        });

        if (method_exists(self::class, 'restoring')) {
            static::restoring(function ($model) {
                $model->validateCascadingSoftDelete();

                $model->runCascadingRestores();
            });
        }

    }


    /**
     * Validate that the calling model is correctly setup for cascading soft deletes.
     *
     * @throws \Dyrynda\Database\Support\CascadeSoftDeleteException
     */
    protected function validateCascadingSoftDelete()
    {
        if (!$this->implementsSoftDeletes()) {
            throw CascadeSoftDeleteException::softDeleteNotImplemented(get_called_class());
        }

        if ($invalidCascadingRelationships = $this->hasInvalidCascadingRelationships()) {
            throw CascadeSoftDeleteException::invalidRelationships($invalidCascadingRelationships);
        }
    }

    /**
     * Run the cascading restoration for this model.
     *
     * @return void
     */
    protected function runCascadingRestores()
    {
        foreach ($this->getActiveCascadingDeletes(true) as $relationship) {
            $this->cascadeRestores($relationship);
        }
    }

    /**
     * Cascade restore the given relationship.
     *
     * @param  string  $relationship
     * @return void
     */
    protected function cascadeRestores($relationship)
    {
        foreach ($this->{$relationship}()->withTrashed()->get() as $model) {
            isset($model->pivot) ? $model->pivot->restore() : $model->restore();
        }
    }


    /**
     * Run the cascading soft delete for this model.
     *
     * @return void
     */
    protected function runCascadingDeletes()
    {
        foreach ($this->getActiveCascadingDeletes() as $relationship) {
            $this->cascadeSoftDeletes($relationship);
        }
    }


    /**
     * Cascade delete the given relationship on the given mode.
     *
     * @param  string  $relationship
     * @return void
     */
    protected function cascadeSoftDeletes($relationship)
    {
        $delete = $this->forceDeleting ? 'forceDelete' : 'delete';

        foreach ($this->{$relationship}()->get() as $model) {
            isset($model->pivot) ? $model->pivot->{$delete}() : $model->{$delete}();
        }
    }


    /**
     * Determine if the current model implements soft deletes.
     *
     * @return bool
     */
    protected static function implementsSoftDeletes()
    {
        return method_exists(self::class, 'runSoftDelete');
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
            return !method_exists($this, $relationship) || !$this->{$relationship}() instanceof Relation;
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
     * If $isRestore is provided true
     * For the cascading deletes defined on the model, return only those that are trashed.
     * 
     * @return array
     */
    protected function getActiveCascadingDeletes($isRestore = false)
    {
        return array_filter($this->getCascadingDeletes(), function ($relationship) use ($isRestore) {
            return $isRestore ? $this->{$relationship}()->withTrashed()->exists() : $this->{$relationship}()->exists();
        });
    }
}