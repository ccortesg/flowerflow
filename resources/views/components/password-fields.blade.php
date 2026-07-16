@props([
    'passwordLabel' => 'Contraseña',
    'confirmationLabel' => 'Confirmar contraseña',
    'errorBag' => 'default',
])

@php($passwordErrors = $errors->getBag($errorBag))

<div data-password-validation>
    <div class="mb-3">
        <label class="form-label" for="password">{{ $passwordLabel }}</label>
        <div class="input-group">
            <input
                class="form-control @if($passwordErrors->has('password')) is-invalid @endif"
                id="password"
                name="password"
                type="password"
                minlength="8"
                pattern="(?=.*\p{Ll})(?=.*\p{Lu})(?=.*\p{N})(?=.*[\p{Z}\p{S}\p{P}]).{8,}"
                title="Usa al menos 8 caracteres e incluye mayúscula, minúscula, número y símbolo."
                required
                autocomplete="new-password"
                aria-describedby="password-requirements"
                @if($passwordErrors->has('password')) aria-invalid="true" @endif
                data-password-input
            >
            <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-controls="password" aria-label="Mostrar contraseña">Mostrar</button>
        </div>
        @foreach($passwordErrors->get('password') as $message)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @endforeach
    </div>

    <div id="password-requirements" class="ff-password-requirements mb-3" aria-live="polite">
        <p class="mb-2 fw-semibold">Tu contraseña debe incluir:</p>
        <ul class="list-unstyled mb-0">
            <li data-password-rule="length"><span class="ff-password-rule-icon" aria-hidden="true">○</span><span class="visually-hidden" data-password-rule-status>Sin evaluar: </span>Al menos 8 caracteres</li>
            <li data-password-rule="lowercase"><span class="ff-password-rule-icon" aria-hidden="true">○</span><span class="visually-hidden" data-password-rule-status>Sin evaluar: </span>Al menos una letra minúscula</li>
            <li data-password-rule="uppercase"><span class="ff-password-rule-icon" aria-hidden="true">○</span><span class="visually-hidden" data-password-rule-status>Sin evaluar: </span>Al menos una letra mayúscula</li>
            <li data-password-rule="number"><span class="ff-password-rule-icon" aria-hidden="true">○</span><span class="visually-hidden" data-password-rule-status>Sin evaluar: </span>Al menos un número</li>
            <li data-password-rule="symbol"><span class="ff-password-rule-icon" aria-hidden="true">○</span><span class="visually-hidden" data-password-rule-status>Sin evaluar: </span>Al menos un símbolo, por ejemplo !, @ o #</li>
        </ul>
    </div>

    <div class="mb-3">
        <label class="form-label" for="password_confirmation">{{ $confirmationLabel }}</label>
        <div class="input-group">
            <input
                class="form-control"
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                aria-describedby="password-match"
                data-password-confirmation
            >
            <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Mostrar confirmación de contraseña">Mostrar</button>
        </div>
        <p id="password-match" class="ff-password-match mb-0 mt-2" data-password-match aria-live="polite">Confirma exactamente la misma contraseña.</p>
    </div>
</div>
