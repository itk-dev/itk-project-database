import { Controller } from "@hotwired/stimulus";

/*
 * A friendly star mascot in the bottom-right corner. It greets the user, then
 * periodically pops a speech bubble cheering them on to start or finish an
 * initiative. Rarely it invites the user to a game of catch: click it and it
 * darts away from the pointer; catch it and it turns the tables and chases the
 * pointer until it tags it back. Messages arrive already translated.
 */
export default class extends Controller {
    static targets = ["bubble", "text", "cta", "play", "finish"];

    static values = {
        messages: { type: Array, default: [] },
        interval: { type: Number, default: 24000 },
        playChance: { type: Number, default: 0.08 },
        invite: String,
        caught: String,
        gotcha: String,
        giveup: String,
        escaped: String,
        finishText: String,
    };

    connect() {
        this.mode = "idle";
        const state = this.loadState();
        this.lastIndex = state.lastIndex ?? -1;
        this.lastShownAt = state.lastShownAt ?? 0;
        // Resume the cadence where the previous page left off, so navigating
        // around doesn't pop a fresh message on every load.
        const sinceLast = Date.now() - this.lastShownAt;
        this.scheduleNext(Math.max(1800, this.intervalValue - sinceLast));
    }

    disconnect() {
        this.endGame();
        window.clearTimeout(this.cycleTimer);
        window.clearTimeout(this.hideTimer);
        window.clearTimeout(this.inviteTimer);
        window.clearTimeout(this.quietTimer);
    }

    scheduleNext(delay) {
        window.clearTimeout(this.cycleTimer);
        this.cycleTimer = window.setTimeout(() => this.tick(), delay);
    }

    tick() {
        if ("idle" === this.mode && !this.quiet) {
            if (
                !this.prefersReducedMotion &&
                Math.random() < this.playChanceValue
            ) {
                this.invite();
            } else {
                this.speak();
            }
        }
        this.scheduleNext(this.intervalValue);
    }

    // The avatar click means different things depending on the current mode.
    poke() {
        if ("invited" === this.mode) {
            this.startFlee();
        } else if ("flee" === this.mode) {
            this.caught();
        } else if ("idle" === this.mode) {
            this.speak();
        }
    }

    speak() {
        // Now and then, nudge the user to finish their least-complete initiative.
        if (this.hasFinishTarget && this.finishTextValue && Math.random() < 0.4) {
            this.say(this.finishTextValue, "finish");

            return;
        }
        this.say(this.nextMessage(), "cta");
    }

    invite() {
        this.mode = "invited";
        this.say(this.inviteValue, "play");
        window.clearTimeout(this.inviteTimer);
        this.inviteTimer = window.setTimeout(() => {
            if ("invited" === this.mode) {
                this.mode = "idle";
                this.hide();
            }
        }, 8000);
    }

    startFlee() {
        window.clearTimeout(this.inviteTimer);
        this.hide();
        this.mode = "flee";
        this.anchorPosition();
        this.fleeHandler = (event) => this.onFlee(event);
        document.addEventListener("pointermove", this.fleeHandler);
        this.giveUpTimer = window.setTimeout(() => this.giveUp(), 15000);
    }

    onFlee(event) {
        if (this.fleeCooldown) {
            return;
        }
        const box = this.avatar.getBoundingClientRect();
        const cx = box.left + box.width / 2;
        const cy = box.top + box.height / 2;
        if (Math.hypot(cx - event.clientX, cy - event.clientY) >= 75) {
            return;
        }
        // A short, bounded hop straight away from the pointer (clamped on-screen),
        // so the mascot can be cornered and caught instead of teleporting away.
        const angle = Math.atan2(cy - event.clientY, cx - event.clientX);
        const here = this.element.getBoundingClientRect();
        this.moveTo(
            here.left + Math.cos(angle) * 95,
            here.top + Math.sin(angle) * 95,
        );
        this.fleeCooldown = window.setTimeout(() => {
            this.fleeCooldown = null;
        }, 220);
    }

    caught() {
        window.clearTimeout(this.giveUpTimer);
        document.removeEventListener("pointermove", this.fleeHandler);
        this.mode = "chase-prep";
        this.say(this.caughtValue);
        this.prepTimer = window.setTimeout(() => this.startChase(), 1700);
    }

    startChase() {
        this.hide();
        this.mode = "chase";
        this.element.classList.add("mascot--chasing");
        this.pointer = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
        this.chaseHandler = (event) => {
            this.pointer.x = event.clientX;
            this.pointer.y = event.clientY;
        };
        document.addEventListener("pointermove", this.chaseHandler);
        this.giveUpTimer = window.setTimeout(() => this.escape(), 8000);
        this.chaseStep();
    }

    chaseStep() {
        if ("chase" !== this.mode) {
            return;
        }
        const box = this.avatar.getBoundingClientRect();
        const dx = this.pointer.x - (box.left + box.width / 2);
        const dy = this.pointer.y - (box.top + box.height / 2);
        const dist = Math.hypot(dx, dy);
        if (dist < 38) {
            this.gotcha();

            return;
        }
        // Move toward the pointer, capped at a gentle top speed so the chase is
        // playful and evadable rather than instant.
        const step = Math.min(7, dist * 0.12) / dist;
        const here = this.element.getBoundingClientRect();
        this.moveTo(here.left + dx * step, here.top + dy * step);
        this.raf = window.requestAnimationFrame(() => this.chaseStep());
    }

    gotcha() {
        this.endGame();
        this.say(this.gotchaValue);
    }

    giveUp() {
        this.endGame();
        this.say(this.giveupValue);
    }

    // The mascot ran out of time chasing — it owns the loss, no "draw".
    escape() {
        this.endGame();
        this.say(this.escapedValue);
    }

    endGame() {
        window.cancelAnimationFrame(this.raf);
        window.clearTimeout(this.giveUpTimer);
        window.clearTimeout(this.prepTimer);
        window.clearTimeout(this.fleeCooldown);
        this.fleeCooldown = null;
        if (this.fleeHandler) {
            document.removeEventListener("pointermove", this.fleeHandler);
        }
        if (this.chaseHandler) {
            document.removeEventListener("pointermove", this.chaseHandler);
        }
        this.element.classList.remove("mascot--playing", "mascot--chasing");
        this.element.style.left = "";
        this.element.style.top = "";
        this.mode = "idle";

        // Stay quiet after a game so the closing quip can be read and nothing new
        // pops up right after: it shows for ~8s, then ~5s of calm before cheering.
        this.quiet = true;
        window.clearTimeout(this.quietTimer);
        this.quietTimer = window.setTimeout(() => {
            this.quiet = false;
        }, 13000);
    }

    anchorPosition() {
        const box = this.element.getBoundingClientRect();
        this.element.classList.add("mascot--playing");
        this.moveTo(box.left, box.top);
    }

    moveTo(left, top) {
        const margin = 8;
        const w = this.element.offsetWidth;
        const h = this.element.offsetHeight;
        this.element.style.left = `${Math.max(margin, Math.min(left, window.innerWidth - w - margin))}px`;
        this.element.style.top = `${Math.max(margin, Math.min(top, window.innerHeight - h - margin))}px`;
    }

    say(text, action = null) {
        if (!this.hasTextTarget || !text) {
            return;
        }
        this.textTarget.textContent = text;
        if (this.hasCtaTarget) {
            this.ctaTarget.hidden = "cta" !== action;
        }
        if (this.hasPlayTarget) {
            this.playTarget.hidden = "play" !== action;
        }
        if (this.hasFinishTarget) {
            this.finishTarget.hidden = "finish" !== action;
        }
        this.bubbleTarget.hidden = false;
        window.requestAnimationFrame(() => {
            this.bubbleTarget.classList.add("is-visible");
        });
        window.clearTimeout(this.hideTimer);
        this.hideTimer = window.setTimeout(() => this.hide(), 8000);
        this.lastShownAt = Date.now();
        this.saveState();
    }

    hide() {
        this.bubbleTarget.classList.remove("is-visible");
        window.clearTimeout(this.hideTimer);
        this.hideTimer = window.setTimeout(() => {
            this.bubbleTarget.hidden = true;
        }, 250);
    }

    // The × just closes the current bubble; the mascot stays and cheers again later.
    close() {
        if ("invited" === this.mode) {
            this.mode = "idle";
            window.clearTimeout(this.inviteTimer);
        }
        this.hide();
    }

    // Pick a message different from the last one shown, so it never repeats twice.
    nextMessage() {
        const messages = this.messagesValue;
        if (0 === messages.length) {
            return "";
        }
        if (1 === messages.length) {
            return messages[0];
        }

        let index = this.lastIndex;
        while (index === this.lastIndex) {
            index = Math.floor(Math.random() * messages.length);
        }
        this.lastIndex = index;

        return messages[index];
    }

    // Persist the cadence across page loads so navigating doesn't re-trigger a
    // greeting, and the last message isn't repeated on the next page.
    loadState() {
        try {
            return JSON.parse(sessionStorage.getItem("mascot-state") || "{}");
        } catch {
            return {};
        }
    }

    saveState() {
        try {
            sessionStorage.setItem(
                "mascot-state",
                JSON.stringify({
                    lastShownAt: this.lastShownAt ?? 0,
                    lastIndex: this.lastIndex,
                }),
            );
        } catch {
            // sessionStorage may be unavailable (private mode / quota); ignore.
        }
    }

    get avatar() {
        return this.element.querySelector(".mascot__avatar");
    }

    get prefersReducedMotion() {
        return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    }
}
