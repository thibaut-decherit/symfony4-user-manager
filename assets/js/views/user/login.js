import {body} from '../../components/helpers/jquery/selectors';

body.on('submit', '#ajax-form-login', function (e) {
    const LOGIN_FORM = $('#ajax-form-login');

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
            const LOGIN_ERROR_ALERT = $('#login-error-alert');
            const LOGIN_FLASH_ALERT = $('#login-flash-alert');
            const PASSWORD_FIELD = LOGIN_FORM.find('#password');

            /*
             Hides flash message showing if anonymous user just activated his account, reset his password or attempted
             to access protected route.
             */
            LOGIN_FLASH_ALERT.addClass('d-none');

            PASSWORD_FIELD.val('');
            PASSWORD_FIELD.removeAttr('required');
            LOGIN_ERROR_ALERT.removeClass('d-none');
            LOGIN_ERROR_ALERT.html(response.responseJSON.errorMessage);
        });
});
