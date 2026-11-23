<?php

namespace Tests\Entities;

class AuthorWithSmallChunkFetch extends AuthorWithChunkFetch
{
    protected $chunkSize = 1;
}
