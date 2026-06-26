import "./stimulus_bootstrap.js";
import "./styles/app.css";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.default.min.css";

function initUserMenu() {
    const toggle = document.getElementById("userMenuToggle");
    const menu = document.getElementById("userMenu");
    if (!toggle || !menu || toggle.dataset.bound) {
        return;
    }
    toggle.dataset.bound = "1";

    toggle.addEventListener("click", (event) => {
        event.stopPropagation();
        menu.classList.toggle("is-open");
    });
}

function initCollections() {
    document.querySelectorAll("[data-collection]").forEach((collection) => {
        if (collection.dataset.bound) {
            return;
        }
        const list = collection.querySelector("[data-collection-list]");
        const addButton = collection.querySelector("[data-collection-add]");
        if (!list || !addButton) {
            return;
        }
        collection.dataset.bound = "1";

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

function initContactSelect() {
    document.querySelectorAll("[data-contact-select]").forEach((select) => {
        if (select.dataset.bound) {
            return;
        }
        select.dataset.bound = "1";
        new TomSelect(select, {
            plugins: ["remove_button"],
            hideSelected: true,
            maxOptions: null,
        });
    });
}

// The document survives Turbo navigations, so the outside-click handler is
// registered once here rather than re-added on every page.
document.addEventListener("click", (event) => {
    const menu = document.getElementById("userMenu");
    const toggle = document.getElementById("userMenuToggle");
    if (menu && !menu.contains(event.target) && event.target !== toggle) {
        menu.classList.remove("is-open");
    }
});

// Turbo Drive swaps <body> on each visit and never fires DOMContentLoaded;
// turbo:load runs on the first load and on every subsequent visit.
document.addEventListener("turbo:load", () => {
    initUserMenu();
    initCollections();
    initContactSelect();
});
