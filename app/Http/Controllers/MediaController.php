<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class MediaController extends Controller
{
    public function producto(string $filename)
    {
        $filename = urldecode($filename);
        $filename = str_replace('\\', '/', $filename);
        $filename = ltrim($filename, '/');
        $filename = preg_replace('#/+#', '/', $filename);

        if (!$filename || str_contains($filename, '..') || str_starts_with($filename, '.')) {
            abort(404);
        }

        $candidates = [
            storage_path('app/public/' . $filename),
            public_path($filename),
            public_path('storage/' . $filename),
            public_path('imagenes/' . $filename),
            public_path('imagenes/Landingpage/' . $filename),
        ];

        foreach ($candidates as $path) {
            if (File::exists($path)) {
                return response()->file($path);
            }
        }

        abort(404);
    }
}
