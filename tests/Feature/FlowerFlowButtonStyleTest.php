<?php

namespace Tests\Feature;

use Tests\TestCase;

class FlowerFlowButtonStyleTest extends TestCase
{
    public function test_flower_button_variant_overrides_bootstrap_for_every_interaction_state(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertNotFalse($css);
        $this->assertSame(
            1,
            preg_match('/\.btn\.btn-flower\s*\{(?<declarations>[^}]*)\}/s', $css, $matches),
            'The Flower Flow button selector must remain more specific than Bootstrap .btn.',
        );

        $declarations = $matches['declarations'];

        foreach ([
            '--bs-btn-color: #fff;',
            '--bs-btn-bg: var(--ff-green);',
            '--bs-btn-border-color: var(--ff-green);',
            '--bs-btn-hover-color: #fff;',
            '--bs-btn-hover-bg: #115f47;',
            '--bs-btn-hover-border-color: #115f47;',
            '--bs-btn-focus-shadow-rgb: 22, 124, 91;',
            '--bs-btn-active-color: #fff;',
            '--bs-btn-active-bg: #115f47;',
            '--bs-btn-active-border-color: #115f47;',
            '--bs-btn-disabled-color: #fff;',
            '--bs-btn-disabled-bg: var(--ff-green);',
            '--bs-btn-disabled-border-color: var(--ff-green);',
        ] as $expectedDeclaration) {
            $this->assertStringContainsString($expectedDeclaration, $declarations);
        }
    }
}
