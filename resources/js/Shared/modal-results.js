const SUSPENDED_CLASS = "br-modal-result-hidden";
const suspendedCounts = new WeakMap();

function isResult(options) {
    return ["success", "error"].includes(options.icon)
        && options.showConfirmButton !== false
        && !options.showCancelButton && !options.showDenyButton
        && !options.input && !options.preConfirm;
}

function closeModal(element, instance) {
    return new Promise(resolve => {
        const finish = () => {
            element.removeEventListener("hidden.bs.modal", finish);
            resolve();
        };
        element.addEventListener("hidden.bs.modal", finish, {once: true});

        const hide = () => {
            instance.hide();
            // A module can explicitly prevent closing while another operation runs.
            if(instance._isShown && !instance._isTransitioning) finish();
        };

        if(instance._isTransitioning) {
            if(instance._isShown) element.addEventListener("shown.bs.modal", hide, {once: true});
        }else if(element.classList.contains("show")) {
            hide();
        }else {
            finish();
        }
    });
}

/** One lifecycle for final results; confirmations, inputs and loaders keep their context. */
export function prepareModalResult({options, document, bootstrap}) {
    const Modal = bootstrap?.Modal;
    if(!isResult(options) || !Modal || !document.querySelectorAll) return null;

    const modals = [...document.querySelectorAll(".modal")]
        .filter(element => element.classList.contains("show") || element.style.display === "block");
    if(!modals.length) return null;

    const instances = new Map(modals.map(element => [element, Modal.getInstance(element)]));
    const focus = document.activeElement;
    const suspended = [...modals, ...document.querySelectorAll(".modal-backdrop")];
    suspended.forEach(element => {
        suspendedCounts.set(element, (suspendedCounts.get(element) || 0) + 1);
        element.classList.add(SUSPENDED_CLASS);
    });
    focus?.blur?.();

    const deactivate = () => instances.forEach(instance => instance?._focustrap?.deactivate());
    deactivate();

    const closing = [];
    if(options.icon === "success") {
        // The last non-inert modal is the active form. A stacked parent keeps its data.
        const active = modals.filter(element => !element.inert && element.classList.contains("show")).at(-1);
        modals.forEach(element => {
            const instance = instances.get(element);
            if(instance && (element === active || !element.classList.contains("show"))) {
                closing.push(closeModal(element, instance));
            }
        });
    }

    let restored = false;
    return {
        ready: Promise.all(closing).then(deactivate),
        restore() {
            if(restored) return;
            restored = true;
            suspended.forEach(element => {
                const count = (suspendedCounts.get(element) || 1) - 1;
                if(count) suspendedCounts.set(element, count);
                else {
                    suspendedCounts.delete(element);
                    element.classList.remove(SUSPENDED_CLASS);
                }
            });
            const remaining = modals.filter(element => element.classList.contains("show") && !element.inert).at(-1);
            if(!remaining || suspendedCounts.has(remaining)) return;
            instances.get(remaining)?._focustrap?.activate();
            if(focus?.isConnected && remaining.contains(focus)) focus.focus({preventScroll: true});
        }
    };
}
