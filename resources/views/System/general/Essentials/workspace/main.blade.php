@extends("System/layouts/main")

@section("content")
    <main class="br-workspace" aria-labelledby="workspace-title">
        <header class="br-workspace__hero">
            <h1 id="workspace-title">¡Hola, {{ Str::before(trim($user->name), " ") }}!</h1>
        </header>

        <div class="br-workspace__layout">
            <div class="br-workspace__main">
                <section class="br-workspace__panel" aria-labelledby="workspace-popular-title">
                    <div class="br-workspace__panel-head">
                        <h2 id="workspace-popular-title">Más utilizados</h2>
                    </div>

                    <div class="br-workspace__links">
                        @forelse($workspace["popular"] as $item)
                            @include("System.general.Essentials.workspace.partials.link", ["item" => $item])
                        @empty
                            <div class="br-workspace__empty">Tus accesos más utilizados aparecerán aquí.</div>
                        @endforelse
                    </div>
                </section>

                <section class="br-workspace__panel" aria-labelledby="workspace-suggested-title">
                    <div class="br-workspace__panel-head">
                        <h2 id="workspace-suggested-title">También puedes usar</h2>
                    </div>

                    <div class="br-workspace__links">
                        @forelse($workspace["suggested"] as $item)
                            @include("System.general.Essentials.workspace.partials.link", ["item" => $item])
                        @empty
                            <div class="br-workspace__empty">No hay otros módulos disponibles para tu perfil.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="br-workspace__aside" aria-labelledby="workspace-company-title">
                <section class="br-workspace__panel br-workspace__company">
                    <div class="br-workspace__company-head">
                        <span class="br-workspace__company-logo">
                            <img src="{{ $logoUrl }}" alt="Logo de {{ $companyName }}" />
                        </span>
                        <div>
                            <span class="br-workspace__overline">Tu empresa</span>
                            <h2 id="workspace-company-title">{{ $companyName }}</h2>
                            @if($company->tagline)
                                <p>{{ $company->tagline }}</p>
                            @endif
                        </div>
                    </div>

                    <dl class="br-workspace__company-details">
                        @foreach([
                            ["label" => "Razón social", "value" => $company->legal_name, "full" => true],
                            ["label" => "Tipo de documento", "value" => $company->identityDocumentType?->name],
                            ["label" => "Número de documento", "value" => $company->document_number, "copyable" => true],
                            ["label" => "Correo", "value" => $company->email, "copyable" => true, "full" => true],
                            ["label" => "Dirección", "value" => $company->address, "full" => true],
                            ["label" => "Teléfono", "value" => $company->telephone],
                            ["label" => "WhatsApp", "value" => $companySocials->get("whatsapp")?->link],
                            ["label" => "Facebook", "value" => $companySocials->get("facebook")?->link],
                            ["label" => "Instagram", "value" => $companySocials->get("instagram")?->link],
                        ] as $detail)
                            @include("System.general.Essentials.workspace.partials.company-detail", $detail)
                        @endforeach
                    </dl>
                </section>
            </aside>
        </div>
    </main>
@endsection
