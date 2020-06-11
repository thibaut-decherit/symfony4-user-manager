import {handleAjaxForm} from '../../components/helpers/AjaxFormHelper';
import '../../components/password-strength-meter';

handleAjaxForm('ajax-form-password-reset', {
    successScrollToTop: false,
    unlockOnSuccess: false
}, {
    success: {
        beforeUpdate: (jQueryOriginalForm, jQueryNewForm, response) => {
            window.location.href = response.data.url;
        }
    },
    failure: {
        beforeUpdate: (jQueryOriginalForm, jQueryNewForm, response) => {
            // IF token is expired, aborts here and redirects to password reset request view.
            if (response.data.isTokenExpired === true) {
                window.location.href = response.data.url;
            }
        }
    }
});
