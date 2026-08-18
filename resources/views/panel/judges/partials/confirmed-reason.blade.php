<div class="mb-3">
  <label class="form-label" for="{{ $prefix }}_reason">Razón administrativa</label>
  <textarea class="form-control @error('reason') is-invalid @enderror" id="{{ $prefix }}_reason" name="reason" rows="4" minlength="20" maxlength="1000" required>{{ old('reason') }}</textarea>
  <div class="form-text">Entre 20 y 1,000 caracteres. Quedará en auditoría.</div>
  @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
  <label class="form-label" for="{{ $prefix }}_current_password">Contraseña administrativa actual</label>
  <input class="form-control @error('current_password') is-invalid @enderror" id="{{ $prefix }}_current_password" name="current_password" type="password" autocomplete="current-password" required>
  @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<button class="btn {{ $buttonClass }}" type="submit">{{ $buttonLabel }}</button>
