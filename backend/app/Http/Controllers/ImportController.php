<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportResource;
use App\Models\User;
use App\Services\Import\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

class ImportController extends Controller
{
    public function __construct(private readonly ImportService $imports) {}

    public function index(): AnonymousResourceCollection
    {
        return ImportResource::collection($this->imports->latest());
    }

    public function store(StoreImportRequest $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        /** @var User|null $user */
        $user = $request->user();

        $import = $this->imports->start($file, $user);

        return (new ImportResource($import))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function show(string $import): ImportResource
    {
        return new ImportResource(
            $this->imports->find($import) ?? abort(Response::HTTP_NOT_FOUND, 'Импорт не найден.'),
        );
    }
}
