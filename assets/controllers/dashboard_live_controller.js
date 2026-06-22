import { Controller } from "@hotwired/stimulus";

/*
 * Animates live updates for the server-rendered dashboard regions (KPI cards
 * and the recent list). On connect it counts the KPI numbers up from zero;
 * when a Turbo Stream replaces a region it flashes only the values that
 * actually changed (data-live-text), so the eye is drawn to what moved.
 */
export default class extends Controller {
    connect() {
        this.beforeStreamRender = this.beforeStreamRender.bind(this);
        document.addEventListener("turbo:before-stream-render", this.beforeStreamRender);
        this.countUp();
    }

    disconnect() {
        document.removeEventListener("turbo:before-stream-render", this.beforeStreamRender);
    }

    beforeStreamRender(event) {
        const id = event.target.getAttribute("target");
        const region = id && this.element.querySelector(`#${CSS.escape(id)}`);
        if (!region) {
            return;
        }

        const before = this.snapshot(region);
        const render = event.detail.render;
        event.detail.render = async (streamElement) => {
            await render(streamElement);
            const updated = this.element.querySelector(`#${CSS.escape(id)}`);
            if (updated) {
                this.flashChanges(updated, before);
            }
        };
    }

    snapshot(region) {
        const texts = new Map();
        for (const el of region.querySelectorAll("[data-live-text]")) {
            texts.set(el.dataset.liveText, el.textContent.trim());
        }
        return texts;
    }

    flashChanges(region, before) {
        for (const el of region.querySelectorAll("[data-live-text]")) {
            if (before.get(el.dataset.liveText) !== el.textContent.trim()) {
                el.classList.add("is-live-flash");
            }
        }
    }

    countUp() {
        if (this.prefersReducedMotion) {
            return;
        }
        const duration = 1100;
        for (const el of this.element.querySelectorAll("[data-count]")) {
            const target = parseInt(el.dataset.count, 10);
            if (isNaN(target) || target <= 0) {
                continue;
            }
            const start = performance.now();
            const step = (now) => {
                const t = Math.min(1, (now - start) / duration);
                el.textContent = Math.round((1 - Math.pow(1 - t, 3)) * target);
                if (t < 1) {
                    requestAnimationFrame(step);
                }
            };
            requestAnimationFrame(step);
        }
    }

    get prefersReducedMotion() {
        return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    }
}
