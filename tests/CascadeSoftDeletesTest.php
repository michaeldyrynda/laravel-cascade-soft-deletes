<?php

class CascadeSoftDeletesTest extends PHPUnit_Framework_TestCase
{
    /**
     * Setup the test, getting an object for the CascadeSoftDeletes trait
     *
     * @return void
     */
    public function setUp()
    {
        $this->cascade = $this->getMockForTrait('Iatstuti\Database\Support\CascadeSoftDeletes');
    }
}
