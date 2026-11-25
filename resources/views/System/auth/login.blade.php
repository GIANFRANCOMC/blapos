<x-system-guest-layout>
    @php
        $ownerApp = config('app.owner_app');
        $supportContact = app(\App\Services\System\Tenancy\PlatformSupportService::class)->contact();
        $company = $data->company ?? null;
    @endphp

    <main class="fg-auth-login-page" aria-labelledby="fg-login-title">
        <aside class="fg-auth-brand-panel" aria-hidden="true">
            <p class="fg-auth-brand-panel__name">{{ $ownerApp->commercial_name }}</p>
            <div>
                <p class="fg-auth-brand-panel__title">Tu negocio,<br>en un solo lugar.</p>
                <p class="fg-auth-brand-panel__copy">Ventas, inventario y atención a tus clientes.</p>
            </div>
            <ul class="fg-auth-brand-panel__areas">
                <li>Ventas</li>
                <li>Inventario</li>
                <li>Clientes</li>
            </ul>
            @if($company)
                <p class="fg-auth-brand-panel__workspace">Espacio de trabajo · {{ $company->commercial_name }}</p>
            @endif
        </aside>

        <section class="fg-auth-login-content">
            <div class="fg-auth-login-card">
                <header class="fg-auth-login-header">
                    <p class="fg-auth-login-brand">
                        <img src="{{ asset($ownerApp->assets->img->logomark) }}" alt="" width="32" height="32">
                        <span>{{ $ownerApp->commercial_name }}</span>
                    </p>
                    @if($company)
                        <h1 id="fg-login-title" class="fg-auth-login-title">Inicia sesión</h1>
                        <p class="fg-auth-login-sub">Ingresa con tu cuenta de {{ $company->commercial_name }}.</p>
                    @else
                        <h1 id="fg-login-title" class="fg-auth-login-title">Acceso no disponible</h1>
                    @endif
                </header>

                @if($company)
                    <form class="fg-auth-login-form" id="fg-login-form" method="POST" action="{{ route('login') }}">
                        @csrf
                        <div>
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="text" inputmode="email" class="form-control" id="email" name="email"
                                   placeholder="nombre@empresa.com" value="{{ old('email') }}" required autocomplete="username"
                                   @if($errors->has('email')) aria-invalid="true" aria-describedby="fg-login-email-error" @endif>
                            @if($errors->has('email'))
                                <p class="fg-auth-field-error" id="fg-login-email-error" role="alert">{{ $errors->first('email') }}</p>
                            @endif
                        </div>
                        <div>
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="fg-auth-password-field">
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="••••••••" required autocomplete="current-password"
                                       @if($errors->has('password')) aria-invalid="true" aria-describedby="fg-login-password-error" @endif>
                                <button type="button" class="fg-auth-password-toggle" id="fg-auth-password-toggle"
                                        aria-controls="password" aria-label="Mostrar contraseña" aria-pressed="false">Ver</button>
                            </div>
                            @if($errors->has('password'))
                                <p class="fg-auth-field-error" id="fg-login-password-error" role="alert">{{ $errors->first('password') }}</p>
                            @endif
                        </div>
                        <div class="fg-auth-verification" aria-label="Verificación de seguridad">
                            <div class="cf-turnstile" data-sitekey="{{ config('app.CAPTCHA_KEY_FRONTEND') }}" data-size="flexible"></div>
                            @if($errors->has('captcha'))
                                <p class="fg-auth-field-error" role="alert">{{ $errors->first('captcha') }}</p>
                            @endif
                        </div>
                        <button class="fg-button" type="submit" id="fg-login-submit">Iniciar sesión</button>
                    </form>
                    <p class="visually-hidden" id="fg-login-status" role="status"></p>
                @else
                    <div class="fg-auth-access-denied" role="alert">
                        <p>El plan de tu empresa está inactivo o vencido. Pide a quien administra la cuenta que revise la renovación.</p>
                        <p>Si ya se hizo el pago, el acceso puede tardar unos minutos en actualizarse.</p>
                    </div>
                @endif

                <footer class="fg-auth-help-footer">
                    @include('shared.support-contact')
                </footer>
            </div>
        </section>
    </main>

    @if($company)
        <script>
            const loginForm = document.getElementById('fg-login-form');
            const loginSubmit = document.getElementById('fg-login-submit');
            const loginStatus = document.getElementById('fg-login-status');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('fg-auth-password-toggle');

            passwordToggle.addEventListener('click', () => {
                const visible = passwordInput.type === 'password';
                passwordInput.type = visible ? 'text' : 'password';
                passwordToggle.textContent = visible ? 'Ocultar' : 'Ver';
                passwordToggle.setAttribute('aria-pressed', String(visible));
                passwordToggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });

            loginForm.addEventListener('submit', event => {
                if (loginSubmit.disabled) {
                    event.preventDefault();
                    return;
                }

                loginSubmit.disabled = true;
                loginSubmit.textContent = 'Ingresando…';
                loginStatus.textContent = 'Verificando tus datos de acceso.';
                loginForm.setAttribute('aria-busy', 'true');
            });

            window.addEventListener('pageshow', () => {
                loginSubmit.disabled = false;
                loginSubmit.textContent = 'Iniciar sesión';
                loginStatus.textContent = '';
                loginForm.removeAttribute('aria-busy');
            });
        </script>
    @endif
</x-system-guest-layout>
