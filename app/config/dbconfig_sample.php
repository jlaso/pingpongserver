<?php

    define('DBHOST', 'localhost');
    define('DBNAME', 'my-simple-web');
    define('DBUSER', 'root');
    define('DBPASS', 'root');

    $languages = array(
        'en'    => _('English'),
        'es'    => _('Espa&ntilde;ol'),
    );

    define('LANGUAGES', serialize($languages));


    define('ACS_APP_KEY', 'here-your-acs-app-key');
    define('ACS_USER', 'here-your-admin-user');
    define('ACS_PASSWORD', 'here-your-pass');