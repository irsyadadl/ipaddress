<?php

class IpAddress
{
    public function information($ip = null, $purpose = 'location', $deep_detect = true)
    {
        $output = null;
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $ip = $_SERVER['REMOTE_ADDR'];
            if ($deep_detect) {
                if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
                if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                }
            }
        }
        $purpose = str_replace(['name', "\n", "\t", ' ', '-', '_'], null, strtolower(trim($purpose)));
        $support = ['country', 'countrycode', 'state', 'region', 'city', 'location', 'address'];
        $continents = [
            'AF' => 'Africa',
            'AN' => 'Antarctica',
            'AS' => 'Asia',
            'EU' => 'Europe',
            'OC' => 'Australia (Oceania)',
            'NA' => 'North America',
            'SA' => 'South America',
        ];
        if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support, true)) {
            $client = new \GuzzleHttp\Client();
            $ipdat = json_decode($client->request('GET', 'http://www.geoplugin.net/json.gp', $params = [
                'query' => [
                    'ip' => $ip,
                ],
            ])->getBody()->getContents(), true);
            if (strlen(trim($ipdat['geoplugin_countryCode'])) === 2) {
                switch ($purpose) {
                    case 'location':
                        $output = [
                            'city' => $ipdat['geoplugin_city'],
                            'state' => $ipdat['geoplugin_regionName'],
                            'country' => $ipdat['geoplugin_countryName'],
                            'country_code' => $ipdat['geoplugin_countryCode'],
                            'continent' => $continents[strtoupper($ipdat['geoplugin_continentCode'])],
                            'continent_code' => $ipdat['geoplugin_continentCode'],
                        ];
                        break;
                    case 'address':
                        $address = [$ipdat['geoplugin_countryName']];
                        if (@strlen($ipdat['geoplugin_regionName']) >= 1) {
                            $address[] = $ipdat['geoplugin_regionName'];
                        }
                        if (@strlen($ipdat['geoplugin_city']) >= 1) {
                            $address[] = $ipdat['geoplugin_city'];
                        }
                        $output = implode(', ', array_reverse($address));
                        break;
                    case 'city':
                        $output = $ipdat['geoplugin_city'];
                        break;
                    case 'region':
                    case 'state':
                        $output = $ipdat['geoplugin_regionName'];
                        break;
                    case 'country':
                        $output = $ipdat['geoplugin_countryName'];
                        break;
                    case 'countrycode':
                        $output = $ipdat['geoplugin_countryCode'];
                        break;
                }
            }
        }

        return $output;
    }

    public function getCountry($ip = null)
    {
        return $this->information($ip, 'country');
    }

    public function getCity($ip = null)
    {
        return $this->information($ip, 'city');
    }

    public function getState($ip = null)
    {
        return $this->information($ip, 'state');
    }

    public function getCountryCode($ip = null)
    {
        return $this->information($ip, 'countrycode');
    }

    public function getContinent($ip = null)
    {
        return $this->information($ip, 'continent');
    }
}
