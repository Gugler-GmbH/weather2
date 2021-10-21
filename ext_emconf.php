<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "weather2"
 * Auto generated by Extension Builder 2016-05-04
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Weather Forecasts and Alerts',
    'description' => 'Display weather forecasts and weather alerts using various Weather APIs. Default APIs: OpenWeatherMap and Deutscher Wetterdienst',
    'category' => 'plugin',
    'author' => 'Markus Kugler, Pascal Rinker',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '3.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.17-10.4.99',
            'static_info_tables' => '6.6.0'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
