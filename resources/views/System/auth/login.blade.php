<x-system-guest-layout>
    @php
        $ownerApp     = config("app.owner_app");
        $supportContact = app(\App\Services\System\Tenancy\PlatformSupportService::class)->contact();
        $hasCompany   = isset($data->company) && !empty($data->company);
        $seeLoginForm = $hasCompany;
    @endphp

    <div class="fg-auth-login-page container-fluid min-vh-100 d-flex flex-column justify-content-center align-items-center">
        <div class="row w-100 fg-auth-login-card {{ $seeLoginForm ? "overflow-hidden" : "fg-auth-login-card--no-access" }}" style="max-width: 480px;">
            <div class="col-lg-12 px-3 px-md-5 {{ $seeLoginForm ? "pt-4 pt-md-5 pb-3 pb-md-4" : "py-4 py-md-5" }} {{ $seeLoginForm ? "bg-white" : "fg-auth-login-inner--no-access" }}">
                <header class="fg-auth-login-header text-center">
                    @if($seeLoginForm)
                        <p class="fg-auth-login-eyebrow mb-2">Bienvenido</p>
                    @endif
                    <h1 class="fg-auth-login-title fg-login-brand-name">
                        {{ $hasCompany ? $data->company->commercial_name : $ownerApp->commercial_name }}
                    </h1>
                    @if($seeLoginForm)
                        <p class="fg-auth-login-sub mt-2 mb-0">Inicia sesión para continuar</p>
                    @endif
                </header>
                @if($seeLoginForm)
                    <form method="POST" action="{{ route('login') }}" class="mt-3 mt-md-4">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label colon-at-end">Correo electrónico</label>
                            <input type="text" class="form-control" id="email" name="email" placeholder="Ingrese el correo electrónico" :value="old('email')" required autofocus autocomplete="username"/>
                            @if($errors->get("email"))
                                @foreach((array) $errors->get("email") as $message)
                                    <small class="text-danger">{{ $message }}</small>
                                @endforeach
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label colon-at-end">Contraseña</label>
                            <div class="fg-auth-password-field position-relative">
                                <input type="password" class="form-control fg-auth-password-input" id="password" name="password" placeholder="•••••••" required autocomplete="current-password"/>
                                <button type="button" class="fg-auth-password-toggle" id="fg-auth-password-toggle" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            @if($errors->get("password"))
                                @foreach((array) $errors->get("password") as $message)
                                    <small class="text-danger">{{ $message }}</small>
                                @endforeach
                            @endif
                        </div>
                        <div class="mb-1">
                            <div class="cf-turnstile" data-sitekey="{{ config("app.CAPTCHA_KEY_FRONTEND") }}" data-size="flexible"></div>
                            @if($errors->get("captcha"))
                                @foreach((array) $errors->get('captcha') as $message)
                                    <small class="text-danger">{{ $message }}</small>
                                @endforeach
                            @endif
                        </div>
                        <div class="d-flex justify-content-center align-items-center flex-wrap">
                            <button class="fg-button w-100" type="submit">
                                <i class="fa fa-sign-in"></i>
                                <span class="ms-2">Iniciar sesión</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="fg-auth-access-denied mt-3 mt-md-4">
                        <div class="fg-auth-access-denied__notice" role="alert" aria-live="polite">
                            <span class="fg-auth-access-denied__notice-icon" aria-hidden="true"><i class="ti ti-lock"></i></span>
                            <div>
                                <h2 class="fg-auth-access-denied__title">Acceso no disponible por ahora</h2>
                                <p class="fg-auth-access-denied__text">
                                    En este momento <strong class="fg-auth-access-denied__strong-danger">no puedes iniciar sesión</strong> porque la <strong>membresía o el plan</strong> de tu empresa no está activo o ya venció.
                                </p>
                                <p class="fg-auth-access-denied__hint mb-0">
                                    Renueva el plan o pide a quien administra la cuenta que lo actualice. Si <strong class="fg-auth-access-denied__strong-success">ya pagaste</strong>, el cambio puede tardar unos minutos en reflejarse.
                                </p>
                            </div>
                        </div>
                        <aside class="fg-auth-support-bar fg-auth-support-bar--transparent mb-0" aria-labelledby="fg-auth-contactanos-heading-denied">
                            <div class="fg-auth-support-bar__layout">
                                <p id="fg-auth-contactanos-heading-denied" class="fg-auth-support-bar__kicker">Contáctanos</p>
                                <div class="fg-auth-support-bar__inner">
                                    <div class="fg-auth-support-bar__channel">
                                        <span class="fg-auth-support-bar__label">Correo electrónico</span>
                                        <a href="mailto:{{ $supportContact['email'] }}" class="fg-link">{{ $supportContact['email'] }}</a>
                                    </div>
                                    <div class="fg-auth-support-bar__channel fg-auth-support-bar__channel--phone">
                                        <span class="fg-auth-support-bar__label">Teléfono</span>
                                        <a href="tel:{{ $supportContact['phone_href'] }}" class="fg-link">{{ $supportContact['phone'] }}</a>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </div>
                @endif
            </div>
        </div>
        @if($seeLoginForm)
            <div class="text-center mt-3 mt-md-4 px-2">
                <small class="fg-auth-help-footer d-inline-block mb-0">
                    @include("shared.support-contact")
                </small>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const form = document.querySelector("form");

            form?.addEventListener("submit", function(e) {

                const captchaResponse = document.querySelector(`input[name="cf-turnstile-response"]`);

                if(false) {

                    e.preventDefault();

                    Swal.fire({
                        icon              : "info",
                        title             : "Falta un paso de seguridad",
                        allowOutsideClick : false,
                        allowEscapeKey    : false,
                        html              : `
                            <div class="text-start px-1">
                                <p class="mb-3 mb-md-4" style="line-height: 1.55;">
                                    Para proteger tu cuenta, debemos comprobar que <strong>no eres un robot</strong>.
                                </p>
                                <p class="mb-0 fw-semibold" style="line-height: 1.55;">
                                    ¿Qué hacer?
                                </p>
                                <ol class="text-start mt-2 mb-0 ps-3 small" style="line-height: 1.6;">
                                    <li class="mb-2"><strong>Encima</strong> del botón azul «Iniciar sesión» verás la verificación: haz lo que te pida (marcar una casilla, elegir imágenes, etc.).</li>
                                    <li>Cuando se complete correctamente, pulsa <strong>«Iniciar sesión»</strong>.</li>
                                </ol>
                            </div>
                        `,
                        confirmButtonText : "De acuerdo",
                        width             : "32rem",
                        padding           : "1.25rem 1.25rem 1.5rem",
                        customClass: {
                            confirmButton: "btn btn-primary waves-effect",
                            popup        : "text-start",
                        },
                    });

                }

            });

            const passwordInput      = document.getElementById("password");
            const passwordToggle     = document.getElementById("fg-auth-password-toggle");
            const passwordToggleIcon = passwordToggle?.querySelector("i");

            passwordToggle?.addEventListener("click", function() {

                if(!passwordInput || !passwordToggleIcon) return;

                const visible = passwordInput.type === "password";
                passwordInput.type = visible ? "text" : "password";
                passwordToggle.setAttribute("aria-pressed", visible ? "true" : "false");
                passwordToggle.setAttribute("aria-label", visible ? "Ocultar contraseña" : "Mostrar contraseña");
                passwordToggleIcon.className = visible ? "fa-solid fa-eye-slash" : "fa-solid fa-eye";

            });

        });
    </script>

</x-system-guest-layout>
