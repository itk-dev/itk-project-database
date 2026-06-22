import { Controller } from "@hotwired/stimulus";

/*
 * Drives the initiative form's completion bar. Completion = filled / total over a
 * fixed set of fields (the `fields` value, shared with the server so the bar and
 * the list percentage always agree), grouped by field key so a multi-input field
 * (links, funding) counts once. The bar's width and hue (red -> green) track the
 * ratio. Used on both the new and edit forms.
 */
export default class extends Controller {
    static targets = ["fill", "label"];

    static values = {
        fields: { type: Array, default: [] },
    };

    connect() {
        this.recompute();
    }

    recompute() {
        if (!this.hasFillTarget) {
            return;
        }

        const wanted = new Set(this.fieldsValue);
        const filled = new Set();

        for (const el of this.element.querySelectorAll(
            "input, select, textarea",
        )) {
            const key = this.fieldKey(el);
            if (key && wanted.has(key) && this.isFilled(el)) {
                filled.add(key);
            }
        }

        const total = wanted.size;
        const ratio = total ? filled.size / total : 0;
        const percent = Math.round(ratio * 100);

        this.fillTarget.style.width = `${percent}%`;
        this.fillTarget.style.backgroundColor = `hsl(${Math.round(ratio * 120)}, 72%, 45%)`;
        this.fillTarget.parentElement?.setAttribute(
            "aria-valuenow",
            String(percent),
        );

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = `${percent}%`;
        }
    }

    // "initiative[links][0]" -> "links", "initiative[funding][]" -> "funding".
    fieldKey(el) {
        const match = el.name?.match(/\[([^\]]+)\]/);

        return match ? match[1] : null;
    }

    isFilled(el) {
        if ("checkbox" === el.type || "radio" === el.type) {
            return el.checked;
        }
        if ("SELECT" === el.tagName) {
            return el.multiple
                ? Array.from(el.selectedOptions).some((o) => "" !== o.value)
                : "" !== el.value;
        }
        return "" !== el.value.trim();
    }
}
