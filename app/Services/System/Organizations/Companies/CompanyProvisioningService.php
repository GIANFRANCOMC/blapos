<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use App\Helpers\System\{Utilities};
use Illuminate\Support\Facades\{DB, Hash, Schema};
use RuntimeException;

final class CompanyProvisioningService {
    private const ROOT_COMPANY_ID = 1;

    public function createOrUpdate(array $attributes): int {

        $payload = [
            "slug" => $attributes["slug"],
            "internal_code" => $attributes["internal_code"] ?? strtoupper($attributes["slug"]),
            "document_number" => $attributes["document_number"] ?? "99999999999",
            "legal_name" => $attributes["legal_name"],
            "commercial_name" => $attributes["commercial_name"],
            "email" => $attributes["email"] ?? null,
            "status" => "active",
            "updated_at" => now(),
        ];

        if(DB::table("companies")->where("id", "!=", self::ROOT_COMPANY_ID)->exists()) {

            throw new RuntimeException("El tenant debe contener exactamente una empresa raíz.");

        }

        DB::table("companies")->updateOrInsert(
            ["id" => self::ROOT_COMPANY_ID],
            $payload
        );

        return self::ROOT_COMPANY_ID;

    }

    public function enable(int $companyId, bool $enableModules = true): void {

        $company = DB::table("companies")->where("id", $companyId)->first();

        if(!$company) {

            throw new RuntimeException("No existe una organización con ID {$companyId}.");

        }

        DB::transaction(function() use ($companyId, $enableModules): void {

            $this->seedIdentityDocumentTypes($companyId);
            $this->seedDocumentTypes($companyId);
            $this->seedCurrencies($companyId);
            $this->ensureCompanyMasterReferences($companyId);
            $this->seedSettings($companyId);
            $this->seedTaxes($companyId);
            $this->seedPaymentMethods($companyId);
            $this->seedSaleDeliveryMethods($companyId);
            $this->seedOperationalDefaults($companyId);
            $this->seedMiscExpenseCategories($companyId);
            $this->seedBusinessProfiles($companyId);

            if($enableModules) {

                $this->ensureAdminRole($companyId);

            }

        });

    }

    public function ensureAdminUser(int $companyId, string $name, string $email, string $password): int {

        $roleId = DB::table("roles")->where("is_full_access", true)->value("id");
        $identityId = DB::table("identity_document_types")->where("code", "dni")->value("id");

        if(!$roleId || !$identityId) {

            throw new RuntimeException("La organización debe aprovisionarse antes de crear el administrador.");

        }

        DB::table("users")->updateOrInsert(
            ["email" => $email],
            [
                "role_id" => $roleId,
                "identity_document_type_id" => $identityId,
                "document_number" => "00000000",
                "name" => $name,
                "password" => Hash::make($password),
                "email_verified_at" => now(),
                "status" => "active",
                "updated_at" => now(),
            ]
        );

        $userId = (int) DB::table("users")->where("email", $email)->value("id");
        $branchId = DB::table("branches")->where("name", "Sede Principal")->value("id");

        if($branchId) {

            DB::table("user_branches")->updateOrInsert(
                ["user_id" => $userId, "branch_id" => $branchId],
                ["status" => "active", "updated_at" => now()]
            );

        }

        return $userId;

    }

    private function seedIdentityDocumentTypes(int $companyId): void {

        $records = [
            ["code" => "doc.trib.no.dom.sin.ruc", "name" => "Doc. trib. no dom. sin RUC", "is_searchable" => false, "min_length" => 15, "max_length" => 15],
            ["code" => "dni", "name" => "DNI", "is_searchable" => true, "min_length" => 8, "max_length" => 8],
            ["code" => "ce", "name" => "CE", "is_searchable" => false, "min_length" => 12, "max_length" => 12],
            ["code" => "ruc", "name" => "RUC", "is_searchable" => true, "min_length" => 11, "max_length" => 11],
            ["code" => "pasaporte", "name" => "Pasaporte", "is_searchable" => false, "min_length" => 8, "max_length" => 8],
        ];

        foreach($records as $record) {

            DB::table("identity_document_types")->updateOrInsert(
                ["code" => $record["code"]],
                $record + ["status" => "active"]
            );

        }

    }

    private function seedDocumentTypes(int $companyId): void {

        foreach([
            ["code" => "BV", "name" => "BOLETA"],
            ["code" => "FA", "name" => "FACTURA"],
        ] as $record) {

            DB::table("document_types")->updateOrInsert(
                ["code" => $record["code"]],
                $record + ["status" => "active"]
            );

        }

    }

    private function seedCurrencies(int $companyId): void {

        DB::table("currencies")->updateOrInsert(
            ["code" => "PEN"],
            [
                "code" => "PEN",
                "sign" => "S/",
                "singular_name" => "SOL",
                "plural_name" => "SOLES",
                "status" => "active",
            ]
        );

    }

    private function ensureCompanyMasterReferences(int $companyId): void {

        $identityDocumentTypeId = DB::table("identity_document_types")
            ->where("code", "ruc")
            ->value("id");

        $currencyId = DB::table("currencies")
            ->where("code", "PEN")
            ->value("id");

        DB::table("companies")
            ->where("id", $companyId)
            ->update([
                "identity_document_type_id" => $identityDocumentTypeId,
                "currency_id" => $currencyId,
            ]);

    }

    private function seedSettings(int $companyId): void {

        $settings = [
            ["group" => "internal_code_prefixes", "key" => "product", "value" => "PRO", "description" => "Prefijo usado para generar códigos internos de productos. Si el valor queda vacío, el código se guarda sin prefijo.", "value_type" => "string"],
            ["group" => "internal_code_prefixes", "key" => "service", "value" => "SER", "description" => "Prefijo usado para generar códigos internos de servicios. Si el valor queda vacío, el código se guarda sin prefijo.", "value_type" => "string"],
            ["group" => "internal_code_prefixes", "key" => "subscription", "value" => "MEM", "description" => "Prefijo usado para generar códigos internos de membresías. Si el valor queda vacío, el código se guarda sin prefijo.", "value_type" => "string"],
            ["group" => "internal_code_prefixes", "key" => "brand", "value" => "MAR", "description" => "Prefijo usado para generar códigos internos de marcas. Si el valor queda vacío, el código se guarda sin prefijo.", "value_type" => "string"],
            ["group" => "internal_code_prefixes", "key" => "category", "value" => "CAT", "description" => "Prefijo usado para generar códigos internos de categorías. Si el valor queda vacío, el código se guarda sin prefijo.", "value_type" => "string"],
            ["group" => "internal_code_prefixes", "key" => "branch", "value" => "SUC", "description" => "Prefijo usado para generar códigos internos de sucursales. Si el valor queda vacío, el código se guarda sin prefijo.", "value_type" => "string"],
            ["group" => "internal_code_prefixes", "key" => "asset", "value" => "ACT", "description" => "Prefijo usado para generar códigos internos de activos. Si el valor queda vacío, el código se guarda sin prefijo.", "value_type" => "string"],
            ["group" => "internal_code_prefixes", "key" => "recipe", "value" => "REC", "description" => "Prefijo sugerido para identificar recetas, platillos y configuraciones operativas de cocina. Si el valor queda vacío, el código se guarda sin prefijo.", "value_type" => "string"],
            ["group" => "inventory", "key" => "allow_negative_stock_on_sale", "value" => "false", "description" => "Define si una venta normal o POS/caja puede dejar productos con stock negativo. Por defecto es false: si la salida supera el stock disponible, la venta se bloquea antes de confirmar.", "value_type" => "boolean"],
            ["group" => "inventory", "key" => "restore_stock_on_sale_cancellation", "value" => "false", "description" => "Define si al anular una venta se devuelven automáticamente los productos al almacén original. Por defecto es false: la devolución física debe registrarse desde Inventario si corresponde.", "value_type" => "boolean"],
            ["group" => "inventory", "key" => "valuation_method", "value" => "weighted_average", "description" => "Método usado para valorizar inventario y kardex. El valor inicial weighted_average calcula costo promedio ponderado sobre entradas y saldos.", "value_type" => "string"],
        ];

        foreach($settings as $setting) {

            DB::table("company_settings")->updateOrInsert(
                ["company_id" => $companyId, "group" => $setting["group"], "key" => $setting["key"]],
                $setting + ["company_id" => $companyId]
            );

        }

        $attendanceSettings = [
            ["group" => "customer_attendance", "key" => "daily_limit_scope", "value" => "branch", "description" => "Define si el limite diario de asistencia de clientes se cuenta por sucursal o por empresa.", "value_type" => "string"],
            ["group" => "customer_attendance", "key" => "biometric_duplicate_tolerance_seconds", "value" => "10", "description" => "Ventana minima entre lecturas biometricas equivalentes del mismo cliente y dispositivo.", "value_type" => "integer"],
            ["group" => "customer_attendance", "key" => "allow_automatic_checkout", "value" => "false", "description" => "Permite que una lectura QR o biometrica finalice automaticamente una asistencia activa de cliente.", "value_type" => "boolean"],
            ["group" => "customer_attendance", "key" => "max_active_hours", "value" => "20", "description" => "Horas maximas que una asistencia de cliente puede permanecer abierta antes de finalizarse tecnicamente para permitir un nuevo ingreso.", "value_type" => "integer"],
            ["group" => "customer_attendance", "key" => "auto_close_stale_enabled", "value" => "true", "description" => "Activa el cierre tecnico de asistencias de clientes que quedaron abiertas sin salida.", "value_type" => "boolean"],
            ["group" => "customer_attendance", "key" => "auto_close_after_time", "value" => "01:00", "description" => "Hora local desde la cual el scheduler puede cerrar asistencias del dia anterior que quedaron abiertas.", "value_type" => "string"],
            ["group" => "customer_attendance", "key" => "auto_close_end_time", "value" => "23:50", "description" => "Hora local usada como salida tecnica cuando una asistencia quedo abierta sin checkout.", "value_type" => "string"],
            ["group" => "customer_attendance", "key" => "retention_months", "value" => "5", "description" => "Cantidad de meses que se conservan asistencias de clientes finalizadas, anuladas, inactivas o ausentes antes de permitir su depuracion.", "value_type" => "integer"],
            ["group" => "subscriptions", "key" => "send_welcome_email_on_sale", "value" => "true", "description" => "Encola un correo de agradecimiento cuando una venta genera una membresia para un cliente.", "value_type" => "boolean"],
            ["group" => "loyalty", "key" => "enabled", "value" => "false", "description" => "Activa el calculo de puntos para clientes en ventas confirmadas. Requiere reglas activas en loyalty_point_rules.", "value_type" => "boolean"],
            ["group" => "loyalty", "key" => "reverse_points_on_sale_cancellation", "value" => "true", "description" => "Revierte puntos ganados cuando se anula la venta que los origino.", "value_type" => "boolean"],
            ["group" => "reports", "key" => "sale_share_ttl_minutes", "value" => "4320", "description" => "Tiempo de vigencia, en minutos, de los enlaces firmados para compartir o imprimir comprobantes de venta fuera de la sesion autenticada.", "value_type" => "integer"],
            ["group" => "inventory", "key" => "stock_alert_email_enabled", "value" => "false", "description" => "Activa el correo al abrir una alerta de stock mínimo.", "value_type" => "boolean"],
            ["group" => "inventory", "key" => "stock_alert_email_to", "value" => null, "description" => "Correo destino de las alertas de stock; usa el correo de la empresa cuando queda vacío.", "value_type" => "string"],
            ["group" => "external_api", "key" => "document_lookup_monthly_warning_threshold", "value" => "80", "description" => "Umbral mensual para advertir el consumo de consultas externas de DNI y RUC.", "value_type" => "integer"],
            ["group" => "numeric_validation", "key" => "decimal_precision", "value" => "3", "description" => "Cantidad de decimales permitidos y usados para redondear montos, cantidades, costos, tributos, pagos e inventario en validaciones y formularios.", "value_type" => "integer"],
            ["group" => "numeric_validation", "key" => "default_min_value", "value" => "0", "description" => "Valor minimo operativo usado por defecto en validaciones numericas cuando el campo no define una regla mas especifica.", "value_type" => "decimal"],
            ["group" => "numeric_validation", "key" => "default_max_value", "value" => "999999999999.999", "description" => "Valor maximo operativo usado por defecto en validaciones numericas de cantidades, precios, totales, pagos, costos y saldos.", "value_type" => "decimal"],
            ["group" => "numeric_validation", "key" => "max_file_size_kb", "value" => "4096", "description" => "Tamanio maximo por defecto, en KB, para archivos validados desde formularios de la empresa.", "value_type" => "integer"],
        ];

        foreach($attendanceSettings as $setting) {

            DB::table("company_settings")->updateOrInsert(
                ["company_id" => $companyId, "group" => $setting["group"], "key" => $setting["key"]],
                $setting + ["company_id" => $companyId]
            );

        }

    }

    private function seedTaxes(int $companyId): void {

        $taxes = [
            ["code" => "SALE-IGV", "name" => "IGV", "description" => "Impuesto General a las Ventas del Perú aplicado a ventas. Si el item incluye IGV, se calcula como tributo contenido; si no lo incluye, se suma al total.", "scope" => "sale", "calculation_type" => "percentage", "rate" => 18, "min_apply_quantity" => null, "max_apply_quantity" => null, "operation_type" => "addition", "is_required" => true, "is_default" => true],
            ["code" => "SALE-ICBP", "name" => "ICBP", "description" => "Impuesto al Consumo de Bolsas Plásticas aplicado a ventas cuando corresponde. Es opcional porque no todas las ventas incluyen bolsa gravada.", "scope" => "sale", "calculation_type" => "fixed", "rate" => 0.5, "min_apply_quantity" => 0, "max_apply_quantity" => null, "operation_type" => "addition", "is_required" => false, "is_default" => false],
            ["code" => "PURCHASE-IGV", "name" => "IGV", "description" => "Impuesto General a las Ventas del Perú aplicado a compras. Se calcula sobre la base de compra registrada.", "scope" => "purchase", "calculation_type" => "percentage", "rate" => 18, "min_apply_quantity" => null, "max_apply_quantity" => null, "operation_type" => "addition", "is_required" => true, "is_default" => true],
            ["code" => "PURCHASE-ICBP", "name" => "ICBP", "description" => "Impuesto al Consumo de Bolsas Plásticas aplicado a compras cuando corresponde. Es opcional porque no todas las compras incluyen bolsa gravada.", "scope" => "purchase", "calculation_type" => "fixed", "rate" => 0.5, "min_apply_quantity" => 0, "max_apply_quantity" => null, "operation_type" => "addition", "is_required" => false, "is_default" => false],
        ];

        foreach($taxes as $tax) {

            DB::table("taxes")->updateOrInsert(
                ["code" => $tax["code"]],
                $tax + ["status" => "active"]
            );

        }

    }

    private function seedPaymentMethods(int $companyId): void {

        $methods = [
            ["code" => "CASH", "category" => "cash", "sunat_code" => "008", "name" => "Efectivo", "description" => "Pago realizado con dinero físico al momento de la operación.", "image_path" => "System/assets/img/payment-methods/cash.svg", "scope" => "both", "requires_reference" => false, "supports_variants" => false, "allows_partial_payment" => true, "is_default" => true],
            ["code" => "BANK_DEPOSIT", "category" => "bank", "sunat_code" => "001", "name" => "Depósito en cuenta", "description" => "Depósito realizado en una cuenta bancaria de la empresa o del proveedor.", "image_path" => "System/assets/img/payment-methods/bank-deposit.svg", "scope" => "both", "requires_reference" => true, "supports_variants" => false, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "BANK_TRANSFER", "category" => "bank", "sunat_code" => "003", "name" => "Transferencia de fondos", "description" => "Transferencia bancaria entre cuentas o entidades financieras.", "image_path" => "System/assets/img/payment-methods/bank-transfer.svg", "scope" => "both", "requires_reference" => true, "supports_variants" => false, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "DEBIT_CARD", "category" => "card", "sunat_code" => "005", "name" => "Tarjeta de débito", "description" => "Pago con tarjeta de débito; puede registrar marca o red como variante.", "image_path" => "System/assets/img/payment-methods/debit-card.svg", "scope" => "sale", "requires_reference" => true, "supports_variants" => true, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "CREDIT_CARD", "category" => "card", "sunat_code" => "006", "name" => "Tarjeta de crédito", "description" => "Pago con tarjeta de crédito; puede registrar marca o red como variante.", "image_path" => "System/assets/img/payment-methods/credit-card.svg", "scope" => "sale", "requires_reference" => true, "supports_variants" => true, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "CHECK", "category" => "bank", "sunat_code" => "007", "name" => "Cheque no negociable", "description" => "Cheque emitido como medio de pago bancarizado.", "image_path" => "System/assets/img/payment-methods/check.svg", "scope" => "both", "requires_reference" => true, "supports_variants" => false, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "DIGITAL_WALLET", "category" => "digital_wallet", "sunat_code" => null, "name" => "Billetera digital", "description" => "Método general para pagos con billeteras digitales como Yape, Plin, Agora PAY, Bim o IzipayYA.", "image_path" => "System/assets/img/payment-methods/digital-wallet.svg", "scope" => "both", "requires_reference" => true, "supports_variants" => true, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "MONEY_ORDER", "category" => "bank", "sunat_code" => "002", "name" => "Giro", "description" => "Giro u orden bancaria reconocida como medio de pago.", "image_path" => "System/assets/img/payment-methods/money-order.svg", "scope" => "both", "requires_reference" => true, "supports_variants" => false, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "PAYMENT_ORDER", "category" => "bank", "sunat_code" => "004", "name" => "Orden de pago", "description" => "Orden emitida mediante el sistema financiero para cancelar una operación.", "image_path" => "System/assets/img/payment-methods/payment-order.svg", "scope" => "both", "requires_reference" => true, "supports_variants" => false, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "REMITTANCE", "category" => "bank", "sunat_code" => null, "name" => "Remesa", "description" => "Remesa canalizada por el sistema financiero.", "image_path" => "System/assets/img/payment-methods/remittance.svg", "scope" => "both", "requires_reference" => true, "supports_variants" => false, "allows_partial_payment" => true, "is_default" => false],
            ["code" => "LETTER_OF_CREDIT", "category" => "bank", "sunat_code" => null, "name" => "Carta de crédito", "description" => "Carta de crédito usada principalmente en compras u operaciones empresariales.", "image_path" => "System/assets/img/payment-methods/letter-of-credit.svg", "scope" => "purchase", "requires_reference" => true, "supports_variants" => false, "allows_partial_payment" => true, "is_default" => false],
        ];

        foreach($methods as $method) {

            DB::table("payment_methods")->updateOrInsert(
                ["code" => $method["code"]],
                $method + ["status" => "active"]
            );

        }

        DB::table("payment_methods")
            ->whereIn("code", ["YAPE", "PLIN"])
            ->delete();

        $this->seedPaymentMethodVariants($companyId);

    }

    private function seedSaleDeliveryMethods(int $companyId): void {

        if(!Schema::hasTable("sale_delivery_methods")) {

            return;

        }

        $methods = [
            ["code" => "local_pickup", "name" => "Recojo en local", "description" => "El cliente recoge lo vendido en un local de la empresa.", "sort_order" => 10, "is_default" => true],
            ["code" => "delivery", "name" => "Delivery", "description" => "La empresa entrega lo vendido en la ubicación indicada por el cliente.", "sort_order" => 20, "is_default" => false],
            ["code" => "shipping", "name" => "Envío", "description" => "Lo vendido se remite mediante transporte propio o un tercero.", "sort_order" => 30, "is_default" => false],
        ];

        foreach($methods as $method) {

            DB::table("sale_delivery_methods")->updateOrInsert(
                ["code" => $method["code"]],
                $method + ["status" => "active"]
            );

        }

    }

    private function seedMiscExpenseCategories(int $companyId): void {

        if(!Schema::hasTable("misc_expense_categories")) {

            return;

        }

        $categories = [
            ["name" => "Mantenimiento", "description" => "Gastos de conservación, ajustes menores y revisiones operativas."],
            ["name" => "Reparación", "description" => "Gastos por arreglo de equipos, mobiliario, infraestructura o herramientas."],
            ["name" => "Servicios básicos", "description" => "Pagos de luz, agua, internet, telefonía u otros servicios de operación."],
            ["name" => "Suministros menores", "description" => "Compras pequeñas que no ingresan como inventario comercial."],
            ["name" => "Otros gastos", "description" => "Gastos operativos que no encajan en una categoría específica."],
        ];

        foreach($categories as $category) {

            DB::table("misc_expense_categories")->updateOrInsert(
                ["name" => $category["name"]],
                $category + ["status" => "active", "updated_at" => now()]
            );

        }

    }

    private function seedBusinessProfiles(int $companyId): void {

        if(!Schema::hasTable("business_industries") || !Schema::hasTable("business_industry_module_sets")) {

            return;

        }

        $profiles = [
            "gym" => [
                "name" => "Gimnasio y membresías",
                "description" => "Base para gimnasios, estudios deportivos y negocios con membresías, asistencias y servicios recurrentes.",
            ],
            "restaurant" => [
                "name" => "Restaurante y comida",
                "description" => "Base para restaurantes, cafeterías y negocios de comida con POS, mesas, recetas y cocina.",
            ],
            "retail" => [
                "name" => "Comercio y retail",
                "description" => "Base para tiendas con productos, inventario, compras, caja y ventas rápidas.",
            ],
        ];

        $subSectionIds = DB::table("sub_sections")
            ->where("status", "active")
            ->pluck("id");

        foreach($profiles as $slug => $profile) {

            DB::table("business_industries")->updateOrInsert(
                ["slug" => $slug],
                $profile + ["status" => "active", "updated_at" => now()]
            );

            $industryId = (int) DB::table("business_industries")
                ->where("slug", $slug)
                ->value("id");

            foreach($subSectionIds as $subSectionId) {

                DB::table("business_industry_module_sets")->updateOrInsert(
                    [
                        "business_industry_id" => $industryId,
                        "sub_section_id" => $subSectionId,
                    ],
                    [
                        "is_enabled_by_default" => true,
                        "reason" => "Módulo disponible para el rubro {$profile["name"]}.",
                        "status" => "active",
                        "updated_at" => now(),
                    ]
                );

            }

        }

        $defaultIndustryId = (int) DB::table("business_industries")
            ->where("slug", "gym")
            ->value("id");

        DB::table("companies")
            ->where("id", $companyId)
            ->whereNull("business_industry_id")
            ->update(["business_industry_id" => $defaultIndustryId]);

    }

    private function seedPaymentMethodVariants(int $companyId): void {

        if(!Schema::hasTable("payment_method_variants")) {

            return;

        }

        $methods = DB::table("payment_methods")
            ->whereIn("code", ["DIGITAL_WALLET", "DEBIT_CARD", "CREDIT_CARD"])
            ->pluck("id", "code");

        $variantsByMethod = [
            "DIGITAL_WALLET" => [
                ["code" => "YAPE", "name" => "Yape", "image_path" => "System/assets/img/payment-methods/yape.svg", "description" => "Billetera digital de uso masivo en Perú."],
                ["code" => "PLIN", "name" => "Plin", "image_path" => "System/assets/img/payment-methods/plin.svg", "description" => "Billetera digital interoperable en Perú."],
                ["code" => "AGORA_PAY", "name" => "Agora PAY", "image_path" => "System/assets/img/payment-methods/agora-pay.svg", "description" => "Billetera digital disponible en Perú."],
                ["code" => "BIM", "name" => "Bim", "image_path" => "System/assets/img/payment-methods/bim.svg", "description" => "Billetera móvil peruana orientada a pagos digitales."],
                ["code" => "IZIPAYYA", "name" => "IzipayYA", "image_path" => "System/assets/img/payment-methods/izipayya.svg", "description" => "Billetera digital antes conocida como Tunki."],
            ],
            "DEBIT_CARD" => [
                ["code" => "VISA_DEBIT", "name" => "Visa débito", "image_path" => "System/assets/img/payment-methods/visa.svg", "description" => "Pago con tarjeta de débito Visa."],
                ["code" => "MASTERCARD_DEBIT", "name" => "Mastercard débito", "image_path" => "System/assets/img/payment-methods/mastercard.svg", "description" => "Pago con tarjeta de débito Mastercard."],
            ],
            "CREDIT_CARD" => [
                ["code" => "VISA_CREDIT", "name" => "Visa crédito", "image_path" => "System/assets/img/payment-methods/visa.svg", "description" => "Pago con tarjeta de crédito Visa."],
                ["code" => "MASTERCARD_CREDIT", "name" => "Mastercard crédito", "image_path" => "System/assets/img/payment-methods/mastercard.svg", "description" => "Pago con tarjeta de crédito Mastercard."],
                ["code" => "AMEX_CREDIT", "name" => "American Express", "image_path" => "System/assets/img/payment-methods/american-express.svg", "description" => "Pago con tarjeta American Express."],
                ["code" => "DINERS_CREDIT", "name" => "Diners Club", "image_path" => "System/assets/img/payment-methods/diners-club.svg", "description" => "Pago con tarjeta Diners Club."],
            ],
        ];

        foreach($variantsByMethod as $methodCode => $variants) {

            $methodId = $methods[$methodCode] ?? null;

            if(!$methodId) {

                continue;

            }

            foreach($variants as $variant) {

                DB::table("payment_method_variants")->updateOrInsert(
                    ["payment_method_id" => $methodId, "code" => $variant["code"]],
                    $variant + [
                        "payment_method_id" => $methodId,
                        "sunat_code" => null,
                        "requires_reference" => true,
                        "is_default" => false,
                        "status" => "active",
                        "updated_at" => now(),
                    ]
                );

            }

        }

    }

    private function seedOperationalDefaults(int $companyId): void {

        DB::table("branches")->updateOrInsert(
            ["company_id" => $companyId, "name" => "Sede Principal"],
            ["company_id" => $companyId, "internal_code" => "SUC-PRINCIPAL", "status" => "active", "updated_at" => now()]
        );

        $branchId = (int) DB::table("branches")->where("name", "Sede Principal")->value("id");

        DB::table("warehouses")->updateOrInsert(
            ["branch_id" => $branchId, "name" => "Almacén 1"],
            ["status" => "active", "updated_at" => now()]
        );
        DB::table("cash_registers")->updateOrInsert(
            ["branch_id" => $branchId, "name" => "Caja principal"],
            ["code" => "CAJ-PRINCIPAL", "is_main" => true, "status" => "active", "updated_at" => now()]
        );

        $genericDocumentId = DB::table("identity_document_types")->where("code", "doc.trib.no.dom.sin.ruc")->value("id");
        DB::table("customers")->updateOrInsert(
            ["document_number" => "999999999"],
            ["identity_document_type_id" => $genericDocumentId, "name" => "Cliente varios", "phone_number" => "", "status" => "active", "updated_at" => now()]
        );

        $documentTypes = DB::table("document_types")->get();

        foreach($documentTypes as $documentType) {

            DB::table("series")->updateOrInsert(
                ["branch_id" => $branchId, "document_type_id" => $documentType->id],
                ["code" => $documentType->code, "number" => 1, "init" => 1, "status" => "active", "updated_at" => now()]
            );

        }

    }

    private function ensureAdminRole(int $companyId): void {

        $existingRoleId = DB::table("roles")
            ->where("is_full_access", true)
            ->value("id");

        if(!$existingRoleId) {

            DB::table("roles")->insert([
                "slug" => Utilities::generateCode(),
                "name" => "Administrador",
                "is_full_access" => true,
                "status" => "active",
            ]);

            $existingRoleId = DB::table("roles")
                ->where("is_full_access", true)
                ->value("id");

        }

        $subSectionIds = DB::table("sub_sections")->pluck("id");

        foreach($subSectionIds as $subSectionId) {

            DB::table("role_sub_sections")->updateOrInsert(
                ["role_id" => $existingRoleId, "sub_section_id" => $subSectionId],
                ["role_id" => $existingRoleId, "sub_section_id" => $subSectionId, "status" => "active"]
            );

        }

    }
}
