import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';
import path from 'node:path';
import {buildSystemCss} from './scripts/build-system-css.mjs';

function systemBrandingCss() {

    const sourceDirectories = ['br-branding', 'br-login', 'demo']
        .map(directory => path.resolve('resources/css/System', directory) + path.sep);

    return {
        name: 'system-branding-css',
        buildStart() {

            buildSystemCss();

        },
        configureServer(server) {

            buildSystemCss();
            server.watcher.add(sourceDirectories.map(directory => path.join(directory, '*.css')));
            server.watcher.on('change', changedPath => {

                const file = path.resolve(changedPath);

                if(!file.endsWith('.css') || !sourceDirectories.some(directory => file.startsWith(directory))) {

                    return;

                }

                buildSystemCss();
                server.ws.send({type: 'full-reload'});

            });

        },
    };

}

export default defineConfig({
    resolve: {
        alias: {
            '@System': fileURLToPath(new URL('./resources/js/System', import.meta.url)),
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        systemBrandingCss(),
        vue(),
        laravel({
            input: [
                'resources/css/System/app.css',
                'resources/css/System/platform.css',
                'resources/css/Platform/app.css',
                'resources/js/System/app.js',
                'resources/js/System/Layout/main.js',
                'resources/js/Platform/app.js',

                // Guest Pages
                'resources/js/Guest/Pages/home/main.js',
                'resources/js/Guest/Pages/book_complaints/main.js',
                'resources/js/Guest/Pages/tracking_attendances/main.js',

                // System Pages - Assets
                'resources/js/System/Pages/Assets/assets/main.js',
                'resources/js/System/Pages/Assets/assets_management/main.js',

                // System Pages - Catalogs
                'resources/js/System/Pages/Catalogs/brands/main.js',
                'resources/js/System/Pages/Catalogs/categories/main.js',
                'resources/js/System/Pages/Catalogs/products/main.js',
                'resources/js/System/Pages/Catalogs/services/main.js',
                'resources/js/System/Pages/Catalogs/subscriptions/main.js',

                // System Pages - Customers
                'resources/js/System/Pages/Customers/customers/main.js',
                'resources/js/System/Pages/Customers/tracking_attendances/main.js',
                'resources/js/System/Pages/Customers/tracking_customers/main.js',
                'resources/js/System/Pages/Customers/tracking_subscriptions/main.js',

                // System Pages - Essentials
                'resources/js/System/Pages/Essentials/dashboard/main.js',
                'resources/js/System/Pages/Essentials/account/main.js',
                'resources/js/System/Pages/Essentials/home/main.js',
                'resources/js/System/Pages/Essentials/reports/main.js',

                // System Pages - General
                'resources/js/System/Pages/General/master_data/main.js',

                // System Pages - Customers (Tracking Notifications)
                'resources/js/System/Pages/Customers/tracking_notifications/main.js',

                // System Pages - Organizations
                'resources/js/System/Pages/Organizations/book_complaints/main.js',
                'resources/js/System/Pages/Organizations/branches/main.js',
                'resources/js/System/Pages/Organizations/companies/main.js',
                'resources/js/System/Pages/Organizations/roles/main.js',
                'resources/js/System/Pages/Organizations/users/main.js',
                'resources/js/System/Pages/Organizations/user_attendances/main.js',
                'resources/js/System/Pages/Organizations/business_profile/main.js',

                // System Pages - Operations
                'resources/js/System/Pages/Operations/service_operations/main.js',

                // System Pages - Purchases
                'resources/js/System/Pages/Purchases/purchases/main.js',
                'resources/js/System/Pages/Purchases/suppliers/main.js',

                // System Pages - Finance
                'resources/js/System/Pages/Finance/accounts_receivable/main.js',
                'resources/js/System/Pages/Finance/accounts_payable/main.js',
                'resources/js/System/Pages/Finance/cash_registers/main.js',
                'resources/js/System/Pages/Finance/cash_sessions/main.js',
                'resources/js/System/Pages/Finance/cash_movements/main.js',
                'resources/js/System/Pages/Finance/cash_summary/main.js',
                'resources/js/System/Pages/Finance/misc_expenses/main.js',

                // System Pages - Sales
                'resources/js/System/Pages/Sales/sales/list.js',
                'resources/js/System/Pages/Sales/sales/main.js',
                'resources/js/System/Pages/Sales/sales/deliveries.js',
                'resources/js/System/Pages/Sales/pos/main.js',
                'resources/js/System/Pages/Sales/quotations/main.js',

                // System Pages - Warehouses
                'resources/js/System/Pages/Warehouses/inventory/stock.js',
                'resources/js/System/Pages/Warehouses/inventory/transfers.js',
                'resources/js/System/Pages/Warehouses/inventory/guides.js',
                'resources/js/System/Pages/Warehouses/inventory/kardex.js',
                'resources/js/System/Pages/Warehouses/inventory/valued-kardex.js',

                // System Pages - Devices
                'resources/js/System/Pages/Devices/biometric_devices/main.js',

                // System Helpers
                'resources/js/System/Helpers/Alerts.js',
                'resources/js/System/Helpers/Constants.js',
                'resources/js/System/Helpers/Requests.js',
                'resources/js/System/Helpers/Utils.js',
            ],
            refresh: true,
        }),
    ],
});
