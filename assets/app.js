import "./styles/app.css";

function initUserMenu() {
    const toggle = document.getElementById("userMenuToggle");
    const menu = document.getElementById("userMenu");
    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener("click", (event) => {
        event.stopPropagation();
        menu.classList.toggle("is-open");
    });

    document.addEventListener("click", (event) => {
        if (!menu.contains(event.target) && event.target !== toggle) {
            menu.classList.remove("is-open");
        }
    });
}

function initCollections() {
    document.querySelectorAll("[data-collection]").forEach((collection) => {
        const list = collection.querySelector("[data-collection-list]");
        const addButton = collection.querySelector("[data-collection-add]");
        if (!list || !addButton) {
            return;
        }

        let index = list.querySelectorAll("[data-collection-item]").length;

        const addRemoveButton = (item) => {
            if (item.querySelector("[data-collection-remove]")) {
                return;
            }
            const remove = document.createElement("button");
            remove.type = "button";
            remove.className = "btn btn--danger btn--sm";
            remove.dataset.collectionRemove = "";
            remove.textContent = collection.dataset.removeLabel || "Remove";
            remove.addEventListener("click", () => item.remove());
            item.appendChild(remove);
        };

        list.querySelectorAll("[data-collection-item]").forEach(
            addRemoveButton,
        );

        addButton.addEventListener("click", () => {
            const prototype = collection.dataset.prototype;
            const html = prototype.replace(/__name__/g, String(index));
            index += 1;

            const wrapper = document.createElement("div");
            wrapper.className = "collection__item";
            wrapper.dataset.collectionItem = "";
            wrapper.innerHTML = html;
            addRemoveButton(wrapper);
            list.appendChild(wrapper);
        });
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initUserMenu();
    initCollections();
});
