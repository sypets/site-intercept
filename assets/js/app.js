// Load the CSS stuff
require('@fortawesome/fontawesome-free/css/fontawesome.min.css');
require('@fortawesome/fontawesome-free/css/brands.min.css');
require('@fortawesome/fontawesome-free/css/solid.min.css');
require('../css/app.scss');

// Load the JS stuff
let $ = require('jquery');
require('bootstrap');
require('./libs/navbar.js');

// We need bootstrap collapse
var { DateTime } = require('luxon');
import hljs from 'highlightjs';

$(document).ready(function () {
    convertDates();
    hljs.initHighlightingOnLoad();
});

function convertDates() {
    Array.from(document.querySelectorAll('[data-processor="localdate"]')).forEach(function (element) {
        const value = element.dataset.value;
        element.textContent = DateTime.fromISO(value).toLocaleString({ 
            month: '2-digit',
            day: '2-digit',
            year: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
    });
}
