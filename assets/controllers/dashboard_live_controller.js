import { Controller } from "@hotwired/stimulus";

/*
 * Animates live dashboard updates. The stats, recent list and status bars are
 * refreshed by Turbo Streams that replace their markup wholesale, which would
 * otherwise change with no transition. This snapshots a region's values just
 * before its stream renders and, once rendered, flashes only the cells whose
 * value changed (data-live-text) and slides status bars from their old to new
 * width (data-live-bar) — so the eye is drawn to what actually changed rather
 * than to the whole panel on every broadcast.
 */
export default class extends Controller {
    connect() {
        this.beforeStreamRender = this.beforeStreamRender.bind(this);
        document.addEventListener(
            "turbo:before-stream-render",
            this.beforeStreamRender,
        );
    }

    disconnect() {
        document.removeEventListener(
            "turbo:before-stream-render",
            this.beforeStreamRender,
        );
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
                this.animate(updated, before);
            }
        };
    }

    snapshot(region) {
        const texts = new Map();
        for (const el of region.querySelectorAll("[data-live-text]")) {
            texts.set(el.dataset.liveText, el.textContent.trim());
        }
        const bars = new Map();
        for (const el of region.querySelectorAll("[data-live-bar]")) {
            bars.set(el.dataset.liveBar, el.style.width);
        }
        return { texts, bars };
    }

    animate(region, before) {
        for (const el of region.querySelectorAll("[data-live-text]")) {
            if (before.texts.get(el.dataset.liveText) !== el.textContent.trim()) {
                el.classList.add("is-live-flash");
            }
        }

        if (this.prefersReducedMotion) {
            return;
        }

        for (const el of region.querySelectorAll("[data-live-bar]")) {
            const from = before.bars.get(el.dataset.liveBar);
            const to = el.style.width;
            // Animate the visual width old -> new without touching the inline
            // style: the bar is rendered at its final width, and a relational
            // rescale can move bars whose own count never changed.
            if (undefined !== from && from !== to && el.animate) {
                el.animate([{ width: from }, { width: to }], {
                    duration: 450,
                    easing: "ease",
                });
            }
        }
    }

    get prefersReducedMotion() {
        return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    }
}
