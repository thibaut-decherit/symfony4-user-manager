// Disables (submit) button, prevents submit spamming and displays loading spinner.
import {body} from './helpers/jquery/selectors';
import {translations} from './twigData/data/translations';

function handleButton(button) {
    button.attr('disabled', true);

    const buttonIcon = button.find("span[class*=' fa-']");

    const spinner = `
            <div class="spinner-border spinner-border-sm mr-2" role="status">
                <span class="sr-only">${translations.global.loading}</span>
            </div>
        `;

    // IF button has an icon already, replaces it with spinner icon. ELSE, prepends spinner icon to button content.
    if (buttonIcon.exists()) {
        buttonIcon.replaceWith(spinner);
    } else {
        // Use .append if you want the spinner to be displayed after the button label instead of before it.
        button.prepend(spinner);
    }
}

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

    handleButton(submitButton);
});

// Disables button to prevent spamming and displays loading spinner.
body.on('click', '.disable-on-click', function () {
    handleButton($(this));
});
