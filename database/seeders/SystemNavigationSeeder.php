<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Support\System\NavigationOrder;
use Illuminate\Database\{Seeder};
use Illuminate\Support\Facades\{DB, Schema};

final class SystemNavigationSeeder extends Seeder {

    private const DISABLED_BY_DEFAULT_ROUTES = [
        "dashboard.index",
        "reports.index",
        "restaurant_pos.index",
        "tracking_customers.index",
        "tracking_subscriptions.index",
        "tracking_attendances.index",
        "tracking_notifications.index",
        "book_complaints.index",
        "recipes.index",
        "assets.index",
        "assets_management.index",
        "biometric_devices.index",
        "user_attendances.index",
        "business_profile.index",
    ];

    public function run(): void {

        if(DB::table("sections")->exists() || DB::table("sub_sections")->exists()) {

            $this->consolidateCatalogMasters();
            $this->consolidateConfigurationAccess();
            $this->disableBusinessProfile();

            return;

        }

        $catalog = [
            "categories" => [
                ["id" => 1, "code" => "principal", "label" => "Principal", "order" => 1],
                ["id" => 2, "code" => "operations", "label" => "Operaciones", "order" => 2],
                ["id" => 3, "code" => "management", "label" => "Gestión", "order" => 3],
                ["id" => 4, "code" => "administration", "label" => "Administración", "order" => 4],
            ],
            "sections" => [
                ["id" => 1, "menu_category_id" => 1, "slug" => "sc_workspace", "name" => "workspace", "order" => 1, "dom_id" => "menu-parent-workspace", "dom_label" => "Mi espacio de trabajo", "dom_icon" => "fa-solid fa-compass", "has_sub_menu" => true],
                ["id" => 3, "menu_category_id" => 2, "slug" => "sc_sales", "name" => "sales", "order" => 1, "dom_id" => "menu-parent-sales", "dom_label" => "Ventas", "dom_icon" => "fa-solid fa-cash-register", "has_sub_menu" => true],
                ["id" => 9, "menu_category_id" => 2, "slug" => "sc_purchases", "name" => "purchases", "order" => 2, "dom_id" => "menu-parent-purchases", "dom_label" => "Compras", "dom_icon" => "fa-solid fa-cart-flatbed", "has_sub_menu" => true],
                ["id" => 10, "menu_category_id" => 2, "slug" => "sc_cash", "name" => "cash", "order" => 3, "dom_id" => "menu-parent-cash", "dom_label" => "Caja y finanzas", "dom_icon" => "fa-solid fa-vault", "has_sub_menu" => true],
                ["id" => 4, "menu_category_id" => 3, "slug" => "sc_customers", "name" => "customers", "order" => 1, "dom_id" => "menu-parent-customers", "dom_label" => "Clientes", "dom_icon" => "fa fa-user", "has_sub_menu" => true],
                ["id" => 5, "menu_category_id" => 3, "slug" => "sc_items", "name" => "items", "order" => 2, "dom_id" => "menu-parent-items", "dom_label" => "Catálogo comercial", "dom_icon" => "fa fa-book", "has_sub_menu" => true],
                ["id" => 7, "menu_category_id" => 4, "slug" => "sc_configuration", "name" => "configuration", "order" => 1, "dom_id" => "menu-parent-configuration", "dom_label" => "Configuración", "dom_icon" => "fa fa-gear", "has_sub_menu" => true],
            ],
            "groups" => [
                ["id" => 1, "section_id" => 3, "code" => "sales", "label" => "Ventas", "order" => 1],
                ["id" => 2, "section_id" => 3, "code" => "point_of_sale", "label" => "Punto de venta", "order" => 2],
                ["id" => 3, "section_id" => 3, "code" => "quotations", "label" => "Cotizaciones", "order" => 3],
                ["id" => 4, "section_id" => 3, "code" => "dispatch_collection", "label" => "Despacho y cobranza", "order" => 4],
                ["id" => 5, "section_id" => 9, "code" => "purchases", "label" => "Compras", "order" => 1],
                ["id" => 25, "section_id" => 9, "code" => "purchase_control", "label" => "Recepción y pagos", "order" => 2],
                ["id" => 6, "section_id" => 9, "code" => "suppliers", "label" => "Proveedores", "order" => 3],
                ["id" => 7, "section_id" => 10, "code" => "cash_operation", "label" => "Operación de caja", "order" => 1],
                ["id" => 8, "section_id" => 10, "code" => "financial_control", "label" => "Control", "order" => 2],
                ["id" => 9, "section_id" => 4, "code" => "services", "label" => "Servicios", "order" => 3],
                ["id" => 10, "section_id" => 4, "code" => "communications", "label" => "Atención al cliente", "order" => 4],
                ["id" => 11, "section_id" => 4, "code" => "customer_management", "label" => "Gestión", "order" => 1],
                ["id" => 12, "section_id" => 4, "code" => "memberships", "label" => "Membresías", "order" => 2],
                ["id" => 15, "section_id" => 5, "code" => "inventory", "label" => "Inventario", "order" => 1],
                ["id" => 13, "section_id" => 5, "code" => "commercial_offer", "label" => "Maestras", "order" => 2],
                ["id" => 20, "section_id" => 7, "code" => "organization", "label" => "Organización", "order" => 1],
                ["id" => 19, "section_id" => 7, "code" => "assets", "label" => "Activos", "order" => 2],
                ["id" => 21, "section_id" => 7, "code" => "access", "label" => "Accesos y ajustes", "order" => 3],
                ["id" => 23, "section_id" => 1, "code" => "personal_workspace", "label" => "Mi espacio", "order" => 1],
                ["id" => 24, "section_id" => 1, "code" => "workspace_analysis", "label" => "Análisis", "order" => 2],
            ],
            "items" => [
                ["id" => 11, "section_id" => 1, "menu_group_id" => 23, "order" => 1, "slug" => "sc_workspace", "name" => "workspace", "dom_id" => "menu-workspace", "dom_label" => "Mi espacio", "dom_route" => "workspace.index", "description" => "Retoma tus rutas recientes y accede a las funciones que más utilizas."],
                ["id" => 10, "section_id" => 1, "menu_group_id" => 23, "order" => 2, "slug" => "sc_home", "name" => "home", "dom_id" => "menu-home", "dom_label" => "Menú y favoritos", "dom_route" => "home.index", "description" => "Organiza y abre tus accesos favoritos del sistema."],
                ["id" => 12, "section_id" => 1, "menu_group_id" => 23, "order" => 3, "slug" => "sc_account", "name" => "account", "dom_id" => "menu-account", "dom_label" => "Mi cuenta", "dom_route" => "account.index", "description" => "Actualiza tus datos personales y la información de tu cuenta."],
                ["id" => 20, "section_id" => 1, "menu_group_id" => 24, "order" => 1, "slug" => "sc_dashboard", "name" => "dashboard", "dom_id" => "menu-dashboard", "dom_label" => "Dashboard", "dom_route" => "dashboard.index", "description" => "Consulta indicadores y el estado general de la operación."],
                ["id" => 31, "section_id" => 3, "menu_group_id" => 1, "order" => 1, "slug" => "sc_sales-create", "name" => "sales-create", "dom_id" => "menu-sales-create", "dom_label" => "Nueva venta", "dom_route" => "sales.create", "description" => "Registra una venta de productos, servicios o membresías."],
                ["id" => 30, "section_id" => 3, "menu_group_id" => 1, "order" => 2, "slug" => "sc_sales-list", "name" => "sales-list", "dom_id" => "menu-sales-list", "dom_label" => "Listado de ventas", "dom_route" => "sales.index", "description" => "Consulta las ventas registradas y sus detalles."],
                ["id" => 32, "section_id" => 3, "menu_group_id" => 2, "order" => 1, "slug" => "sc_sales-pos", "name" => "sales-pos", "dom_id" => "menu-sales-pos", "dom_label" => "Venta POS", "dom_route" => "sales.pos", "description" => "Venta rápida para mostrador vinculada a caja y almacén."],
                ["id" => 105, "section_id" => 3, "menu_group_id" => 2, "order" => 2, "slug" => "sc_restaurant-pos", "name" => "restaurant-pos", "dom_id" => "menu-restaurant-pos", "dom_label" => "POS restaurante", "dom_route" => "restaurant_pos.index", "description" => "Gestiona mesas, pedidos y cobros del restaurante."],
                ["id" => 34, "section_id" => 3, "menu_group_id" => 3, "order" => 1, "slug" => "sc_quotations-create", "name" => "quotations-create", "dom_id" => "menu-quotations-create", "dom_label" => "Nueva cotización", "dom_route" => "quotations.create", "description" => "Registra una nueva propuesta comercial."],
                ["id" => 33, "section_id" => 3, "menu_group_id" => 3, "order" => 2, "slug" => "sc_quotations-list", "name" => "quotations-list", "dom_id" => "menu-quotations-list", "dom_label" => "Listado de cotizaciones", "dom_route" => "quotations.index", "description" => "Consulta y convierte cotizaciones en ventas."],
                ["id" => 35, "section_id" => 3, "menu_group_id" => 4, "order" => 1, "slug" => "sc_sales-deliveries", "name" => "sales-deliveries", "dom_id" => "menu-sales-deliveries", "dom_label" => "Entregas pendientes", "dom_route" => "sales.deliveries.index", "description" => "Controla despachos parciales y totales de ventas pendientes."],
                ["id" => 36, "section_id" => 3, "menu_group_id" => 4, "order" => 2, "slug" => "sc_sales-accounts-receivable", "name" => "sales-accounts-receivable", "dom_id" => "menu-sales-accounts-receivable", "dom_label" => "Cuentas por cobrar", "dom_route" => "accounts_receivable.index", "description" => "Consulta saldos, vencimientos y cronogramas de ventas a crédito."],
                ["id" => 92, "section_id" => 9, "menu_group_id" => 5, "order" => 1, "slug" => "sc_purchases-new", "name" => "purchases-new", "dom_id" => "menu-purchases-new", "dom_label" => "Nueva compra", "dom_route" => "purchases.create", "description" => "Registra una compra, sus tributos, pagos y recepción."],
                ["id" => 90, "section_id" => 9, "menu_group_id" => 5, "order" => 2, "slug" => "sc_purchases-list", "name" => "purchases-list", "dom_id" => "menu-purchases-list", "dom_label" => "Compras registradas", "dom_route" => "purchases.index", "description" => "Consulta compras, recepción y estado de pago."],
                ["id" => 93, "section_id" => 9, "menu_group_id" => 25, "order" => 1, "slug" => "sc_purchases-receipts", "name" => "purchases-receipts", "dom_id" => "menu-purchases-receipts", "dom_label" => "Ingresos pendientes", "dom_route" => "purchases.receipts.index", "description" => "Registra ingresos parciales o totales de compras pendientes y actualiza inventario."],
                ["id" => 94, "section_id" => 9, "menu_group_id" => 25, "order" => 2, "slug" => "sc_purchases-accounts-payable", "name" => "purchases-accounts-payable", "dom_id" => "menu-purchases-accounts-payable", "dom_label" => "Cuentas por pagar", "dom_route" => "accounts_payable.index", "description" => "Consulta saldos, vencimientos y cronogramas de compras a crédito."],
                ["id" => 91, "section_id" => 9, "menu_group_id" => 6, "order" => 1, "slug" => "sc_purchases-suppliers", "name" => "purchases-suppliers", "dom_id" => "menu-purchases-suppliers", "dom_label" => "Proveedores", "dom_route" => "suppliers.index", "description" => "Administra proveedores y sus datos comerciales."],
                ["id" => 100, "section_id" => 10, "menu_group_id" => 7, "order" => 1, "slug" => "sc_cash-registers", "name" => "cash-registers", "dom_id" => "menu-cash-registers", "dom_label" => "Cajas", "dom_route" => "cash_registers.index", "description" => "Gestiona las cajas configuradas por sucursal."],
                ["id" => 101, "section_id" => 10, "menu_group_id" => 7, "order" => 2, "slug" => "sc_cash-sessions", "name" => "cash-sessions", "dom_id" => "menu-cash-sessions", "dom_label" => "Aperturas y cierres", "dom_route" => "cash_sessions.index", "description" => "Consulta aperturas, cierres, arqueos y diferencias."],
                ["id" => 102, "section_id" => 10, "menu_group_id" => 7, "order" => 3, "slug" => "sc_cash-movements", "name" => "cash-movements", "dom_id" => "menu-cash-movements", "dom_label" => "Movimientos", "dom_route" => "cash_movements.index", "description" => "Registra entradas, salidas y ajustes de dinero."],
                ["id" => 103, "section_id" => 10, "menu_group_id" => 8, "order" => 1, "slug" => "sc_cash-summary", "name" => "cash-summary", "dom_id" => "menu-cash-summary", "dom_label" => "Resumen de caja", "dom_route" => "cash_summary.index", "description" => "Revisa importes esperados, contados y diferencias."],
                ["id" => 109, "section_id" => 10, "menu_group_id" => 8, "order" => 2, "slug" => "sc_misc-expenses", "name" => "misc-expenses", "dom_id" => "menu-misc-expenses", "dom_label" => "Gastos varios", "dom_route" => "misc_expenses.index", "description" => "Registra gastos no ligados a compras de inventario."],
                ["id" => 106, "section_id" => 4, "menu_group_id" => 9, "order" => 1, "slug" => "sc_service-sessions", "name" => "service-sessions", "dom_id" => "menu-service-sessions", "dom_label" => "Servicios en curso", "dom_route" => "service_sessions.index", "description" => "Inicia, asigna y finaliza servicios."],
                ["id" => 44, "section_id" => 4, "menu_group_id" => 10, "order" => 1, "slug" => "sc_customers-notifications", "name" => "customers-notifications", "dom_id" => "menu-customers-notifications", "dom_label" => "Notificaciones", "dom_route" => "tracking_notifications.index", "description" => "Gestiona comunicaciones y avisos para clientes."],
                ["id" => 45, "section_id" => 4, "menu_group_id" => 10, "order" => 2, "slug" => "sc_customers-book_complaints", "name" => "customers-book-complaints", "dom_id" => "menu-customers-book-complaints", "dom_label" => "Libro de reclamaciones", "dom_route" => "book_complaints.index", "description" => "Atiende reclamos, quejas y sugerencias."],
                ["id" => 40, "section_id" => 4, "menu_group_id" => 11, "order" => 1, "slug" => "sc_customers", "name" => "customers", "dom_id" => "menu-customers", "dom_label" => "Clientes", "dom_route" => "customers.index", "description" => "Administra los datos y estado de los clientes."],
                ["id" => 41, "section_id" => 4, "menu_group_id" => 11, "order" => 2, "slug" => "sc_customers-history", "name" => "customers-history", "dom_id" => "menu-customers-history", "dom_label" => "Historial", "dom_route" => "tracking_customers.index", "description" => "Consulta la actividad y movimientos del cliente."],
                ["id" => 42, "section_id" => 4, "menu_group_id" => 12, "order" => 1, "slug" => "sc_customers-subscriptions", "name" => "customers-subscriptions", "dom_id" => "menu-customers-subscriptions", "dom_label" => "Membresías", "dom_route" => "tracking_subscriptions.index", "description" => "Controla vigencias, renovaciones y estados."],
                ["id" => 43, "section_id" => 4, "menu_group_id" => 12, "order" => 2, "slug" => "sc_customers-attendances", "name" => "customers-attendances", "dom_id" => "menu-customers-attendances", "dom_label" => "Asistencias de clientes", "dom_route" => "tracking_attendances.index", "description" => "Registra ingresos y salidas validados por la membresía vigente del cliente."],
                ["id" => 54, "section_id" => 5, "menu_group_id" => 15, "order" => 1, "slug" => "sc_inventory-stock", "name" => "inventory-stock", "dom_id" => "menu-inventory-stock", "dom_label" => "Control de stock", "dom_route" => "stocks_management.stock.index", "description" => "Consulta existencias, mínimos y alertas."],
                ["id" => 57, "section_id" => 5, "menu_group_id" => 15, "order" => 2, "slug" => "sc_inventory-transfers", "name" => "inventory-transfers", "dom_id" => "menu-inventory-transfers", "dom_label" => "Traslados", "dom_route" => "stocks_management.transfers.index", "description" => "Registra traslados entre almacenes."],
                ["id" => 107, "section_id" => 5, "menu_group_id" => 15, "order" => 3, "slug" => "sc_inventory-guides", "name" => "inventory-guides", "dom_id" => "menu-inventory-guides", "dom_label" => "Guías", "dom_route" => "stocks_management.guides.index", "description" => "Consulta guías numeradas de entrada y salida."],
                ["id" => 56, "section_id" => 5, "menu_group_id" => 15, "order" => 4, "slug" => "sc_inventory-kardex", "name" => "inventory-kardex", "dom_id" => "menu-inventory-kardex", "dom_label" => "Kardex", "dom_route" => "stocks_management.kardex.index", "description" => "Consulta la trazabilidad de movimientos y saldos."],
                ["id" => 58, "section_id" => 5, "menu_group_id" => 15, "order" => 5, "slug" => "sc_inventory-valued", "name" => "inventory-valued", "dom_id" => "menu-inventory-valued", "dom_label" => "Kardex valorizado", "dom_route" => "stocks_management.valued.index", "description" => "Consulta costos y saldos valorizados."],
                ["id" => 50, "section_id" => 5, "menu_group_id" => 13, "order" => 1, "slug" => "sc_items-products", "name" => "items-products", "dom_id" => "menu-items-products", "dom_label" => "Productos", "dom_route" => "products.index", "description" => "Administra productos, precios y disponibilidad."],
                ["id" => 51, "section_id" => 5, "menu_group_id" => 13, "order" => 2, "slug" => "sc_items-services", "name" => "items-services", "dom_id" => "menu-items-services", "dom_label" => "Servicios", "dom_route" => "services.index", "description" => "Configura los servicios ofrecidos."],
                ["id" => 52, "section_id" => 5, "menu_group_id" => 13, "order" => 3, "slug" => "sc_items-subscriptions", "name" => "items-subscriptions", "dom_id" => "menu-items-subscriptions", "dom_label" => "Membresías", "dom_route" => "subscriptions.index", "description" => "Define planes, duración y precio."],
                ["id" => 53, "section_id" => 5, "menu_group_id" => 13, "order" => 4, "slug" => "sc_items-categories", "name" => "items-categories", "dom_id" => "menu-items-categories", "dom_label" => "Categorías", "dom_route" => "categories.index", "description" => "Organiza la oferta mediante categorías."],
                ["id" => 55, "section_id" => 5, "menu_group_id" => 13, "order" => 5, "slug" => "sc_items-brands", "name" => "items-brands", "dom_id" => "menu-items-brands", "dom_label" => "Marcas", "dom_route" => "brands.index", "description" => "Administra las marcas del catálogo."],
                ["id" => 59, "section_id" => 5, "menu_group_id" => 13, "order" => 6, "slug" => "sc_items-recipes", "name" => "items-recipes", "dom_id" => "menu-items-recipes", "dom_label" => "Recetas y platillos", "dom_route" => "recipes.index", "description" => "Configura recetas, platillos, toppings e insumos."],
                ["id" => 70, "section_id" => 7, "menu_group_id" => 20, "order" => 1, "slug" => "sc_configuration-my-company", "name" => "configuration-my-company", "dom_id" => "menu-configuration-my-company", "dom_label" => "Mi empresa", "dom_route" => "companies.index", "description" => "Actualiza la identidad y datos de la empresa."],
                ["id" => 60, "section_id" => 7, "menu_group_id" => 20, "order" => 2, "slug" => "sc_configuration-branches", "name" => "configuration-branches", "dom_id" => "menu-configuration-branches", "dom_label" => "Sucursales", "dom_route" => "branches.index", "description" => "Administra sedes y datos de contacto."],
                ["id" => 71, "section_id" => 7, "menu_group_id" => 20, "order" => 3, "slug" => "sc_configuration-users", "name" => "configuration-users", "dom_id" => "menu-configuration-users", "dom_label" => "Colaboradores", "dom_route" => "users.index", "description" => "Administra colaboradores internos."],
                ["id" => 104, "section_id" => 7, "menu_group_id" => 20, "order" => 4, "slug" => "sc_user-attendances", "name" => "user-attendances", "dom_id" => "menu-user-attendances", "dom_label" => "Asistencia del personal", "dom_route" => "user_attendances.index", "description" => "Controla ingresos, salidas y horas trabajadas."],
                ["id" => 61, "section_id" => 7, "menu_group_id" => 19, "order" => 1, "slug" => "sc_configuration-assets", "name" => "configuration-assets", "dom_id" => "menu-configuration-assets", "dom_label" => "Activos", "dom_route" => "assets.index", "description" => "Mantén el catálogo de equipos y bienes."],
                ["id" => 62, "section_id" => 7, "menu_group_id" => 19, "order" => 2, "slug" => "sc_configuration-assets-management", "name" => "configuration-assets-management", "dom_id" => "menu-configuration-assets-management", "dom_label" => "Gestión de activos", "dom_route" => "assets_management.index", "description" => "Asigna, traslada y controla activos."],
                ["id" => 63, "section_id" => 7, "menu_group_id" => 19, "order" => 3, "slug" => "sc_configuration-biometric-devices", "name" => "configuration-biometric-devices", "dom_id" => "menu-configuration-biometric-devices", "dom_label" => "Dispositivos biométricos", "dom_route" => "biometric_devices.index", "description" => "Configura dispositivos de control biométrico."],
                ["id" => 72, "section_id" => 7, "menu_group_id" => 21, "order" => 1, "slug" => "sc_configuration-roles", "name" => "configuration-roles", "dom_id" => "menu-configuration-roles", "dom_label" => "Perfiles de acceso", "dom_route" => "roles.index", "description" => "Define perfiles, permisos y módulos."],
                ["id" => 73, "section_id" => 7, "menu_group_id" => 21, "order" => 2, "slug" => "sc_configuration-master-data", "name" => "configuration-master-data", "dom_id" => "menu-configuration-master-data", "dom_label" => "Maestros internos", "dom_route" => "master_data.index", "description" => "Administra tributos, pagos y maestros internos."],
                ["id" => 74, "section_id" => 7, "menu_group_id" => 21, "order" => 3, "slug" => "sc-configuration-business-profile", "name" => "configuration-business-profile", "dom_id" => "menu-configuration-business-profile", "dom_label" => "Rubro y módulos", "dom_route" => "business_profile.index", "description" => "Configura el rubro y módulos sugeridos."],
                ["id" => 80, "section_id" => 1, "menu_group_id" => 24, "order" => 2, "slug" => "sc_reports", "name" => "reports", "dom_id" => "menu-reports", "dom_label" => "Reportes", "dom_route" => "reports.index", "description" => "Genera reportes para análisis operativo."],
            ],
        ];

        DB::transaction(function() use ($catalog): void {

            foreach($catalog["categories"] as $record) {

                DB::table("menu_categories")->insert([
                    "id" => $record["id"],
                    "slug" => $record["code"],
                    "name" => $record["label"],
                    "order" => $record["order"],
                    "status" => "active",
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);

            }

            foreach($catalog["sections"] as $record) {

                DB::table("sections")->insert($record + [
                    "status" => "active",
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);

            }

            foreach($catalog["groups"] as $record) {

                DB::table("menu_groups")->insert([
                    "id" => $record["id"],
                    "section_id" => $record["section_id"],
                    "slug" => $record["code"],
                    "name" => $record["label"],
                    "order" => $record["order"],
                    "status" => "active",
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);

            }

            foreach($catalog["items"] as $record) {

                DB::table("sub_sections")->insert($record + [
                    "dom_icon" => "",
                    "is_enabled_by_default" => !in_array($record["dom_route"], self::DISABLED_BY_DEFAULT_ROUTES, true),
                    "status" => "active",
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);

            }

        });

    }

    private function disableBusinessProfile(): void {

        if(!Schema::hasTable("sub_sections")) return;

        $moduleId = DB::table("sub_sections")
            ->where("dom_route", "business_profile.index")
            ->value("id");

        if(!$moduleId) return;

        $changed = DB::transaction(function() use ($moduleId): bool {

            $now = now();
            $changed = DB::table("sub_sections")
                ->where("id", $moduleId)
                ->where("is_enabled_by_default", true)
                ->update(["is_enabled_by_default" => false, "updated_at" => $now]) > 0;

            if(Schema::hasTable("companies_sub_sections")) {

                $changed = DB::table("companies_sub_sections")
                    ->where("sub_section_id", $moduleId)
                    ->where("status", "active")
                    ->update(["status" => "inactive", "updated_at" => $now]) > 0 || $changed;

            }

            if(Schema::hasTable("business_industry_module_sets")) {

                $changed = DB::table("business_industry_module_sets")
                    ->where("sub_section_id", $moduleId)
                    ->where("is_enabled_by_default", true)
                    ->update(["is_enabled_by_default" => false, "updated_at" => $now]) > 0 || $changed;

            }

            return $changed;

        });

        if($changed) CompanySectionService::clearTenantCache();

    }

    private function consolidateCatalogMasters(): void {

        if(!Schema::hasTable("menu_groups") || !Schema::hasTable("sub_sections")) {

            return;

        }

        $updated = DB::transaction(function(): bool {

            $masters = DB::table("menu_groups")
                ->where("id", 13)
                ->where("section_id", 5)
                ->first();

            if(!$masters) return false;

            $changed = DB::table("menu_groups")
                ->where("id", 13)
                ->where("name", "Oferta comercial")
                ->update(["name" => "Maestras", "updated_at" => now()]) > 0;

            $legacyGroup = DB::table("menu_groups")
                ->where("id", 14)
                ->where("section_id", 5)
                ->exists();

            if(!$legacyGroup) return $changed;

            $legacyItems = DB::table("sub_sections")
                ->where("menu_group_id", 14)
                ->orderBy("order")
                ->pluck("id");

            $orders = [53 => 4, 55 => 5, 59 => 6];
            $nextOrder = max(7, (int) DB::table("sub_sections")
                ->where("menu_group_id", 13)
                ->max("order") + 1);

            foreach($legacyItems as $id) {

                $order = $orders[$id] ?? $nextOrder++;

                DB::table("sub_sections")
                    ->where("id", $id)
                    ->update(["menu_group_id" => 13, "order" => $order, "updated_at" => now()]);

                if(Schema::hasTable("companies_sub_sections")) {

                    DB::table("companies_sub_sections")
                        ->where("sub_section_id", $id)
                        ->update(["sub_section_order" => NavigationOrder::item(2, $order), "updated_at" => now()]);

                }

            }

            DB::table("sub_sections")
                ->where("id", 59)
                ->where("menu_group_id", 13)
                ->update(["order" => 6, "updated_at" => now()]);

            if(Schema::hasTable("companies_sub_sections")) {

                DB::table("companies_sub_sections")
                    ->where("sub_section_id", 59)
                    ->update(["sub_section_order" => NavigationOrder::item(2, 6), "updated_at" => now()]);

            }

            DB::table("menu_groups")->where("id", 14)->delete();

            return true;

        });

        if($updated) {

            CompanySectionService::clearTenantCache();

        }

    }

    private function consolidateConfigurationAccess(): void {

        if(!Schema::hasTable("menu_groups") || !Schema::hasTable("sub_sections")) return;

        $changed = DB::transaction(function(): bool {

            $group = DB::table("menu_groups")->where("id", 21)->where("section_id", 7)->first();

            if(!$group) return false;

            $changed = DB::table("menu_groups")
                ->where("id", 21)
                ->where("name", "!=", "Accesos y ajustes")
                ->update(["name" => "Accesos y ajustes", "updated_at" => now()]) > 0;

            foreach([73 => 2, 74 => 3] as $id => $order) {

                $updated = DB::table("sub_sections")
                    ->where("id", $id)
                    ->where("section_id", 7)
                    ->where(function($query) use ($order): void {
                        $query->where("menu_group_id", "!=", 21)->orWhere("order", "!=", $order);
                    })
                    ->update(["menu_group_id" => 21, "order" => $order, "updated_at" => now()]);

                if(!$updated) continue;

                $changed = true;

                if(Schema::hasTable("companies_sub_sections")) {
                    DB::table("companies_sub_sections")
                        ->where("sub_section_id", $id)
                        ->update(["sub_section_order" => NavigationOrder::item(3, $order), "updated_at" => now()]);
                }

            }

            DB::table("menu_groups")->where("id", 22)->where("section_id", 7)->delete();

            return $changed;

        });

        if($changed) CompanySectionService::clearTenantCache();

    }

}
