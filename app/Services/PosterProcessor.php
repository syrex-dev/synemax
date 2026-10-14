<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class PosterProcessor
{
    public function __construct(private readonly WebpImageProcessor $images) {}

    public function storeAsWebp(UploadedFile $upload): string
    {
        return $this->images->store($upload, 'posters');
    }
}
