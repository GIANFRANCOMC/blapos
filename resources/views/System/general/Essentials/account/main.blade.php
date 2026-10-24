@extends("System/layouts/main")

@section("content")
    <main class="br-account" aria-labelledby="account-title">
        <header class="br-account__header">
            <div>
                <span>Configuración · Ajustes</span>
                <h1 id="account-title">Mi cuenta</h1>
                <p>Actualiza los datos personales asociados a tu acceso.</p>
            </div>
            <span class="br-account__avatar" aria-hidden="true">
                {{ Str::upper(Str::substr($account->name, 0, 1)) }}
            </span>
        </header>

        @if(session("status"))
            <div class="br-account__notice" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <span>{{ session("status") }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route("account.update") }}" class="br-account__form">
            @csrf
            @method("PATCH")

            <section class="br-account__panel">
                <div class="br-account__panel-head">
                    <div>
                        <h2>Datos personales</h2>
                        <p>Esta información identifica tu cuenta dentro de la organización.</p>
                    </div>
                </div>

                <div class="br-account__fields">
                    <div class="br-account__field br-account__field--wide">
                        <label for="account-name">Nombre completo</label>
                        <input id="account-name" name="name" type="text" class="form-control @error("name") is-invalid @enderror" value="{{ old("name", $account->name) }}" maxlength="100" autocomplete="name" required>
                        @error("name")<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="br-account__field br-account__field--wide">
                        <label for="account-email">Correo electrónico</label>
                        <input id="account-email" name="email" type="email" class="form-control @error("email") is-invalid @enderror" value="{{ old("email", $account->email) }}" maxlength="100" autocomplete="email" required>
                        @error("email")<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="br-account__field">
                        <label for="account-phone">Teléfono</label>
                        <input id="account-phone" name="phone_number" type="tel" class="form-control @error("phone_number") is-invalid @enderror" value="{{ old("phone_number", $account->phone_number) }}" maxlength="15" autocomplete="tel">
                        @error("phone_number")<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="br-account__field">
                        <label for="account-gender">Género</label>
                        <select id="account-gender" name="gender" class="form-select @error("gender") is-invalid @enderror">
                            <option value="">Sin especificar</option>
                            <option value="male" @selected(old("gender", $account->gender) === "male")>Masculino</option>
                            <option value="female" @selected(old("gender", $account->gender) === "female")>Femenino</option>
                            <option value="other" @selected(old("gender", $account->gender) === "other")>Otro</option>
                        </select>
                        @error("gender")<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="br-account__field">
                        <label for="account-birthdate">Fecha de nacimiento</label>
                        <input id="account-birthdate" name="birthdate" type="date" class="form-control @error("birthdate") is-invalid @enderror" value="{{ old("birthdate", $account->birthdate) }}" max="{{ now()->toDateString() }}">
                        @error("birthdate")<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>

                <footer class="br-account__actions">
                    <button type="submit" class="br-btn br-btn-primary">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        <span>Guardar cambios</span>
                    </button>
                </footer>
            </section>
        </form>
    </main>
@endsection
