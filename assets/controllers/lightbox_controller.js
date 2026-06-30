import { Controller } from "@hotwired/stimulus";

/*
 * Opens an image preview in an in-page overlay instead of a new tab. Attached to
 * the image link; the href is the full-size source. The scroll wheel zooms in and
 * out; once zoomed, the image can be dragged to pan. Closes on the backdrop, the
 * close button or Escape, and restores focus to the link that opened it.
 */
export default class extends Controller {
    static values = {
        src: String,
        closeLabel: { type: String, default: "Close" },
    };

    open(event) {
        event.preventDefault();
        const src = this.srcValue || this.element.getAttribute("href");
        if (!src) {
            return;
        }

        const overlay = document.createElement("div");
        overlay.className = "lightbox";
        overlay.setAttribute("role", "dialog");
        overlay.setAttribute("aria-modal", "true");

        const close = document.createElement("button");
        close.type = "button";
        close.className = "lightbox__close";
        close.setAttribute("aria-label", this.closeLabelValue);
        close.textContent = "×";

        const img = document.createElement("img");
        img.className = "lightbox__img";
        img.src = src;
        img.alt = "";

        overlay.append(close, img);

        const min = 1;
        const max = 4;
        let zoom = 1;

        const applyZoom = () => {
            if (zoom <= 1) {
                img.classList.remove("lightbox__img--zoomed");
                img.style.removeProperty("width");
                img.style.removeProperty("max-width");
                img.style.removeProperty("max-height");
                return;
            }
            // Capture the fitted width before lifting the size constraints, so the
            // zoom factor is relative to what the user actually sees.
            if (!img.dataset.baseWidth) {
                img.dataset.baseWidth = String(img.clientWidth);
            }
            img.classList.add("lightbox__img--zoomed");
            img.style.maxWidth = "none";
            img.style.maxHeight = "none";
            img.style.width = `${Number(img.dataset.baseWidth) * zoom}px`;
        };

        const onWheel = (event) => {
            event.preventDefault();
            // Normalise wheel deltas (line/page modes report small integers) and
            // zoom multiplicatively, so a trackpad gesture doesn't jump to max.
            let delta = event.deltaY;
            if (1 === event.deltaMode) {
                delta *= 16;
            } else if (2 === event.deltaMode) {
                delta *= window.innerHeight;
            }
            const previous = zoom;
            zoom = Math.min(
                max,
                Math.max(min, zoom * Math.exp(-delta * 0.001)),
            );
            if (zoom !== previous) {
                applyZoom();
            }
        };

        let dragging = false;
        let startX = 0;
        let startY = 0;
        let startLeft = 0;
        let startTop = 0;
        const onDown = (event) => {
            if (zoom <= 1 || 0 !== event.button) {
                return;
            }
            dragging = true;
            startX = event.clientX;
            startY = event.clientY;
            startLeft = overlay.scrollLeft;
            startTop = overlay.scrollTop;
            img.style.cursor = "grabbing";
            event.preventDefault();
        };
        const onMove = (event) => {
            if (!dragging) {
                return;
            }
            overlay.scrollLeft = startLeft - (event.clientX - startX);
            overlay.scrollTop = startTop - (event.clientY - startY);
        };
        const onUp = () => {
            dragging = false;
            img.style.removeProperty("cursor");
        };

        const dismiss = () => {
            overlay.remove();
            document.removeEventListener("keydown", onKey);
            window.removeEventListener("mousemove", onMove);
            window.removeEventListener("mouseup", onUp);
            document.body.classList.remove("is-lightbox-open");
            this.element.focus();
        };
        const onKey = (event) => {
            if ("Escape" === event.key) {
                dismiss();
            }
        };

        overlay.addEventListener("click", (event) => {
            if (event.target === overlay || event.target === close) {
                dismiss();
            }
        });
        overlay.addEventListener("wheel", onWheel, { passive: false });
        img.addEventListener("mousedown", onDown);
        window.addEventListener("mousemove", onMove);
        window.addEventListener("mouseup", onUp);
        document.addEventListener("keydown", onKey);

        document.body.classList.add("is-lightbox-open");
        document.body.appendChild(overlay);
        close.focus();
    }
}
