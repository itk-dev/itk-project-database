import { Controller } from "@hotwired/stimulus";

/*
 * Guards a destructive submit behind a modal that spells out the consequences —
 * a plain confirm() can only carry text, and the point here is to link the
 * records that would be affected. Attached to the form; the trigger is a real
 * submit button, so with JavaScript off the form still posts unguarded rather
 * than the button going dead. showModal() brings Escape and focus trapping.
 */
export default class extends Controller {
    static targets = ["dialog"];

    open(event) {
        event.preventDefault();
        this.dialogTarget.showModal();
    }

    cancel() {
        this.dialogTarget.close();
    }

    // A modal dialog fills the top layer, so a click on the backdrop reports the
    // dialog itself as the target; anything inside reports a descendant.
    backdrop(event) {
        if (event.target === this.dialogTarget) {
            this.dialogTarget.close();
        }
    }
}
