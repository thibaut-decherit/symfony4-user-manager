import $ from 'jquery';

/*
 Returns true if given element exists, false otherwise.
 Example: $("#test").exists() will return false if there is no element with id="test" in DOM.
 */
$.fn.exists = function () {
    return this.length !== 0;
};
