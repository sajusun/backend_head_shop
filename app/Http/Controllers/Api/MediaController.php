<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Get paginated media items for the Admin Media Library.
     */
    public function index(Request $request)
    {
        $query = Media::latest();

        if ($request->filled('collection')) {
            $query->where('collection_name', $request->collection);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('file_name', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 24);
        $media = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Media retrieved successfully',
            'data' => $media->items(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'total' => $media->total(),
                'per_page' => $media->perPage(),
            ],
        ]);
    }

    /**
     * Upload image(s), auto-convert to WebP, and save in media table.
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,avif,gif|max:10240', // up to 10MB
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,avif,gif|max:10240',
            'folder' => 'nullable|string',
            'collection' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $folder = $request->input('folder', 'products');
        $collection = $request->input('collection', 'default');
        $uploaded = [];

        if ($request->hasFile('image')) {
            $media = $this->mediaService->uploadImage($request->file('image'), $folder, null, $collection);
            $uploaded[] = $media;
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $media = $this->mediaService->uploadImage($file, $folder, null, $collection);
                $uploaded[] = $media;
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($uploaded) . ' image(s) converted to WebP and uploaded successfully',
            'data' => count($uploaded) === 1 ? $uploaded[0] : $uploaded,
        ], 201);
    }

    /**
     * Delete media item.
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        $this->mediaService->deleteMedia($media);

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully',
        ]);
    }
}
