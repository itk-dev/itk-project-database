import { Controller } from "@hotwired/stimulus";

/*
 * The one way this app asks "are you sure?" — a modal that can spell out what a
 * deletion takes with it, which confirm() cannot.
 *
 * The trigger is a plain button rather than a submit, so a click that lands
 * before this controller has hydrated does nothing instead of deleting
 * unguarded. Only the button inside the dialog submits. showModal() brings
 * Escape and focus trapping with it.
 */
export default class extends Controller {
    static targets = ["dialog"];

    open() {
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
