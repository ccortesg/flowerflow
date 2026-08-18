<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DOCUMENTS = [
        'mechanics' => [
            'title' => 'Mecánica y convocatoria',
            'path' => '/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026_v1.1.pdf',
            'sha256' => '11c399ca84735d7dbcb17174e192582c93589afa5100c0250753ca15def4db36',
            'effective_at' => '2026-08-14 00:00:00',
        ],
        'terms' => [
            'title' => 'Términos y condiciones',
            'path' => '/documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026_v1.1.pdf',
            'sha256' => '4e3e6c272f9459b934004168bfccc26d6759a4dbce6c804c03afbb86cda6b144',
            'effective_at' => '2026-08-14 00:00:00',
        ],
        'privacy' => [
            'title' => 'Aviso de privacidad integral',
            'path' => '/documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026_v1.1.pdf',
            'sha256' => '041ae9704f80a0108ee69bb39b8646ee8098134573a730f801e4057642ae2da1',
            'effective_at' => '2026-08-11 00:00:00',
        ],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::DOCUMENTS as $code => $definition) {
                $effectiveAt = CarbonImmutable::parse($definition['effective_at'], 'America/Hermosillo')->utc();
                $existing = DB::table('legal_documents')
                    ->where('code', $code)
                    ->where('version', '1.1')
                    ->first();

                if ($existing) {
                    $matches = $existing->title === $definition['title']
                        && $existing->public_path === $definition['path']
                        && $existing->sha256 === $definition['sha256']
                        && CarbonImmutable::parse($existing->effective_at, 'UTC')->equalTo($effectiveAt)
                        && (bool) $existing->acceptance_required;

                    if (! $matches) {
                        throw new RuntimeException("El documento jurídico {$code} 1.1 existente no coincide con el artefacto inmutable esperado.");
                    }
                } else {
                    DB::table('legal_documents')->insert([
                        'code' => $code,
                        'version' => '1.1',
                        'title' => $definition['title'],
                        'public_path' => $definition['path'],
                        'sha256' => $definition['sha256'],
                        'effective_at' => $effectiveAt,
                        'active' => false,
                        'acceptance_required' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('legal_documents')->where('code', $code)->update([
                    'active' => false,
                    'updated_at' => now(),
                ]);
                DB::table('legal_documents')
                    ->where('code', $code)
                    ->where('version', '1.1')
                    ->update(['active' => true, 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (array_keys(self::DOCUMENTS) as $code) {
                DB::table('legal_documents')
                    ->where('code', $code)
                    ->where('version', '1.1')
                    ->update(['active' => false, 'updated_at' => now()]);
                DB::table('legal_documents')
                    ->where('code', $code)
                    ->where('version', '1.0')
                    ->update(['active' => true, 'updated_at' => now()]);
            }
        });
    }
};
