import { Controller } from "@hotwired/stimulus";

/*
 * A friendly star mascot in the bottom-right corner. It greets the user, then
 * periodically pops a speech bubble cheering them on to start or finish an
 * initiative. Rarely it invites the user to a game of catch: click it and it
 * darts away from the pointer; catch it and it turns the tables and chases the
 * pointer until it tags it back. Messages arrive already translated.
 */
export default class extends Controller {
    static targets = [
        "bubble",
        "text",
        "cta",
        "play",
        "finish",
        "finishContact",
    ];

    static values = {
        messages: { type: Array, default: [] },
        interval: { type: Number, default: 50000 },
        playChance: { type: Number, default: 0.01 },
        invite: String,
        caught: String,
        gotcha: String,
        giveup: String,
        escaped: String,
        finishTexts: { type: Array, default: [] },
        finishContactTexts: { type: Array, default: [] },
        enabled: { type: Boolean, default: true },
        farewell: String,
        welcome: String,
        intro: String,
    };

    connect() {
        this.mode = "idle";
        this.timers = {};
        this.wireToggle();
        const state = this.loadState();
        this.lastIndex = state.lastIndex ?? -1;
        this.lastShownAt = state.lastShownAt ?? 0;
        this.introduced = state.introduced ?? false;
        // Only run the cheer cadence while enabled; a disabled mascot is rendered
        // parked off-screen (the `mascot--away` class) and stays silent until the
        // user turns it back on. Resume where the previous page left off so
        // navigating around doesn't pop a fresh message on every load.
        if (this.enabledValue) {
            const sinceLast = Date.now() - this.lastShownAt;
            this.scheduleNext(Math.max(1800, this.intervalValue - sinceLast));
        }
    }

    disconnect() {
        this.endGame();
        this.clearNamedTimer("cycle");
        this.clearNamedTimer("show");
        window.clearTimeout(this.fadeTimer);
        window.clearTimeout(this.inviteTimer);
        window.clearTimeout(this.quietTimer);
        window.clearTimeout(this.toggleTimer);
        if (this.toggleForm && this.onToggleSubmit) {
            this.toggleForm.removeEventListener("submit", this.onToggleSubmit);
        }
    }

    // The disable/enable control lives in the user menu (outside this element),
    // so we reach for its <form> by id and intercept its submit. Without JS the
    // form posts normally and the server still persists the choice.
    wireToggle() {
        this.toggleForm = document.getElementById("mascotToggleForm");
        if (!this.toggleForm) {
            return;
        }
        this.toggleButton = this.toggleForm.querySelector("button");
        this.onToggleSubmit = (event) => {
            event.preventDefault();
            this.persistToggle();
        };
        this.toggleForm.addEventListener("submit", this.onToggleSubmit);
    }

    persistToggle() {
        const next = !this.enabledValue;
        const token = this.toggleForm.querySelector(
            'input[name="_token"]',
        ).value;
        fetch(this.toggleForm.action, {
            method: "POST",
            headers: {
                "X-Requested-With": "fetch",
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({ _token: token }),
        })
            .then((response) => {
                if (response.ok) {
                    this.applyEnabled(next);
                }
            })
            .catch(() => {});
    }

    // Drive the leaving/returning choreography once the server has stored it.
    applyEnabled(enabled) {
        this.enabledValue = enabled;
        if (this.toggleButton) {
            this.toggleButton.setAttribute(
                "aria-checked",
                enabled ? "true" : "false",
            );
        }
        window.clearTimeout(this.toggleTimer);

        if (enabled) {
            // Slide back in, then say how happy it is to be back.
            this.element.classList.remove("mascot--away");
            this.mode = "idle";
            this.toggleTimer = window.setTimeout(() => {
                this.say(this.welcomeValue, "cta");
                this.scheduleNext(this.intervalValue);
            }, 520);

            return;
        }

        // Wave goodbye, let it linger long enough to read, then slide off-screen.
        this.clearNamedTimer("cycle");
        this.say(this.farewellValue);
        this.toggleTimer = window.setTimeout(() => {
            this.hide();
            this.element.classList.add("mascot--away");
        }, 2600);
    }

    scheduleNext(delay) {
        this.startTimer("cycle", () => this.tick(), delay);
    }

    // Pausable timers: the cycle to the next message and the bubble's auto-hide.
    // Hovering the mascot freezes whatever time is left; leaving resumes it, so a
    // user reading or admiring the bubble is never rushed or interrupted.
    startTimer(name, callback, delay) {
        this.clearNamedTimer(name);
        const timer = { callback, remaining: delay, startedAt: Date.now() };
        timer.id = window.setTimeout(() => {
            delete this.timers[name];
            callback();
        }, delay);
        this.timers[name] = timer;
    }

    clearNamedTimer(name) {
        const timer = this.timers[name];
        if (timer) {
            window.clearTimeout(timer.id);
            delete this.timers[name];
        }
    }

    pause() {
        const now = Date.now();
        for (const timer of Object.values(this.timers)) {
            if (null === timer.id) {
                continue;
            }
            window.clearTimeout(timer.id);
            timer.id = null;
            timer.remaining = Math.max(
                0,
                timer.remaining - (now - timer.startedAt),
            );
        }
    }

    resume() {
        for (const [name, timer] of Object.entries(this.timers)) {
            if (null !== timer.id) {
                continue;
            }
            timer.startedAt = Date.now();
            timer.id = window.setTimeout(() => {
                delete this.timers[name];
                timer.callback();
            }, timer.remaining);
        }
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

    // Clicking the avatar plays catch mid-game; an idle click pops a fresh
    // message and resets the cadence so the next auto-message isn't right behind.
    poke() {
        if ("invited" === this.mode) {
            this.startFlee();
        } else if ("flee" === this.mode) {
            this.caught();
        } else if ("idle" === this.mode) {
            this.speak();
            this.scheduleNext(this.intervalValue);
        }
    }

    speak() {
        // The first time it speaks in a session, Glimt introduces itself by name.
        if (!this.introduced && this.introValue) {
            this.introduced = true;
            this.say(this.introValue, "cta");

            return;
        }

        // A contact created on the fly (name only) gets a gentle reminder to
        // finish it, linking straight to its edit page.
        const contactTexts = this.finishContactTextsValue;
        if (
            this.hasFinishContactTarget &&
            contactTexts.length > 0 &&
            Math.random() < 0.15
        ) {
            this.say(
                contactTexts[Math.floor(Math.random() * contactTexts.length)],
                "finishContact",
            );

            return;
        }

        // Now and then, nudge the user to finish their least-complete initiative,
        // picking one of the finish lines at random for variety.
        const finishTexts = this.finishTextsValue;
        if (
            this.hasFinishTarget &&
            finishTexts.length > 0 &&
            Math.random() < 0.15
        ) {
            this.say(
                finishTexts[Math.floor(Math.random() * finishTexts.length)],
                "finish",
            );

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
        }, 15000);
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
        if (this.hasFinishContactTarget) {
            this.finishContactTarget.hidden = "finishContact" !== action;
        }
        this.bubbleTarget.hidden = false;
        window.requestAnimationFrame(() => {
            this.bubbleTarget.classList.add("is-visible");
        });
        this.startTimer("show", () => this.hide(), 20000);
        this.lastShownAt = Date.now();
        this.saveState();
    }

    hide() {
        this.bubbleTarget.classList.remove("is-visible");
        this.clearNamedTimer("show");
        window.clearTimeout(this.fadeTimer);
        this.fadeTimer = window.setTimeout(() => {
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
                    introduced: this.introduced,
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
