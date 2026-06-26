import { Controller } from "@hotwired/stimulus";

/*
 * Debounced autosave for the initiative form.
 *
 * On the edit form it posts changes to the edit endpoint without re-rendering,
 * so focus and caret are never lost. On the new form (isNew) the first valid
 * save creates the initiative and the controller swaps to editing that record
 * in place (URL + action). File uploads are skipped on autosave and left for an
 * explicit Save, which keeps the request equivalent to a manual save.
 */
export default class extends Controller {
    static targets = ["status", "statusText", "save"];

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
        isNew: { type: Boolean, default: false },
    };

    initialize() {
        this.timer = null;
        this.inFlight = null;
        this.creating = false;
    }

    disconnect() {
        window.clearTimeout(this.timer);
        this.inFlight?.abort();
    }

    schedule(event) {
        // Picking a file can't autosave, but it reveals the Save button to upload it.
        this.revealSaveForFiles();

        // Files are uploaded only on an explicit Save.
        if (event?.target?.type === "file") {
            return;
        }
        // No "unsaved" text — the animated pen icon carries that state.
        this.setStatus("", "unsaved");
        window.clearTimeout(this.timer);
        this.timer = window.setTimeout(() => this.save(), this.debounceValue);
    }

    async save() {
        // A picked-but-unsaved file is left for Save so we never half-upload it.
        if (this.hasPendingFile()) {
            this.setStatus(this.filesHintTextValue, "unsaved");
            return;
        }

        // Hold off while a brand-new initiative is being created so we never
        // POST the create twice; the next change saves it once it exists.
        if (this.creating) {
            return;
        }

        // Edits can supersede an in-flight request; a create must run to completion.
        const creating = this.isNewValue;
        if (!creating) {
            this.inFlight?.abort();
        }
        this.inFlight = new AbortController();
        this.creating = creating;

        // Reveal the saving spinner only for slow saves (> 2s); a quick save jumps
        // straight to "Gemt" with no flicker.
        const savingTimer = window.setTimeout(
            () => this.setStatus(this.savingTextValue, "saving"),
            2000,
        );

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

            if (201 === response.status) {
                // The draft now exists — edit it in place from here on.
                const location = response.headers.get("X-Initiative-Location");
                if (location) {
                    this.element.action = location;
                    this.isNewValue = false;
                    window.history.replaceState({}, "", location);
                    // Show URL = edit URL minus the trailing /edit; lets the
                    // breadcrumb turn the title into a link to the new record.
                    this.dispatch("created", {
                        detail: { showUrl: location.replace(/\/edit$/, "") },
                    });
                }
                this.setStatus(
                    `${this.savedTextValue} · ${this.timestamp()}`,
                    "saved",
                );
            } else if (204 === response.status) {
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
        } finally {
            window.clearTimeout(savingTimer);
            this.creating = false;
        }
    }

    hasPendingFile() {
        return Array.from(
            this.element.querySelectorAll('input[type="file"]'),
        ).some((input) => input.files && input.files.length > 0);
    }

    // Files only upload on an explicit Save, so surface that button while one is staged.
    revealSaveForFiles() {
        if (this.hasSaveTarget) {
            this.saveTarget.hidden = !this.hasPendingFile();
        }
    }

    setStatus(text, state) {
        if (!this.hasStatusTarget) {
            return;
        }
        this.statusTarget.className = `autosave-status autosave-status--${state}`;
        if (this.hasStatusTextTarget) {
            this.statusTextTarget.textContent = text;
        }
    }

    timestamp() {
        const locale = document.documentElement.lang || "en";

        return new Date().toLocaleTimeString(locale, {
            hour: "2-digit",
            minute: "2-digit",
        });
    }
}
