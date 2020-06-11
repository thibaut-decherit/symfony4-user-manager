import {handleAjaxForm} from '../../components/helpers/AjaxFormHelper';

handleAjaxForm('ajax-form-login', {
    unlockOnSuccess: false
}, {
    success: {
        beforeUpdate: (jQueryOriginalForm, jQueryNewForm, response) => {
            window.location.href = response.data.url;
        }
    }
});
