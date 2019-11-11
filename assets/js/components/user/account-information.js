import $ from 'jquery';
import {body} from '../helpers/jquery/selectors';

body.on('submit', '#ajax-form-account-information-edit', function (e) {
    const accountInformationEditForm = $(this);

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

            accountInformationEditForm.html(template);
        })
        // Triggered if response status == 400 (form has errors)
        .fail(function (response) {
            // Parses the JSON response to "unescape" the html code within
            const TEMPLATE = JSON.parse(response.responseJSON.template);
            //  Replaces html content of html element with updated form (with errors and input values)
            accountInformationEditForm.html(TEMPLATE);
        });
});
