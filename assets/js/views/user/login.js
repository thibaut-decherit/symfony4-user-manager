import $ from 'jquery';
import {body} from '../../components/helpers/jquery/selectors';

body.on('submit', '#ajax-form-login', function (e) {
    const loginForm = $(this);

    // Prevents submit button default behaviour
    e.preventDefault();

    $.ajax({
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: $(this).serialize()
    })
    // Triggered if response status == 200 (form is valid and data has been processed successfully)
        .done(function (response) {
            // Redirects to url contained in the JSON response
            window.location.href = response.url;
        })
        // Triggered if response status == 400 (form has errors)
        .fail(function (response) {
            const loginErrorAlert = $('#login-error-alert');
            const loginFlashAlert = $('#login-flash-alert');
            const passwordField = loginForm.find('#password');

            /*
             Hides flash message showing if anonymous user just activated his account, reset his password or attempted
             to access protected route.
             */
            loginFlashAlert.addClass('d-none');

            passwordField.val('');
            passwordField.removeAttr('required');
            loginErrorAlert.removeClass('d-none');
            loginErrorAlert.replaceWith(response.responseJSON.errorMessage);
        });
});
