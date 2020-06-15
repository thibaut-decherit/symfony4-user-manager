import $ from 'jquery';

// In px.
const spacingTop = 5;

export function scrollToTop(element) {
    $([document.documentElement, document.body]).animate({
        scrollTop: element.offset().top - spacingTop
    }, 500)
}

/*
Scrolls to first error of given form.
IF a form is specified but no first error is found, scrolls to the top of the form instead.
IF no form is specified, scrolls to the first error on the page if there is one.
 */
export function scrollToFirstError(jQueryForm = null, fallbackScrollToTop = false) {
    let prop = {};
    if (jQueryForm) {
        const offset = jQueryForm.find('.invalid-feedback:visible').parent().first().offset();

        if (offset) {
            prop = {
                scrollTop: offset.top - spacingTop
            }
        } else {
            scrollToTop(jQueryForm);
        }
    } else {
        const offset = $('.invalid-feedback:visible').parent().first().offset();

        if (offset) {
            prop = {
                scrollTop: offset.top - spacingTop
            }
        }
    }

    $([document.documentElement, document.body]).animate(prop, 500)
}
