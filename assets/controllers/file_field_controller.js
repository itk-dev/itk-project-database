import { Controller } from "@hotwired/stimulus";

/*
 * Drives a single upload field's transient UI: once a file is picked it shows
 * the name and a progress bar that the autosave controller fills as the form
 * (with the file) uploads. On success the media turbo-frame reloads and the
 * server-rendered "uploaded" view replaces this; on failure the bar is hidden
 * so the user can try again.
 */
export default class extends Controller {
    static targets = ["filename", "progress", "bar"];

    connect() {
        this.onStart = this.onStart.bind(this);
        this.onProgress = this.onProgress.bind(this);
        this.onEnd = this.onEnd.bind(this);
        document.addEventListener("autosave:uploadstart", this.onStart);
        document.addEventListener("autosave:uploadprogress", this.onProgress);
        document.addEventListener("autosave:uploadend", this.onEnd);
    }

    disconnect() {
        document.removeEventListener("autosave:uploadstart", this.onStart);
        document.removeEventListener(
            "autosave:uploadprogress",
            this.onProgress,
        );
        document.removeEventListener("autosave:uploadend", this.onEnd);
    }

    get input() {
        return this.element.querySelector('input[type="file"]');
    }

    get pending() {
        const input = this.input;
        return Boolean(input && input.files && input.files.length > 0);
    }

    picked() {
        if (!this.pending) {
            return;
        }
        if (this.hasFilenameTarget) {
            this.filenameTarget.textContent = this.input.files[0].name;
        }
        this.show(0);
    }

    onStart() {
        if (this.pending) {
            this.show(0);
        }
    }

    onProgress(event) {
        if (this.pending) {
            this.show(event.detail.percent);
        }
    }

    onEnd(event) {
        if (!this.pending) {
            return;
        }
        if (event.detail && event.detail.ok) {
            // Hold full while the media frame reloads and replaces this field.
            this.setBar(100);
        } else {
            this.hide();
        }
    }

    show(percent) {
        if (this.hasProgressTarget) {
            this.progressTarget.hidden = false;
        }
        this.setBar(percent);
    }

    setBar(percent) {
        if (this.hasBarTarget) {
            this.barTarget.style.width = `${percent}%`;
        }
    }

    hide() {
        if (this.hasProgressTarget) {
            this.progressTarget.hidden = true;
        }
    }
}
