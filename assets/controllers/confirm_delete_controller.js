import { Controller } from "@hotwired/stimulus";

// The trigger is a plain button, so a click landing before this controller has
// hydrated does nothing; only the button inside the dialog submits.
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
