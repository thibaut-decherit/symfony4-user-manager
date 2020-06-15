import axios from 'axios';
import $ from 'jquery';
import _ from 'lodash';
import {translations} from '../twig-data/data/translations';
import {body} from './jquery/selectors';
import {scrollToFirstError, scrollToTop} from './ScrollToHelper';

/**
 * Sends the form with the given id then replaces it with the form included in the endpoint's response.
 *
 * Supports following options:
 *   - customErrorScrollToTop: bool, default true
 *   - failureScrollToFirstValidationError: bool, default true
 *   - successScrollToTop: bool, default true
 *   - systemErrorScrollToTop: bool, default true
 *   - systemErrorShowAlert: bool, default true
 *   - unlockOnFailure: bool, default true
 *   - unlockOnSuccess: bool, default true
 *
 * Supports callbacks at various steps:
 *   - before(jQueryOriginalForm): executed before sending the form (jQueryOriginalForm).
 *
 *   - success.beforeUpdate(jQueryOriginalForm, jQueryNewForm, response): executed after sending the form
 *   (jQueryOriginalForm) and receiving a success response but before updating the DOM with the new form received in the
 *   response (jQueryNewForm).
 *   It is still possible to modify jQueryNewForm before it overwrites jQueryOriginalForm in the DOM.
 *
 *   - success.afterUpdate(jQueryOriginalForm, jQueryNewForm, response): executed after sending the form
 *   (jQueryOriginalForm), receiving a success response and updating the DOM with the new form received in the response
 *   (jQueryNewForm).
 *   It is too late to modify jQueryNewForm before it overwrites jQueryOriginalForm in the DOM.
 *
 *   - failure.beforeUpdate(jQueryOriginalForm, jQueryNewForm, response): executed after sending the form
 *   (jQueryOriginalForm) and receiving a failure response but before updating the DOM with the new form received in the
 *   response (jQueryNewForm).
 *   It is still possible to modify jQueryNewForm before it overwrites jQueryOriginalForm in the DOM.
 *
 *   - failure.afterUpdate(jQueryOriginalForm, jQueryNewForm, response): executed after sending the form
 *   (jQueryOriginalForm), receiving a failure response and updating the DOM with the new form received in the response
 *   (jQueryNewForm).
 *   It is too late to modify jQueryNewForm before it overwrites jQueryOriginalForm in the DOM.
 *
 *   - after(jQueryOriginalForm, jQueryNewForm, response): executed after sending the form (jQueryOriginalForm),
 *   receiving any response and updating the DOM with the new form received in the response (jQueryNewForm).
 *   It is too late to modify jQueryNewForm before it overwrites jQueryOriginalForm in the DOM.
 *
 * @param formId
 * @param options
 * @param callbacks
 */
export function handleAjaxForm(formId, options = {}, callbacks = {}) {
    const defaultOptions = {
        customErrorScrollToTop: true,
        failureScrollToFirstValidationError: true,
        successScrollToTop: true,
        systemErrorScrollToTop: true,
        systemErrorShowAlert: true,
        unlockOnFailure: true,
        unlockOnSuccess: true
    };

    // Overwrites defaultOptions properties declared in options.
    options = {...defaultOptions, ...options};

    body.on('submit', `#${formId}`, event => {
        const jQueryOriginalForm = $(`#${formId}`);
        let response;
        let jQueryNewForm;
        let formHasBeenUpdated = false;
        let formHasValidationErrors = false;
        let formHasCustomError = false;
        let formHasSystemError = false;
        let formSuccess = false;

        // Prevents submit button default behaviour.
        event.preventDefault();

        if (_.has(callbacks, 'before')) {
            callbacks.before(jQueryOriginalForm);
        }

        axios
            .post(jQueryOriginalForm.attr('action'), new FormData(event.currentTarget))
            .then(responseObject => {
                response = responseObject;

                // Parses the JSON response to retrieve the HTML form.
                jQueryNewForm = _.has(response, 'data.template') ? $(JSON.parse(response.data.template)) : null;

                if (_.has(callbacks, 'success.beforeUpdate')) {
                    callbacks.success.beforeUpdate(jQueryOriginalForm, jQueryNewForm, response);
                }

                //  Updates form.
                if (jQueryNewForm !== null) {
                    jQueryOriginalForm.replaceWith(jQueryNewForm);

                    formHasBeenUpdated = true;
                }

                if (_.has(callbacks, 'success.afterUpdate')) {
                    callbacks.success.afterUpdate(jQueryOriginalForm, jQueryNewForm, response);
                }

                if (options.successScrollToTop) {
                    const jQueryForm = formHasBeenUpdated ? jQueryNewForm : jQueryOriginalForm;

                    scrollToTop($('#' + jQueryForm.attr('id')));
                }

                formSuccess = true;
            })
            .catch(error => {
                if (error.message === 'Request failed with status code 422') {
                    // try/catch to handle potential error thrown in handleFormError().
                    try {
                        handleFormError(error, callbacks);
                    } catch (error) {
                        handleUnknownError(error, options);
                    }
                } else if (error.message === 'Network Error') {
                    handleNetworkError(error, options);
                } else {
                    handleUnknownError(error, options);
                }
            })
            .finally(() => {
                // try to handle potential error thrown in after() callback.
                try {
                    if (_.has(callbacks, 'after')) {
                        callbacks.after(jQueryOriginalForm, jQueryNewForm, response);
                    }
                } catch (error) {
                    handleUnknownError(error, options);
                } finally {
                    /*
                    Unlocks form on success/failure if relevant option is true.
                    Always unlocks on system error.
                     */
                    if (
                        ((formHasValidationErrors || formHasCustomError) && options.unlockOnFailure)
                        || (formSuccess && options.unlockOnSuccess)
                        || formHasSystemError
                    ) {
                        const unlockButtonEvent = new CustomEvent('submit-and-button-spamming-prevention.unlock-form', {
                            detail: {
                                jQueryForm: formHasBeenUpdated ? jQueryNewForm : jQueryOriginalForm
                            }
                        });

                        document.dispatchEvent(unlockButtonEvent);
                    }
                }

                /*
                IF there is a custom or visible system error, AND relevant option is true, scrolls to the top of the
                form.
                ELSE IF there is a validation error AND relevant option is true, scrolls to first validation error.
                Custom or visible system error have priority over validation errors because the former are expected
                to be displayed as an alert (probably danger or warning) at the top of the form, thus requiring
                scrollToTop() instead of scrollToFirstError().
                 */
                if (
                    (formHasCustomError && options.customErrorScrollToTop)
                    || (formHasSystemError && options.systemErrorScrollToTop && options.systemErrorShowAlert)
                ) {
                    const jQueryForm = formHasBeenUpdated ? jQueryNewForm : jQueryOriginalForm;

                    scrollToTop($('#' + jQueryForm.attr('id')));
                } else if (formHasValidationErrors && options.failureScrollToFirstValidationError) {
                    scrollToFirstError(formHasBeenUpdated ? jQueryNewForm : jQueryOriginalForm);
                }
            });

        function handleFormError(error, callbacks) {
            response = error.response;

            // Parses the JSON response to retrieve the HTML form.
            jQueryNewForm = _.has(response, 'data.template') ? $(JSON.parse(response.data.template)) : null;

            if (_.has(callbacks, 'failure.beforeUpdate')) {
                callbacks.failure.beforeUpdate(jQueryOriginalForm, jQueryNewForm, response);
            }

            if (jQueryNewForm !== null) {
                //  Updates form (with errors and input values).
                jQueryOriginalForm.replaceWith(jQueryNewForm);

                formHasBeenUpdated = true;

                if (jQueryNewForm.find('.invalid-feedback:visible').exists()) {
                    formHasValidationErrors = true;
                }

                if (jQueryNewForm.find('.alert:visible').exists) {
                    formHasCustomError = true;
                }
            }

            if (_.has(callbacks, 'failure.afterUpdate')) {
                callbacks.failure.afterUpdate(jQueryOriginalForm, jQueryNewForm, response);
            }
        }

        function handleNetworkError(error, options) {
            formHasSystemError = true;

            if (options.systemErrorShowAlert) {
                showErrorAlert(translations.global.noInternet);
            }
        }

        function handleUnknownError(error, options) {
            formHasSystemError = true;

            if (options.systemErrorShowAlert) {
                showErrorAlert(translations.global.unknownError);
            }
        }

        function showErrorAlert(message) {
            const jQueryForm = formHasBeenUpdated ? jQueryNewForm : jQueryOriginalForm;

            jQueryForm.find('.alert').remove();

            const alert = `
            <div class="alert alert-danger rounded-0" role="alert">
                <p class="text-center m-0">${message}</p>
            </div>
            `;

            jQueryForm.prepend(alert);
        }
    });
}
