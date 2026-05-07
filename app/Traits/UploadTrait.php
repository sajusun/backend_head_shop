<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait UploadTrait
{
    /**
     * Upload a file to the specified disk.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string $disk
     * @param string $filename
     * @return string
     */
    public function uploadFile(UploadedFile $file, $folder = null, $disk = 'public', $filename = null)
    {
        $name = !is_null($filename) ? $filename : \Illuminate\Support\Str::random(25);

        $file = $file->storeAs(
            $folder,
            $name . "." . $file->getClientOriginalExtension(),
            $disk
        );

        return $file;
    }

    /**
     * Delete a file from the specified disk.
     *
     * @param string $path
     * @param string $disk
     */
    public function deleteFile($path, $disk = 'public')
    {
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
