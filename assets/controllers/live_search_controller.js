import { Controller } from "@hotwired/stimulus";

/*
 * Live filtering for the initiative list. The form targets a Turbo Frame
 * (data-turbo-frame), so submitting it swaps only the results — no full page
 * load. Typing is debounced; selects submit on change. The submit button is
 * gone: this controller drives the submit, and clear() resets the fields.
 */
export default class extends Controller {
    static values = { debounce: { type: Number, default: 300 } };

    connect() {
        this.timer = null;
    }

    disconnect() {
        window.clearTimeout(this.timer);
    }

    submit() {
        window.clearTimeout(this.timer);
        this.timer = window.setTimeout(
            () => this.element.requestSubmit(),
            this.debounceValue,
        );
    }

    clear() {
        for (const input of this.element.querySelectorAll("input")) {
            if (!["submit", "button", "reset"].includes(input.type)) {
                input.value = "";
            }
        }
        for (const select of this.element.querySelectorAll("select")) {
            select.selectedIndex = 0;
        }
        window.clearTimeout(this.timer);
        this.element.requestSubmit();
    }
}
