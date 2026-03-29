<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\CreateDatabaseRequest;
use App\Http\Requests\System\UpdateDatabaseRequest;
use App\Http\Resources\DatabaseCollection;
use App\Http\Resources\DatabaseResource;
use App\Models\Credential;
use App\Models\Database;
use App\Services\DatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseController extends Controller
{
    public function __construct(
        private DatabaseService $databaseService
    ) {}

    public function index(Request $request): DatabaseCollection|Response
    {
        $this->authorize('viewAny', Database::class);

        $databases = Database::withCount('credentials')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return new DatabaseCollection($databases);
        }

        return Inertia::render('App/Databases/Index', [
            'databases' => (new DatabaseCollection($databases))->toArray($request),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Database::class);

        return Inertia::render('App/Databases/Create');
    }

    public function store(CreateDatabaseRequest $request): DatabaseResource
    {
        $this->authorize('create', Database::class);

        $database = $this->databaseService->create($request->validated());

        if ($request->has('credential_ids')) {
            foreach ($request->validated('credential_ids') as $credentialId) {
                $credential = Credential::find($credentialId);
                if ($credential) {
                    $this->databaseService->attachCredential($database, $credential);
                }
            }
        }

        return new DatabaseResource($database);
    }

    public function show(Request $request, Database $database): DatabaseResource|Response
    {
        $this->authorize('view', $database);

        $database->load(['credentials.users']);

        if ($request->wantsJson()) {
            return new DatabaseResource($database);
        }

        return Inertia::render('App/Databases/Show', [
            'database' => (new DatabaseResource($database))->toArray($request),
        ]);
    }

    public function update(UpdateDatabaseRequest $request, Database $database): DatabaseResource
    {
        $this->authorize('update', $database);

        $database = $this->databaseService->update($database, $request->validated());

        return new DatabaseResource($database);
    }

    public function destroy(Database $database): JsonResponse
    {
        $this->authorize('delete', $database);

        $this->databaseService->delete($database->id);

        return response()->json(null, 204);
    }

    public function attachCredential(Request $request, Database $database): DatabaseResource
    {
        $this->authorize('update', $database);

        $request->validate([
            'credential_id' => ['required', 'string', 'size:27', 'exists:credentials,id'],
        ]);

        $credential = Credential::findOrFail($request->input('credential_id'));
        $this->databaseService->attachCredential($database, $credential);

        return new DatabaseResource($database->fresh());
    }

    public function detachCredential(Database $database, Credential $credential): JsonResponse
    {
        $this->authorize('update', $database);

        $this->databaseService->detachCredential($database, $credential);

        return response()->json(null, 204);
    }
}
