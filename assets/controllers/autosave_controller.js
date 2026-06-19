import { Controller } from "@hotwired/stimulus";

/*
 * Debounced autosave for the initiative edit form.
 *
 * A short moment after the last change it posts the form to the same edit
 * endpoint and reports the result in a small status line — without re-rendering
 * the form, so focus and caret are never lost. File uploads are skipped on
 * autosave and left for an explicit Save, which keeps the request equivalent to
 * a manual save that touches no files.
 */
export default class extends Controller {
    static targets = ["status"];

    static values = {
        debounce: { type: Number, default: 800 },
        savingText: { type: String, default: "Saving…" },
        savedText: { type: String, default: "Saved" },
        unsavedText: { type: String, default: "Unsaved changes" },
        errorText: {
            type: String,
            default: "Couldn’t save — check the required fields",
        },
        offlineText: {
            type: String,
            default: "Save failed — your changes are kept here",
        },
        filesHintText: { type: String, default: "Click Save to upload files" },
    };

    initialize() {
        this.timer = null;
        this.inFlight = null;
    }

    disconnect() {
        window.clearTimeout(this.timer);
        this.inFlight?.abort();
    }

    schedule(event) {
        // Files are uploaded only on an explicit Save.
        if (event?.target?.type === "file") {
            return;
        }
        this.setStatus(this.unsavedTextValue, "unsaved");
        window.clearTimeout(this.timer);
        this.timer = window.setTimeout(() => this.save(), this.debounceValue);
    }

    async save() {
        // A picked-but-unsaved file is left for Save so we never half-upload it.
        if (this.hasPendingFile()) {
            this.setStatus(this.filesHintTextValue, "unsaved");
            return;
        }

        this.inFlight?.abort();
        this.inFlight = new AbortController();
        this.setStatus(this.savingTextValue, "saving");

        try {
            const response = await fetch(this.element.action, {
                method: "POST",
                body: new FormData(this.element),
                headers: {
                    "X-Autosave": "1",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
                signal: this.inFlight.signal,
            });

            if (204 === response.status) {
                this.setStatus(
                    `${this.savedTextValue} · ${this.timestamp()}`,
                    "saved",
                );
            } else if (422 === response.status) {
                this.setStatus(this.errorTextValue, "error");
            } else {
                this.setStatus(this.offlineTextValue, "error");
            }
        } catch (error) {
            if ("AbortError" !== error.name) {
                this.setStatus(this.offlineTextValue, "error");
            }
        }
    }

    hasPendingFile() {
        return Array.from(
            this.element.querySelectorAll('input[type="file"]'),
        ).some((input) => input.files && input.files.length > 0);
    }

    setStatus(text, state) {
        if (!this.hasStatusTarget) {
            return;
        }
        this.statusTarget.textContent = text;
        this.statusTarget.className = `autosave-status autosave-status--${state}`;
    }

    timestamp() {
        const locale = document.documentElement.lang || "en";

        return new Date().toLocaleTimeString(locale, {
            hour: "2-digit",
            minute: "2-digit",
        });
    }
}
