<?php

namespace App\Actions\Fortify;

use App\Models\LegalDocument;
use App\Models\User;
use App\Support\MexicoPhoneNumber;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private Request $request) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $validator = Validator::make($input, [
            'first_names' => ['required', 'string', 'max:120'],
            'last_names' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'mobile_national' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (MexicoPhoneNumber::toE164((string) $value) === null) {
                        $fail('Escribe los 10 dígitos de tu celular, por ejemplo 662 123 4567.');
                    }
                },
            ],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'neighborhood' => ['required', 'string', 'max:180'],
            'resident_declaration' => ['accepted'],
            'accept_legal' => ['accepted'],
            'future_activities_opt_in' => ['nullable', 'boolean'],
            'password' => $this->passwordRules(),
        ], [
            'birth_date.before_or_equal' => 'Debes tener 18 años cumplidos para crear una cuenta participante.',
            'resident_declaration.accepted' => 'Debes declarar que resides en Hermosillo, Sonora, y que podrás comprobarlo.',
            'accept_legal.accepted' => 'Debes confirmar que eres mayor de edad y que aceptas los Términos y Condiciones y el Aviso de Privacidad.',
        ]);

        $validated = $validator->validate();
        $documents = LegalDocument::query()
            ->where('active', true)
            ->whereIn('code', ['terms', 'privacy'])
            ->get()
            ->keyBy('code');

        if (! $documents->has('terms') || ! $documents->has('privacy')) {
            throw ValidationException::withMessages([
                'accept_legal' => 'No podemos completar el registro porque los documentos legales no están disponibles. Inténtalo de nuevo más tarde.',
            ]);
        }

        $mobileE164 = MexicoPhoneNumber::toE164($validated['mobile_national']);
        $whatsapp = filter_var($validated['whatsapp_opt_in'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $futureActivities = filter_var($validated['future_activities_opt_in'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $acceptedAt = now('UTC');
        $userAgent = Str::limit((string) $this->request->userAgent(), 1000, '');

        return DB::transaction(function () use ($validated, $documents, $mobileE164, $whatsapp, $futureActivities, $acceptedAt, $userAgent): User {
            $user = User::create([
                'name' => trim($validated['first_names'].' '.$validated['last_names']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create([
                'first_names' => $validated['first_names'],
                'last_names' => $validated['last_names'],
                'mobile_e164' => $mobileE164,
                'whatsapp_opt_in' => $whatsapp,
                'birth_date' => $validated['birth_date'],
                'neighborhood' => $validated['neighborhood'],
                'adult_declared_at' => $acceptedAt,
                'hermosillo_resident_declared_at' => $acceptedAt,
            ]);

            foreach (['terms' => 'registration_terms', 'privacy' => 'registration_privacy'] as $code => $purpose) {
                $document = $documents->get($code);
                $user->legalAcceptances()->create([
                    'legal_document_id' => $document->id,
                    'purpose' => $purpose,
                    'document_version' => $document->version,
                    'accepted' => true,
                    'accepted_at' => $acceptedAt,
                    'ip_address' => $this->request->ip(),
                    'user_agent' => $userAgent,
                    'context' => ['source' => 'registration', 'document_code' => $code],
                ]);
            }

            $privacyDocument = $documents->get('privacy');
            foreach (['whatsapp_contact' => $whatsapp, 'future_activities' => $futureActivities] as $purpose => $accepted) {
                $user->legalAcceptances()->create([
                    'legal_document_id' => $privacyDocument->id,
                    'purpose' => $purpose,
                    'document_version' => $privacyDocument->version,
                    'accepted' => $accepted,
                    'accepted_at' => $acceptedAt,
                    'ip_address' => $this->request->ip(),
                    'user_agent' => $userAgent,
                    'context' => ['source' => 'registration'],
                ]);
            }

            $user->assignRole('participant');

            return $user;
        });
    }
}
