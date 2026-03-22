<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * Display a list of active sessions for the current user.
     */
    public function index(Request $request): Response
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $session): array => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $session->last_activity,
                'is_current' => $session->id === $request->session()->getId(),
            ]);

        return Inertia::render('settings/sessions', [
            'sessions' => $sessions,
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    /**
     * Revoke a session by ID. Cannot revoke the current session.
     */
    public function destroy(Request $request, string $sessionId): RedirectResponse
    {
        if ($sessionId === $request->session()->getId()) {
            return redirect()->back()->withErrors(['session' => 'You cannot revoke your current session.']);
        }

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()->back();
    }
}
