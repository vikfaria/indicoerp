<?php

namespace App\Http\Controllers;

use App\Models\MediaDirectory;
use App\Models\User;
use App\Services\StorageConfigService;
use App\Services\DynamicStorageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
class MediaController extends Controller
{
    public function page()
    {
        if(Auth::user()->can('manage-media')){
            return Inertia::render('media-library');
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }

    }
    public function index()
    {
        if(Auth::user()->can('manage-media')){

            $user = auth()->user();
            $directoryId = request('directory_id');

            $mediaQuery = Media::query();

            // Filter by directory
            if ($directoryId) {
                $mediaQuery->where('directory_id', $directoryId);
            }
            // When no directory is selected, show all files (don't filter by directory_id)

            // Filter by user permissions
            if ($user->type === 'superadmin') {
                $mediaQuery->where('creator_id', creatorId());
            } elseif ($user->can('manage-any-media')) {
                // Company or user with manage-any-media can see own + team media
                $mediaQuery->where('created_by', creatorId());
            } elseif ($user->can('manage-own-media')) {
                // Company or user with manage-own-media can see own media
                $mediaQuery->where('creator_id', $user->id);
            } else {
                // Default: only own media
                $mediaQuery->whereRaw('1 = 0');
            }

            $media = $mediaQuery->latest()->get()->map(function ($media) {
                try {
                    $url = getImageUrlPrefix().'/'.$media->file_name;
                    return [
                        'id' => $media->id,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'url' => $url,
                        'thumb_url' => $url,
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                        'creator_id' => $media->creator_id,
                        'created_by' => $media->created_by,
                        'created_at' => $media->created_at,
                    ];
                } catch (\Exception $e) {
                    return null;
                }
            })->filter();

            // Get directories based on permissions
            $directoriesQuery = MediaDirectory::whereNull('parent_id');

            if ($user->type === 'superadmin') {
                $directoriesQuery->where('created_by', creatorId());
            } elseif ($user->can('manage-any-media-directories')) {
                // Company or user with manage-any-media-directories can see own + team media
                $directoriesQuery->where('created_by', creatorId());
            } elseif ($user->can('manage-own-media-directories')) {
                // Company or user with manage-own-media-directories can see own media
                $directoriesQuery->where('creator_id', $user->id);
            } else {
                // Default: only own media directories
                $directoriesQuery->whereRaw('1 = 0');
            }

            $directories = $directoriesQuery->get(['id', 'name', 'slug']);

            return response()->json([
                'media' => $media,
                'directories' => $directories
            ]);

        }
        else
        {
            return response()->json(['message' => __('Permission denied')], 403);
        }
    }

    private function getUserFriendlyError(\Exception $e, $fileName): string
    {
        $message = $e->getMessage();
        $extension = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION));

        // Handle media library collection errors
        if (str_contains($message, 'was not accepted into the collection')) {
            if (str_contains($message, 'mime:')) {
                return __("File type not allowed : :extension", ['extension' => $extension]);
            }
            return __("File format not supported : :extension", ['extension' => $extension]);
        }

        // Handle storage errors
        if (str_contains($message, 'storage') || str_contains($message, 'disk')) {
            return __("Storage error : :extension", ['extension' => $extension]);
        }

        // Handle file size errors
        if (str_contains($message, 'size') || str_contains($message, 'large')) {
            return __("File too large : :extension", ['extension' => $extension]);
        }

        // Handle permission errors
        if (str_contains($message, 'permission') || str_contains($message, 'denied')) {
            return __("Permission denied : :extension", ['extension' => $extension]);
        }

        // Generic fallback
        return __("Upload failed : :extension", ['extension' => $extension]);
    }

    public function batchStore(Request $request)
    {
        if(Auth::user()->can('create-media')){
             // Check storage limits
            $storageCheck = $this->checkStorageLimit($request->file('files'));
            if ($storageCheck) {
                return $storageCheck;
            }

            $config = StorageConfigService::getStorageConfig();
            $validationRules = StorageConfigService::getFileValidationRules();

            // Custom validation with user-friendly messages
            $validator = \Validator::make($request->all(), [
                'files' => 'required|array',
                'files.*' => array_merge(['file'], $validationRules),
            ], [
                'files.required' => __('Please select at least one file to upload.'),
                'files.array' => __('Files must be provided as an array.'),
                'files.*.file' => __('Each item must be a valid file.'),
                'files.*.mimes' => __('Only specified file types are allowed: :type',[
                        'type' => isset($config['allowed_file_types']) && $config['allowed_file_types']
                            ? strtoupper(str_replace(',', ', ', $config['allowed_file_types']))
                            : __('Please check storage settings')
                    ])
                    ,
                'files.*.max' => __('File size cannot exceed :max KB.', ['max' => $config['max_file_size_kb']]),
            ]);

            // Additional file validation
            foreach ($request->file('files') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $allowedExtensions = array_map('trim', explode(',', strtolower($config['allowed_file_types'])));

                if (!in_array($extension, $allowedExtensions)) {
                    return response()->json([
                        'message' => __('File type not allowed: :type', ['type' => strtoupper($extension)]),
                        'errors' => [__('Only specified file types are allowed')]
                    ], 422);
                }
            }

            if ($validator->fails()) {
                return response()->json([
                    'message' => __('File validation failed'),
                    'errors' => $validator->errors()->all(),
                    'allowed_types' => $config['allowed_file_types'],
                    'max_size_kb' => $config['max_file_size_kb']
                ], 422);
            }

            $uploadedMedia = [];
            $errors = [];

            foreach ($request->file('files') as $file) {
                try {
                    // Configure dynamic storage before upload
                    DynamicStorageService::configureDynamicDisks();

                    $activeDisk = StorageConfigService::getActiveDisk();

                    // Store file directly to storage
                    $fileName = $file->getClientOriginalName();
                    $hashedName = $file->hashName();
                    $storedPath = $file->storeAs('media', $hashedName, $activeDisk);

                    // Create media record directly
                    $media = new Media();
                    $media->model_type = 'App\Models\User';
                    $media->model_id = auth()->id();
                    $media->collection_name = 'files';
                    $media->name = pathinfo($fileName, PATHINFO_FILENAME);
                    $media->file_name = $hashedName;
                    $media->mime_type = $file->getMimeType();
                    $media->disk = $activeDisk;
                    $media->size = $file->getSize();
                    $media->manipulations = [];
                    $media->custom_properties = [];
                    $media->generated_conversions = [];
                    $media->responsive_images = [];
                    $media->uuid = \Str::uuid();

                    $media->creator_id = auth()->id();
                    $media->created_by = creatorId();
                    if ($request->has('directory_id') && $request->directory_id) {
                        $media->directory_id = $request->directory_id;
                    }
                    $media->save();

                    // Force thumbnail generationAdd commentMore actions
                    try {
                        $media->getUrl('thumb');
                    } catch (\Exception $e) {
                        // Thumbnail generation failed, but continue
                    }

                    $originalUrl = Storage::disk($activeDisk)->url('media/' . $hashedName);
                    $thumbUrl = $originalUrl; // Default to original

                    $uploadedMedia[] = [
                        'id' => $media->id,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'url' => $originalUrl,
                        'thumb_url' => $thumbUrl,
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                        'creator_id' => $media->creator_id,
                        'created_by' => $media->created_by,
                        'created_at' => $media->created_at,
                    ];
                } catch (\Exception $e) {
                    if (isset($storedPath) && Storage::disk($activeDisk)->exists($storedPath)) {
                        Storage::disk($activeDisk)->delete($storedPath);
                    }

                    // Log the actual error for debugging
                    \Log::error('Media upload failed', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $this->getUserFriendlyError($e, $file->getClientOriginalName())
                    ];
                }
            }

            if (count($uploadedMedia) > 0 && empty($errors)) {
                return response()->json([
                    'message' => count($uploadedMedia) . __(' file(s) uploaded successfully'),
                    'data' => $uploadedMedia
                ]);
            } elseif (count($uploadedMedia) > 0 && !empty($errors)) {
                return response()->json([
                    'message' => count($uploadedMedia) . ' uploaded, ' . count($errors) . ' failed',
                    'data' => $uploadedMedia,
                    'errors' => array_column($errors, 'error')
                ]);
            } else {
                return response()->json([
                    'message' => 'Upload failed',
                    'errors' => array_column($errors, 'error')
                ], 422);
            }
        }
        else
        {
            return response()->json(['message' => __('Permission denied')], 403);
        }
    }

    public function download($id)
    {
        if(Auth::user()->can('download-media')){
            $user = auth()->user();
            $query = Media::where('id', $id);

            // Permission-based media access
            if ($user->type === 'superadmin') {
                $query->where('creator_id', $user->id);
            } elseif ($user->can('manage-any-media')) {
                // Company or user with manage-any-media can see own + team media
                $query->where('created_by', creatorId());
            } elseif ($user->can('manage-own-media')) {
                // Company or user with manage-own-media can see own media
                $query->where('creator_id', $user->id);
            } else {
                // Default: only own media directories
                $query->whereRaw('1 = 0');
            }

            $media = $query->firstOrFail();

            try {
                $filePath = $media->getPath();

                if (!file_exists($filePath)) {
                    abort(404, __('File not found'));
                }

                return response()->download($filePath, $media->file_name);
            } catch (\Exception $e) {
                abort(404, __('File storage unavailable'));
            }
        }
        else
        {
            return response()->json(['message' => __('Permission denied')], 403);
        }
    }



    public function destroy($id)
    {
        if(Auth::user()->can('delete-media')){
            $user = auth()->user();
            $query = Media::where('id', $id);

            // Permission-based media access
            if ($user->type === 'superadmin') {
                $query->where('creator_id', $user->id);
            } elseif ($user->can('manage-any-media')) {
                // Company or user with manage-any-media can see own + team media
                $query->where('created_by', creatorId());
            } elseif ($user->can('manage-own-media')) {
                // Company or user with manage-own-media can see own media
                $query->where('creator_id', $user->id);
            } else {
                // Default: only own media
                $query->whereRaw('1 = 0');
            }

            $media = $query->firstOrFail();
            $fileSize = $media->size;

            try {
                // Delete file from storage
                Storage::disk($media->disk)->delete('media/' . $media->file_name);
                $media->delete();
            } catch (\Exception $e) {
                // If storage disk is unavailable, force delete from database
                $media->forceDelete();
            }

            return response()->json(['message' => __('The media has been deleted.')]);
        }
        else
        {
            return response()->json(['message' => __('Permission denied')], 403);
        }
    }

    private function checkStorageLimit($files)
    {
        $user = auth()->user();
        if ($user->type === 'superadmin') return null;

        $creator = ($user->type === 'company') ? $user : User::find($user->created_by);
        if (!$creator) {
            return response()->json([
                'message' => __('Creator not found'),
                'errors' => [__('Please contact administrator')]
            ], 422);
        }

        if ($creator->storage_limit == -1) return null;

        $limit = $creator->storage_limit * 1024; // Convert KB to Bytes
        $uploadSize = collect($files)->sum('size');
        $currentUsage = Media::where('created_by', $creator->id)->sum('size');

        if (($currentUsage + $uploadSize) > $limit) {
            return response()->json([
                'message' => __('Storage limit exceeded'),
                'errors' => [__('Please delete files or upgrade plan')]
            ], 422);
        }

        return null;
    }

    public function getImageUrl(Request $request)
    {
        $path = $request->input('path');

        if (!$path) {
            return response()->json(['url' => '']);
        }

        // Get storage disk configuration
        $disk = config('filesystems.default');

        switch ($disk) {
            case 's3':
            case 'wasabi':
                // For S3/Wasabi, generate temporary URL or return full URL
                $url = Storage::disk($disk)->url($path);
                break;
            case 'public':
            case 'local':
            default:
                // For local storage, prepend app URL
                $url = $path[0] === '/' ? url($path) : $path;
                break;
        }

        return response()->json(['url' => $url]);
    }

    public function createDirectory(Request $request)
    {
        if(Auth::user()->can('create-media-directories')){
            $request->validate([
                'name' => 'required|string|max:255',
            ], [
                'name.required' => __('Directory name is required.'),
                'name.string' => __('Directory name must be a valid string.'),
                'name.max' => __('Directory name must not exceed 255 characters.'),
            ]);

            $slug = \Str::slug($request->name . '-' . time());

            $directory = MediaDirectory::create([
                'name' => $request->name,
                'slug' => $slug,
                'created_by' => creatorId(),
                'creator_id' => auth()->id(),
            ]);

            return response()->json([

                'message' => __('The directory has been created successfully.'),
                'directory' => $directory
            ]);
        }
        else
        {
            return response()->json(['message' => __('Permission denied')], 403);
        }
    }

    public function updateMediaDirectory(Request $request, $mediaId)
    {
        $request->validate([
            'directory_id' => 'nullable|exists:media_directories,id',
        ], [
            'directory_id.exists' => __('Selected directory does not exist.'),
        ]);

        $media = Media::findOrFail($mediaId);
        $media->update(['directory_id' => $request->directory_id]);

        return response()->json([
            'message' => __('The media moved successfully.')
        ]);
    }

    public function updateDirectory(Request $request, $id)
    {
        if(Auth::user()->can('edit-media-directories')){

            $request->validate([
                'name' => 'required|string|max:255',
            ], [
                'name.required' => __('Directory name is required.'),
                'name.string' => __('Directory name must be a valid string.'),
                'name.max' => __('Directory name must not exceed 255 characters.'),
            ]);

            $user = auth()->user();
            $query = MediaDirectory::where('id', $id);

            // Permission-based directory access
            if ($user->type === 'superadmin') {
                $query->where('created_by', $user->id);
            } elseif ($user->can('manage-any-media-directories')) {
                // Company or user with manage-any-media-directories can see own + team media
                $query->where('created_by', creatorId());
            } elseif ($user->can('manage-own-media-directories')) {
                // Company or user with manage-own-media-directories can see own media
                $query->where('creator_id', $user->id);
            } else {
                // Default: only own media directories
                $query->whereRaw('1 = 0');
            }

            $directory = $query->firstOrFail();
            $slug = \Str::slug($request->name . '-' . time());

            $directory->update([
                'name' => $request->name,
                'slug' => $slug,
            ]);

            return response()->json([
                'message' => __('The directory details are updated successfully.'),
                'directory' => $directory
            ]);
        }
        else
        {
            return response()->json(['message' => __('Permission denied')], 403);
        }
    }

    public function destroyDirectory($id)
    {
        if(Auth::user()->can('delete-media-directories')){
            $user = auth()->user();
            $query = MediaDirectory::where('id', $id);

            // Permission-based directory access
            if ($user->type === 'superadmin') {
                $query->where('created_by', $user->id);
            } elseif ($user->can('manage-any-media-directories')) {
                // Company or user with manage-any-media-directories can see own + team media
                $query->where('created_by', creatorId());
            } elseif ($user->can('manage-own-media-directories')) {
                // Company or user with manage-own-media-directories can see own media
                $query->where('creator_id', $user->id);
            } else {
                // Default: only own media directories
                $query->whereRaw('1 = 0');
            }

            $directory = $query->firstOrFail();
            $directory->delete();

            return response()->json([
                'message' => __('The directory has been deleted.')
            ]);
        }
        else
        {
            return response()->json(['message' => __('Permission denied')], 403);
        }
    }
}
