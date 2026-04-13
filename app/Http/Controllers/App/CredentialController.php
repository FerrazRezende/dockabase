<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credential\CreateCredentialRequest;
use App\Http\Requests\Credential\UpdateCredentialRequest;
use App\Http\Resources\App\CredentialCollection;
use App\Http\Resources\App\CredentialResource;
use App\Models\Credential;
use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CredentialController extends Controller
{
    public function __construct(
        private CredentialService $credentialService
    ) {}

    public function index(Request $request): CredentialCollection|Response
    {
        $this->authorize('viewAny', Credential::class);

        $credentials = Credential::withCount(['users', 'databases'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return new CredentialCollection($credentials);
        }

        return Inertia::render('App/Credentials/Index', [
            'credentials' => json_decode((new CredentialCollection($credentials))->toJson(), true),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Credential::class);

        return Inertia::render('App/Credentials/Create');
    }

    public function store(CreateCredentialRequest $request): CredentialResource|RedirectResponse
    {
        $this->authorize('create', Credential::class);

        $credential = $this->credentialService->create($request->validated());

        if ($request->has('user_ids')) {
            foreach ($request->validated('user_ids') as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $this->credentialService->attachUser($credential, (string) $user->id);
                }
            }
        }

        if ($request->wantsJson()) {
            return new CredentialResource($credential);
        }

        return to_route('app.credentials.show', $credential);
    }

    public function show(Request $request, Credential $credential): CredentialResource|Response
    {
        $this->authorize('view', $credential);

        $credential->load(['users', 'databases']);

        if ($request->wantsJson()) {
            return new CredentialResource($credential);
        }

        return Inertia::render('App/Credentials/Show', [
            'credential' => (new CredentialResource($credential))->toArray($request),
        ]);
    }

    public function update(UpdateCredentialRequest $request, Credential $credential): CredentialResource
    {
        $this->authorize('update', $credential);

        $credential = $this->credentialService->update($credential, $request->validated());

        return new CredentialResource($credential);
    }

    public function destroy(Request $request, Credential $credential): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $credential);

        $this->credentialService->delete($credential->id);

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return to_route('app.credentials.index');
    }

    public function attachUser(Request $request, Credential $credential): CredentialResource
    {
        $this->authorize('update', $credential);

        $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
        ]);

        $user = User::findOrFail($request->input('user_id'));
        $this->credentialService->attachUser($credential, (string) $user->id);

        return new CredentialResource($credential->fresh()->load(['users']));
    }

    public function detachUser(Credential $credential, User $user): JsonResponse
    {
        $this->authorize('update', $credential);

        $this->credentialService->detachUser($credential, (string) $user->id);

        return response()->json(null, 204);
    }
}
