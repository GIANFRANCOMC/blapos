import fs from "node:fs";
import path from "node:path";
import {fileURLToPath} from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");

const bundles = [
    {
        name: "br-branding",
        sourceDir: "resources/css/System/br-branding",
        output: "public/System/assets/css/br-branding.css",
        description: "tokens, componentes visuales y estilos de módulos System/Guest",
    },
    {
        name: "br-login",
        sourceDir: "resources/css/System/br-login",
        output: "public/System/assets/css/br-login.css",
        description: "login y accesos invitados",
        split: [
            [1, "00-login.css"],
        ],
    },
    {
        name: "demo",
        sourceDir: "resources/css/System/demo",
        output: "public/System/assets/css/demo.css",
        description: "ajustes mínimos de plantilla",
        split: [
            [1, "00-demo.css"],
        ],
    },
];

function absolute(relativePath) {
    return path.join(root, relativePath);
}

function ensureDir(directory) {
    fs.mkdirSync(directory, {recursive: true});
}

function readUtf8(file) {
    return fs.readFileSync(file, "utf8").replace(/^\uFEFF/, "");
}

function writeUtf8(file, content) {
    ensureDir(path.dirname(file));
    fs.writeFileSync(file, content, "utf8");
}

function normalizeCss(content) {
    return content
        .replace(/^\s*@charset\s+["']UTF-8["'];\s*/i, "")
        .replace(/\r\n/g, "\n")
        .trim();
}

function cssFiles(sourceDir) {
    if (!fs.existsSync(sourceDir)) {
        return [];
    }

    return fs.readdirSync(sourceDir)
        .filter((file) => file.endsWith(".css"))
        .sort((a, b) => a.localeCompare(b, "en"))
        .map((file) => path.join(sourceDir, file));
}

function buildBundle(bundle) {
    const sourceDir = absolute(bundle.sourceDir);
    const files = cssFiles(sourceDir);

    if (!files.length) {
        throw new Error(`No hay parciales CSS en ${bundle.sourceDir}. Ejecuta npm run build:css:system:seed.`);
    }

    const parts = files.map((file) => {
        const relativeFile = path.relative(root, file).replaceAll("\\", "/");
        return `/* Source: ${relativeFile} */\n${normalizeCss(readUtf8(file))}`;
    });

    const output = [
        `@charset "UTF-8";`,
        `/*`,
        ` * Archivo generado: ${bundle.output}`,
        ` * Fuente: ${bundle.sourceDir} (${bundle.description}).`,
        ` * No editar este archivo directamente; modifica los parciales y ejecuta npm run build:css:system.`,
        ` */`,
        "",
        parts.join("\n\n"),
        "",
    ].join("\n");

    writeUtf8(absolute(bundle.output), output);
}

function writeViteEntrypoint() {
    const imports = bundles.flatMap((bundle) => {
        const sourceDir = absolute(bundle.sourceDir);
        return cssFiles(sourceDir).map((file) => {
            const relativeFile = path.relative(absolute("resources/css/System"), file).replaceAll("\\", "/");
            return `@import "./${relativeFile}";`;
        });
    });

    const content = [
        "/*",
        " * Entry CSS preparado para Vite.",
        " * Hoy los layouts siguen usando los archivos generados en public/System/assets/css.",
        " * Cuando se migre el layout a @vite, este archivo ya concentra el mismo orden de parciales.",
        " */",
        ...imports,
        "",
    ].join("\n");

    writeUtf8(absolute("resources/css/System/platform.css"), content);
}

export function buildSystemCss() {

    for(const bundle of bundles) {

        buildBundle(bundle);

    }

    writeViteEntrypoint();

}

if(process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {

    buildSystemCss();
    console.log("CSS System generado correctamente.");

}
