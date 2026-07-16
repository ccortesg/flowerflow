<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\LegalDocument;
use App\Support\MexicoPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $futureActivitiesOptIn = request()->user()->legalAcceptances()
            ->where('purpose', 'future_activities')
            ->latest('id')
            ->value('accepted');

        return view('participant.profile', [
            'profile' => request()->user()->profile,
            'futureActivitiesOptIn' => $futureActivitiesOptIn ?? true,
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousWhatsapp = $user->profile?->whatsapp_opt_in;
        $whatsapp = $request->boolean('whatsapp_opt_in');
        $previousFutureActivities = $user->legalAcceptances()
            ->where('purpose', 'future_activities')
            ->latest('id')
            ->value('accepted');
        $futureActivities = $request->boolean('future_activities_opt_in');
        $user->profile()->updateOrCreate([], [
            ...$request->safe()->only(['first_names', 'last_names', 'birth_date', 'neighborhood']),
            'mobile_e164' => MexicoPhoneNumber::toE164((string) $request->string('mobile_national')),
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
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'context' => ['source' => 'participant_profile', 'previous' => $previousWhatsapp],
            ]);
        }

        if ($previousFutureActivities === null || (bool) $previousFutureActivities !== $futureActivities) {
            $privacyDocument = LegalDocument::query()->where('code', 'privacy')->where('active', true)->first();
            $user->legalAcceptances()->create([
                'legal_document_id' => $privacyDocument?->id,
                'purpose' => 'future_activities',
                'document_version' => $privacyDocument?->version ?? '1.0',
                'accepted' => $futureActivities,
                'accepted_at' => now('UTC'),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'context' => ['source' => 'participant_profile', 'previous' => $previousFutureActivities],
            ]);
        }

        return back()->with('status', 'Tus datos personales y preferencias se actualizaron correctamente.');
    }
}
