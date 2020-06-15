// Disables (submit) button, prevents submit spamming and displays loading spinner.
import {body} from './helpers/jquery/selectors';
import {translations} from './twig-data/data/translations';

body.on('submit', '.disable-on-submit', function () {
    /*
    Listens to submit event on form instead of listening to click event on submit button to avoid button disabling
    if clicked while form has validation errors thrown by the browser (e.g. required="required").
    */
    const submitButton = $(this).find('[type="submit"]');

    // Prevents submit if submit button is already disabled. Required to prevent spamming through enter key press.
    if (submitButton.attr('disabled')) {
        return false;
    }

    lockButton(submitButton);
});

// Disables button to prevent spamming and displays loading spinner.
body.on('click', '.disable-on-click', function () {
    lockButton($(this));
});

document.addEventListener('submit-and-button-spamming-prevention.unlock-form', event => {
    if (event.detail.jQueryForm === null) {
        return;
    }

    const submitButton = event.detail.jQueryForm.find('[type="submit"]');
    if (submitButton.attr('disabled')) {
        unlockButton(submitButton);
    }
}, false);

document.addEventListener('submit-and-button-spamming-prevention.unlock-button', event => {
    const jQueryButton = event.detail.jQueryButton;
    if (jQueryButton.attr('disabled')) {
        unlockButton(jQueryButton);
    }
}, false);

function lockButton(jQueryButton) {
    const buttonIcon = jQueryButton.find("span[class*=' fa-']");

    const spinnerIcon = `
            <div class="spinner-border spinner-border-sm mr-2" role="status">
                <span class="sr-only">${translations.global.loading}</span>
            </div>
        `;

    jQueryButton.attr('disabled', true);

    // IF button has an icon already, hides it first.
    if (buttonIcon.exists()) {
        // Button icon is hidden, thus allowing to "restore" it later if necessary. (e.g. with unlockButton()).
        buttonIcon.hide();
    }

    /*
    Then prepends spinner icon to button content.
    Use .append if you want the spinner to be displayed after the button label instead of before it.
     */
    jQueryButton.prepend(spinnerIcon);
}

function unlockButton(jQueryButton) {
    jQueryButton.removeAttr('disabled');

    const spinnerIcon = jQueryButton.find("div[class*='spinner-']");
    const buttonIcon = jQueryButton.find("span[class*=' fa-']");

    if (spinnerIcon.exists()) {
        spinnerIcon.remove();
    }

    if (buttonIcon.exists()) {
        buttonIcon.show();
    }
}
