<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\LegalDocument;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FlowerFlowSeeder extends Seeder
{
    public function run(): void
    {
        $competition = Competition::query()->updateOrCreate(
            ['slug' => 'hermosillo-florece-2026'],
            [
                'name' => 'Hermosillo Florece 2026',
                'opens_at' => null,
                'closes_at' => CarbonImmutable::createFromFormat(
                    'Y-m-d H:i:s', '2026-08-15 23:59:59', 'America/Hermosillo'
                )->utc(),
                'source_timezone' => 'America/Hermosillo',
                'active' => true,
            ]
        );

        foreach ([
            ['movilidad-con-flow', 'Movilidad con Flow', 'Ideas para mejorar movilidad, vialidad, accesibilidad y seguridad de desplazamientos.'],
            ['hermosillo-florece', 'Hermosillo Florece', 'Ideas para una ciudad más verde y sostenible: arbolado, espacios públicos, agua, sombra y cuidado ambiental.'],
            ['mi-familia-mi-mascota', 'Mi familia, mi mascota', 'Ideas para bienestar animal, tenencia responsable y convivencia de familias con mascotas.'],
        ] as $order => [$slug, $name, $description]) {
            $competition->categories()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'sort_order' => $order + 1,
                'active' => true,
            ]);
        }

        foreach ([
            ['mechanics', 'Mecánica y convocatoria', '/documentos/2026/01_Mecanica_Convocatoria_Hermosillo_Florece_2026.pdf', '42bd5ea13e491dc64a6520f0e26d9663e8e8f973b35a3febf226999118685aa2'],
            ['terms', 'Términos y condiciones', '/documentos/2026/02_Terminos_y_Condiciones_Plataforma_Flower_Flow_2026.pdf', 'ca5fdb36f7a35f8268458144348e66485e8870f55a2bdd9da59137143ef4f28c'],
            ['privacy', 'Aviso de privacidad integral', '/documentos/2026/03_Aviso_de_Privacidad_Plataforma_Flower_Flow_2026.pdf', '056355c0405984a239e97b5074fc6b78eef61570022f8f94c062919620cc6898'],
        ] as [$code, $title, $path, $hash]) {
            LegalDocument::query()->updateOrCreate(['code' => $code, 'version' => '1.0'], [
                'title' => $title,
                'public_path' => $path,
                'sha256' => $hash,
                'effective_at' => CarbonImmutable::parse('2026-07-15 00:00:00', 'America/Hermosillo')->utc(),
                'active' => true,
                'acceptance_required' => true,
            ]);
        }

        foreach ([
            'view panel',
            'view submissions',
            'download private files',
            'view admissibility reviews',
            'review admissibility',
            'request clarification',
            'decide admissibility',
            'view residency documents',
            'download residency documents',
            'manage admissibility reviews',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('participant', 'web');
        Role::findOrCreate('reviewer', 'web')->syncPermissions([
            'view panel',
            'view submissions',
            'download private files',
            'view admissibility reviews',
            'review admissibility',
            'request clarification',
            'decide admissibility',
            'view residency documents',
            'download residency documents',
        ]);
        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all());
    }
}
