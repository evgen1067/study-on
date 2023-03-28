// start the Stimulus application
import './bootstrap';

// import @popperjs/core
import '@popperjs/core'

// import JQuery
import 'jquery/dist/jquery.min';

// import Bootstrap
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle';

import './styles/app.scss';

import $ from 'jquery';

$('.notification').on('click', function () {
    $(this).remove();
})