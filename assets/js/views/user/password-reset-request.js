import $ from 'jquery';
import {body} from '../../components/helpers/jquery/selectors';

body.on('submit', '#ajax-form-password-reset-request', function (e) {
    const passwordResetRequestForm = $(this);

    // Prevents submit button default behaviour
    e.preventDefault();

    $.ajax({
        type: $(this).attr('method'),
        url: $(this).attr('action'),
        data: $(this).serialize()
    })
        // Triggered if response status == 200 (form is valid and data has been processed successfully)
        .done(function (response) {
            // Parses the JSON response to "unescape" the html code within
            const template = JSON.parse(response.template);

            passwordResetRequestForm.replaceWith(template);
        })
});
