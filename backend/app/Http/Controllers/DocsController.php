<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocsController extends Controller
{
    public function ui(): View
    {
        return view('swagger');
    }

    public function spec(): BinaryFileResponse
    {
        return response()->file(resource_path('openapi/openapi.yaml'), [
            'Content-Type' => 'application/yaml',
        ]);
    }
}
