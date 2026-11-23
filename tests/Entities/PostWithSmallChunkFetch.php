<?php

namespace Tests\Entities;

class PostWithSmallChunkFetch extends PostWithChunkFetch
{
    protected $chunkSize = 2;
}
