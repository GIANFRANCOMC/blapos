export async function writeClipboard(text) {

    if(navigator.clipboard?.writeText && window.isSecureContext) {

        await navigator.clipboard.writeText(text);
        return;

    }

    const textarea = document.createElement("textarea");

    textarea.value = text;
    textarea.setAttribute("readonly", "");
    textarea.style.position = "fixed";
    textarea.style.left = "-9999px";
    textarea.style.top = "0";
    document.body.appendChild(textarea);
    textarea.select();

    try {

        if(!document.execCommand("copy")) throw new Error("Clipboard copy failed");

    }finally {

        textarea.remove();

    }

}
