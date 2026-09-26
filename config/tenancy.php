<?php

declare(strict_types=1);

return [
    "base_domain" => strtolower(trim((string) env("TENANCY_BASE_DOMAIN", "blapos.test"))),

    "platform_subdomain" => strtolower(trim((string) env("TENANCY_PLATFORM_SUBDOMAIN", "app"))),

    "enforce_subdomains" => (bool) env("TENANCY_ENFORCE_SUBDOMAINS", true),

    "reserved_subdomains" => array_values(array_filter(array_map(
        static fn(string $subdomain): string => strtolower(trim($subdomain)),
        explode(",", (string) env("TENANCY_RESERVED_SUBDOMAINS", "www,api,admin,app,mail,static,assets"))
    ))),

    "tenant_connection" => env("TENANT_DB_CONNECTION", "tenant"),

    "landlord_connection" => env("LANDLORD_DB_CONNECTION", "landlord"),

    "database_prefix" => env("TENANT_DB_PREFIX", "blapos_tenant_"),

    "enforce_database_prefix" => (bool) env("TENANT_ENFORCE_DB_PREFIX", true),

    "resolver_cache_seconds" => max(0, (int) env("TENANCY_RESOLVER_CACHE_SECONDS", 60)),

    "session_cookie_prefix" => env("TENANT_SESSION_COOKIE_PREFIX", "blapos_tenant"),

    "platform_session_cookie" => env("PLATFORM_SESSION_COOKIE", "blapos_platform_session"),

    "backups" => [
        "disk" => env("TENANT_BACKUP_DISK", "local"),
        "retention" => max(1, (int) env("TENANT_BACKUP_RETENTION", 10)),
        "timeout_seconds" => max(60, (int) env("TENANT_BACKUP_TIMEOUT", 900)),
        "dump_binary" => env("MYSQL_DUMP_BINARY", "mysqldump"),
        "client_binary" => env("MYSQL_CLIENT_BINARY", "mysql"),
    ],

    "platform_admin" => [
        "name" => env("PLATFORM_ADMIN_NAME", "Administrador SaaS"),
        "email" => strtolower((string) env("PLATFORM_ADMIN_EMAIL", "admin@app.blapos.test")),
        "password" => env("PLATFORM_ADMIN_PASSWORD", "Admin12345!"),
    ],
];
