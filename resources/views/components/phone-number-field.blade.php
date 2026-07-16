@props([
    'value' => null,
    'errorBag' => 'default',
])

@php
    $phoneErrors = $errors->getBag($errorBag);
    $nationalValue = old('mobile_national', \App\Support\MexicoPhoneNumber::formatNational($value));
@endphp

<div class="mb-3" data-phone-number>
    <label class="form-label" for="mobile_national">Número de celular</label>
    <div class="input-group">
        <span class="input-group-text ff-phone-prefix" id="mobile-country-code">
            <span aria-hidden="true">🇲🇽</span>
            <span>México (+52)</span>
        </span>
        <input
            class="form-control @if($phoneErrors->has('mobile_national')) is-invalid @endif"
            id="mobile_national"
            name="mobile_national"
            type="tel"
            inputmode="numeric"
            autocomplete="tel-national"
            maxlength="12"
            placeholder="662 123 4567"
            value="{{ $nationalValue }}"
            required
            aria-describedby="mobile-country-code mobile-help mobile-status"
            @if($phoneErrors->has('mobile_national')) aria-invalid="true" @endif
            data-phone-national
        >
    </div>
    <div id="mobile-help" class="form-text">Captura los 10 dígitos. Guardaremos el número en formato internacional con la lada +52.</div>
    <div id="mobile-status" class="ff-phone-status small mt-1" aria-live="polite" data-phone-status>Escribe tu número celular completo.</div>
    @foreach($phoneErrors->get('mobile_national') as $message)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @endforeach
</div>
