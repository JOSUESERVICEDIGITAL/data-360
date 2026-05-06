<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\BlockIdentityRequest;
use App\Models\Back\BlockedIdentity;
use Illuminate\Http\Request;

class BlockedIdentityController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $blockedIdentities = BlockedIdentity::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('q'), fn ($q) => $q->where('value', 'like', '%' . $request->input('q') . '%'))
            ->latest()
            ->paginate(30);

        return view('back.security.blocked.index', compact('blockedIdentities'));
    }

    public function store(BlockIdentityRequest $request)
    {
        BlockedIdentity::create([
            'user_id' => $request->input('user_id'),
            'type' => $request->input('type'),
            'value' => $request->input('value'),
            'reason' => $request->input('reason'),
            'expires_at' => $request->input('expires_at'),
            'blocked_by' => auth()->id(),
            'is_active' => true,
        ]);

        return back()->with('success', 'Blocage ajouté avec succès.');
    }

    public function toggle(BlockedIdentity $blockedIdentity)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $blockedIdentity->update([
            'is_active' => !$blockedIdentity->is_active,
        ]);

        return back()->with('success', 'Statut du blocage mis à jour.');
    }

    public function destroy(BlockedIdentity $blockedIdentity)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $blockedIdentity->delete();

        return back()->with('success', 'Blocage supprimé.');
    }
}