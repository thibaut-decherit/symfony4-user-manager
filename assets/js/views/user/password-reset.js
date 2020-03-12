import $ from 'jquery';
import {body} from '../../components/helpers/jquery/selectors';

body.on('submit', '#ajax-form-password-reset', function (e) {
    const passwordResetForm = $(this);

    // Prevents submit button default behaviour
    e.preventDefault();

    $.ajax({
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: $(this).serialize()
    })
        // Triggered if response status == 200 (form is valid and data has been processed successfully)
        .done(function (response) {
            window.location.href = response.url;
        })
        // Triggered if response status == 400 (form has errors)
        .fail(function (response) {
            // IF token is expired, aborts here and redirects to password reset request view.
            if (response.responseJSON.isTokenExpired === true) {
                window.location.href = response.responseJSON.url;

                return;
            }

            // Parses the JSON response to "unescape" the html code within
            const template = JSON.parse(response.responseJSON.template);
            //  Replaces html content of html element id 'ajax-form-fos-user-registration' with updated form
            // (with errors and input values)
            passwordResetForm.replaceWith(template);
        });
});
