<?php

namespace Dyrynda\Database\Support;

use Exception;
use Illuminate\Support\Str;

class CascadeSoftDeleteException extends Exception
{
    public static function softDeleteNotImplemented($class)
    {
        return new static(sprintf('%s does not implement Illuminate\Database\Eloquent\SoftDeletes', $class));
    }


    public static function invalidRelationships($relationships)
    {
        return new static(sprintf(
            '%s [%s] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation',
            Str::plural('Relationship', count($relationships)),
            join(', ', $relationships)
        ));
    }
}
