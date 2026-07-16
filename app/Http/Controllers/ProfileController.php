<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('participant.profile', ['profile' => request()->user()->profile]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousWhatsapp = $user->profile?->whatsapp_opt_in;
        $whatsapp = $request->boolean('whatsapp_opt_in');
        $user->profile()->updateOrCreate([], [
            ...$request->safe()->only(['first_names', 'last_names', 'mobile_e164', 'birth_date', 'neighborhood']),
            'whatsapp_opt_in' => $whatsapp,
            'adult_declared_at' => now('UTC'),
            'hermosillo_resident_declared_at' => now('UTC'),
        ]);

        $user->update(['name' => trim($request->string('first_names').' '.$request->string('last_names'))]);

        if ($previousWhatsapp === null || $previousWhatsapp !== $whatsapp) {
            $user->legalAcceptances()->create([
                'legal_document_id' => null,
                'purpose' => 'whatsapp_contact',
                'document_version' => 'draft-1.1',
                'accepted' => $whatsapp,
                'accepted_at' => now('UTC'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'context' => ['source' => 'participant_profile', 'previous' => $previousWhatsapp],
            ]);
        }

        return back()->with('status', 'Perfil actualizado.');
    }
}
