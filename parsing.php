<?php

/**
 * Initiate program, calling error gestion on filename and redirect to parse
 */
function initiate($file)
{
    errorGestion($file);
    $htmlDoc = file_get_contents($file);
    $regexs = regexDeclare(false);
    $endMatchs = regexMatch($regexs, $htmlDoc);
    $regexs = regexDeclare();
    $matchs = regexMatch($regexs, $htmlDoc);
    writeJson($matchs, $file, $endMatchs);
}

function regexDeclare($details = true)
{
    $regexsDetails = [
        'way' => '/<\s*td[^>]*class="travel-way"[^>]*>(.*?)<\s*\/\s*td>/',
        'date' => '/<\s*td[^>]*class="product-travel-date"[^>]*>(.*?)<\s*\/\s*td>/',
        'departureTime' => '/<\s*td[^>]*class="origin-destination-border origin-destination-hour segment-arrival"[^>]*>(.*?)<\s*\/\s*td>/',
        'departureStation' => '/<\s*td[^>]*class="origin-destination-station segment-departure"[^>]*>(.*?)<\s*\/\s*td>/',
        'arrivalTime' => '/<\s*td[^>]*class="origin-destination-border origin-destination-hour segment-arrival"[^>]*>(.*?)<\s*\/\s*td>/',
        'arrivalStation' => '/<\s*td[^>]*class="origin-destination-border origin-destination-station segment-arrival"[^>]*>(.*?)<\s*\/\s*td>/',
        'type' => '/<\s*td[^>]*class="origin-destination-station segment-departure"[^>]*>.*?<\s*\/\s*td>[^>]*<\s*td[^>]*class="segment"[^\>]*>(.*?)<\s*\/\s*td>/',
        'number' => '/<\s*td[^>]*class="origin-destination-station segment-departure"[^>]*>.*?<\s*\/\s*td>[^>]*<\s*td[^>]*class="segment"[^\>]*>.*?<\s*\/\s*td>[^>]*<\s*td[^>]*class="segment"[^\>]*>(.*?)<\s*\/\s*td>/',
        'passengersAge' => '/<\s*td[^>]*class="typology"[^>]*>.*\((.*?)\).*<\s*\/\s*td>/',
    ];
    $regexsEnd = [
        'code' => '/<\s*td[^>]*class="pnr-ref"[^>]*>[^>]*<\s*span[^>]*class="pnr-info"[^\>]*>(.*?)<\s*\/\s*span>/',
        'name' => '/<\s*td[^>]*class="pnr-name"[^>]*>[^>]*<\s*span[^>]*class="pnr-info"[^\>]*>(.*?)<\s*\/\s*span>/',
        'price' => '/<\s*td[^>]*class="very-important"[^>]*>(.*?)<\s*\/\s*td>/'
    ];
    $regexs = $details === true ? $regexsDetails : $regexsEnd;
    return $regexs;
}

function regexMatch($regexs, $htmlDoc)
{
    $matchs = [];
    $allMatchs = [];
    foreach($regexs as $key => $value) {
        preg_match_all($value, $htmlDoc, $matchs, PREG_PATTERN_ORDER);
        sleep(0.5);
        $allMatchs[$key] = $matchs[1];
    }
    return $allMatchs;
}

function writeJson($matchs, $file, $endMatchs)
{
    $trips = [];
    for($index = 0; $index < count($matchs['way']); $index++)
    {
        foreach($matchs as $key => $value) {
            if($key == 'way' || $key == 'date') {
                $trips[$index][$key == 'way' ? 'type' : 'date'] = $value[$index];
            }
            else if($key !== 'passengersAge') {
                $trips[$index]['trains'][$key] = $value[$index];
            }
        }
    }
    $jsonArray = [
        'status' => 'ok',
        'result' => [
            'trips' => [
                'code' => array_reverse($endMatchs['code'], false)[0],
                'name' => array_reverse($endMatchs['name'], false)[0],
                'details' => [
                    'price' => $endMatchs['price'][0],
                    'roundTrips' => $trips
                ]
            ]
        ]
    ];
    $handle = fopen(str_replace('html', 'json', $file), 'w');
    fwrite($handle, json_encode($jsonArray));
}

/**
 * Error gestion on file
 */
function errorGestion($file)
{
    switch($file) {
        case file_exists($file) === false :
            exit('HTML file not found');
            break;
        case in_array(
                    pathinfo($file, PATHINFO_EXTENSION),
                    ['htm', 'html']
                ) === false :
            exit('File isn\'t HTML');
            break;
    }
}

initiate($argv[1]);