@extends("System/layouts/main")

@section("content")
    <main class="br-workspace" aria-labelledby="workspace-title">
        <header class="br-workspace__hero">
            <h1 id="workspace-title">{{ $greeting }}, <span class="br-workspace__hero-name">{{ Str::before(trim($user->name), " ") }}</span></h1>
        </header>

        <div class="br-workspace__layout">
            <div class="br-workspace__main">
                <section class="br-workspace__section" aria-labelledby="workspace-popular-title">
                    <h2 id="workspace-popular-title">¿Qué quiero hacer ahora?</h2>

                    <div class="br-workspace__links">
                        @forelse($workspace["actions"] as $item)
                            @include("System.general.Essentials.workspace.partials.link", ["item" => $item])
                        @empty
                            <div class="br-workspace__empty">No hay módulos disponibles para tu perfil.</div>
                        @endforelse
                    </div>
                </section>
                <div class="br-workspace__support" role="note" aria-label="Ayuda de Blapos">
                    @include("shared.support-contact")
                </div>
            </div>

            <aside class="br-workspace__aside" aria-labelledby="workspace-company-title">
                <section class="br-workspace__panel br-workspace__company">
                    <div class="br-workspace__company-head">
                        <span class="br-workspace__company-logo">
                            <img src="{{ $logoUrl }}" alt="Logo de {{ $companyName }}" />
                        </span>
                        <div class="br-workspace__company-identity">
                            <h2 id="workspace-company-title">{{ $company->legal_name ?: $companyName }}</h2>
                            <p class="br-workspace__company-document">
                                <span class="visually-hidden">Tipo de documento:</span>
                                <span class="br-workspace__company-document-type">{{ $company->identityDocumentType?->name ?: "Sin información" }}</span>
                                <span class="visually-hidden">Número de documento:</span>
                                <span class="br-workspace__company-document-number">{{ $company->document_number ?: "Sin información" }}</span>
                                @include("System.components.copy-button", ["value" => $company->document_number, "label" => "Número de documento"])
                            </p>
                            @if($company->tagline)
                                <p class="br-workspace__company-tagline">{{ $company->tagline }}</p>
                            @endif
                        </div>
                    </div>

                    <dl class="br-workspace__company-details">
                        @foreach([
                            ["label" => "Nombre comercial", "value" => $company->commercial_name, "full" => true],
                            ["label" => "Dirección", "value" => $company->address, "full" => true],
                            ["label" => "Correo electrónico", "value" => $company->email, "copyable" => true, "full" => true],
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
