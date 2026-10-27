@extends("System/layouts/main")

@section("content")
    <main class="br-workspace" aria-labelledby="workspace-popular-title">
        <div class="br-workspace__layout">
            <div class="br-workspace__main">
                <section class="br-workspace__section" aria-labelledby="workspace-popular-title">
                    <div class="br-workspace__section-heading">
                        <span class="br-workspace__section-eyebrow">Accesos rápidos</span>
                        <h1 id="workspace-popular-title">¿Qué deseas hacer hoy, {{ $firstName }}?</h1>
                        <p>Encuentra aquí las acciones principales para gestionar tu empresa de forma rápida y sencilla.</p>
                    </div>

                    <div class="br-workspace__links">
                        @forelse($workspace["actions"] as $item)
                            @include("System.general.Essentials.workspace.partials.link", ["item" => $item])
                        @empty
                            <div class="br-workspace__empty">No hay módulos disponibles para tu perfil.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="br-workspace__aside" aria-labelledby="workspace-company-title">
                <section class="br-workspace__panel br-workspace__company">
                    <div class="br-workspace__company-head">
                        <span class="br-workspace__company-logo">
                            <img src="{{ $logomarkUrl }}" alt="Isotipo de {{ $companyName }}" />
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
                            ["label" => "Nombre comercial", "value" => $company->commercial_name, "icon" => "fa-solid fa-building", "full" => true],
                            ["label" => "Dirección", "value" => $company->address, "icon" => "fa-solid fa-location-dot", "full" => true],
                            ["label" => "Correo electrónico", "value" => $company->email, "icon" => "fa-regular fa-envelope", "copyable" => true, "full" => true, "divider" => true],
                            ["label" => "Teléfono", "value" => $company->telephone, "icon" => "fa-solid fa-phone", "tone" => "phone"],
                            ["label" => "WhatsApp", "value" => $companySocials->get("whatsapp")?->link, "icon" => "fa-brands fa-whatsapp", "tone" => "whatsapp"],
                            ["label" => "Facebook", "value" => $companySocials->get("facebook")?->link, "icon" => "fa-brands fa-facebook-f", "tone" => "facebook"],
                            ["label" => "Instagram", "value" => $companySocials->get("instagram")?->link, "icon" => "fa-brands fa-instagram", "tone" => "instagram"],
                        ] as $detail)
                            @include("System.general.Essentials.workspace.partials.company-detail", $detail)
                        @endforeach
                    </dl>
                </section>
            </aside>
        </div>

        <div class="br-workspace__support" role="note" aria-label="Ayuda de Blapos">
            <span class="br-workspace__support-icon" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
            <div class="br-workspace__support-intro">
                <strong>¿Necesitas ayuda?</strong>
                <span>Estamos para ayudarte. Contáctanos.</span>
            </div>
            <a class="br-workspace__support-channel br-workspace__support-channel--email" href="mailto:{{ $supportContact['email'] }}">
                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                <span>{{ $supportContact['email'] }}</span>
            </a>
            <a class="br-workspace__support-channel br-workspace__support-channel--phone" href="tel:{{ $supportContact['phone_href'] }}">
                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                <span>{{ $supportContact['phone'] }}</span>
            </a>
            <a class="br-workspace__support-cta" href="mailto:{{ $supportContact['email'] }}">
                Contáctanos <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </main>
@endsection
