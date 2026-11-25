<x-system-guest-layout>
    @php
        $ownerApp = config('app.owner_app');
        $supportContact = app(\App\Services\System\Tenancy\PlatformSupportService::class)->contact();
        $company = $data->company ?? null;
        $combinationMark = $data->combinationMark;
        $loginImage = $data->loginImage;
        $brandName = $combinationMark['url'] === $combinationMark['fallback']
            ? $ownerApp->commercial_name
            : $company->commercial_name;
    @endphp

    <main class="fg-auth-login-page" aria-labelledby="fg-login-title">
        <div @class([
            'fg-auth-login-visual',
            'fg-auth-login-visual--default' => $loginImage['url'] === $loginImage['fallback'],
        ]) aria-hidden="true">
            <div class="fg-auth-login-art">
                <img id="fg-login-cover" src="{{ $loginImage['url'] }}" data-default-src="{{ $loginImage['fallback'] }}"
                     alt="" loading="lazy" decoding="async" fetchpriority="high">
            </div>
        </div>

        <section class="fg-auth-login-content">
            <div class="fg-auth-login-card">
                <header class="fg-auth-login-header">
                    <img id="fg-login-brand" class="fg-auth-login-brand" src="{{ $combinationMark['url'] }}"
                         alt="{{ $brandName }}" data-default-src="{{ $combinationMark['fallback'] }}"
                         data-default-alt="{{ $ownerApp->commercial_name }}">
                    @if($company)
                        <h1 id="fg-login-title" class="fg-auth-login-title">Inicia sesión</h1>
                    @else
                        <h1 id="fg-login-title" class="fg-auth-login-title">Acceso no disponible</h1>
                    @endif
                </header>

                @if($company)
                    <form class="fg-auth-login-form" id="fg-login-form" method="POST" action="{{ route('login') }}">
                        @csrf
                        <div>
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="{{ old('email') }}" required autocomplete="username" autocapitalize="none" spellcheck="false"
                                   @if($errors->has('email')) aria-invalid="true" aria-describedby="fg-login-email-error" @endif>
                            @if($errors->has('email'))
                                <p class="fg-auth-field-error" id="fg-login-email-error" role="alert">{{ $errors->first('email') }}</p>
                            @endif
                        </div>
                        <div>
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="fg-auth-password-field">
                                <input type="password" class="form-control" id="password" name="password"
                                       required autocomplete="current-password"
                                       @if($errors->has('password')) aria-invalid="true" aria-describedby="fg-login-password-error" @endif>
                                <button type="button" class="fg-auth-password-toggle" id="fg-auth-password-toggle"
                                        aria-controls="password" aria-label="Mostrar contraseña">Mostrar</button>
                            </div>
                            @if($errors->has('password'))
                                <p class="fg-auth-field-error" id="fg-login-password-error" role="alert">{{ $errors->first('password') }}</p>
                            @endif
                        </div>
                        <div class="fg-auth-verification" role="group" aria-label="Verificación de seguridad">
                            <div class="cf-turnstile" data-sitekey="{{ config('app.CAPTCHA_KEY_FRONTEND') }}" data-size="flexible" data-language="es"></div>
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
            </div>

            <footer class="fg-auth-help-footer" aria-label="Ayuda de Blapos">
                <button class="fg-link" type="button" id="fg-login-support-trigger"
                        aria-haspopup="dialog">¿Necesitas ayuda?&ensp;Contáctanos.</button>
            </footer>
        </section>

        <template id="fg-login-support">
            <div>
                <p>Si tienes una sugerencia o algún inconveniente, no dudes en contactarnos.</p>
                <div class="fg-auth-support-links">
                    <a class="fg-auth-support-channel" href="mailto:{{ $supportContact['email'] }}">
                        <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                        <span><small>Correo electrónico</small>{{ $supportContact['email'] }}</span>
                    </a>
                    <a class="fg-auth-support-channel" href="tel:{{ $supportContact['phone_href'] }}">
                        <i class="fa-solid fa-phone" aria-hidden="true"></i>
                        <span><small>Teléfono</small>{{ $supportContact['phone'] }}</span>
                    </a>
                </div>
            </div>
        </template>
    </main>

    <script>
        document.querySelectorAll(".fg-auth-login-page img[data-default-src]").forEach(image => {
            const useDefaultImage = () => {
                const fallback = image.dataset.defaultSrc;
                if (image.getAttribute("src") === fallback) return;
                image.src = fallback;
                image.closest(".fg-auth-login-visual")?.classList.add("fg-auth-login-visual--default");
                if (image.dataset.defaultAlt) image.alt = image.dataset.defaultAlt;
            };
            image.addEventListener("error", useDefaultImage);
            if (image.complete && image.naturalWidth === 0) useDefaultImage();
        });

        const supportTrigger = document.getElementById('fg-login-support-trigger');
        const supportTemplate = document.getElementById('fg-login-support');
        supportTrigger.addEventListener('click', () => {
            supportTrigger.focus({preventScroll: true});
            window.Swal.fire({
                titleText: 'Estamos para ayudarte',
                html: supportTemplate.content.firstElementChild.cloneNode(true),
                confirmButtonText: 'Cerrar',
                buttonsStyling: false,
                focusConfirm: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                returnFocus: true,
                showClass: {popup: 'fg-auth-support-enter'},
                hideClass: {popup: 'fg-auth-support-leave'},
                customClass: {
                    container: 'fg-swal-layer fg-auth-support-layer',
                    popup: 'fg-swal fg-swal--result fg-auth-support-alert',
                    confirmButton: 'fg-button'
                }
            });
        });

        @if($company)
            const loginForm = document.getElementById('fg-login-form');
            const loginSubmit = document.getElementById('fg-login-submit');
            const loginStatus = document.getElementById('fg-login-status');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('fg-auth-password-toggle');
            const verification = loginForm.querySelector('.cf-turnstile');

            if (verification.clientWidth < 300) verification.dataset.size = 'compact';

            passwordToggle.addEventListener('click', () => {
                const visible = passwordInput.type === 'password';
                passwordInput.type = visible ? 'text' : 'password';
                passwordToggle.textContent = visible ? 'Ocultar' : 'Mostrar';
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
        @endif
    </script>
</x-system-guest-layout>
