<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class MediaController extends Controller
{
    public function producto(string $filename)
    {
        $filename = basename($filename);
        $candidates = [
            storage_path('app/public/productos/' . $filename),
            public_path('storage/productos/' . $filename),
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
