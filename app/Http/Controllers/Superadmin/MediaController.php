<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Image upload for the page/post editors.
     *
     * The stored extension now comes from the detected content type, not the client-supplied
     * filename (a polyglot "image" named x.php was previously saved as uuid.php in public
     * storage — executable by Apache). SVG is no longer accepted (script-capable, same-origin).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $file = $request->file('image');
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => null,
        };

        if (! $extension) {
            return response()->json(['success' => false, 'message' => 'Unsupported image type.'], 422);
        }

        $path = $file->storeAs('uploads/pages', Str::uuid() . '.' . $extension, 'public');

        return response()->json([
            'success' => true,
            'url' => Storage::url($path),
            'path' => $path,
        ]);
    }
}
