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
            // Parses the JSON response to "unescape" the html code within
            const template = JSON.parse(response.responseJSON.template);
            //  Replaces html content of html element id 'ajax-form-fos-user-registration' with updated form
            // (with errors and input values)
            loginForm.replaceWith(template);
        });
});
