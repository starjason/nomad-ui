<?php

namespace Nomad;

class Nomad {

    public static function getAddress() {
        return getenv('NOMAD_BASEURL') ?: 'http://127.0.0.1:4646';
    }

    public static function getTwig() {
        $loader = new \Twig_Loader_Filesystem(__DIR__.'/../view');
        $twig = new \Twig_Environment($loader, array(
            'cache' => '/tmp',
            'debug' => (bool) getenv(NOMAD_TWIG_DEBUG),
            'strict_variables' => (bool) getenv(NOMAD_TWIG_DEBUG),
        ));;
        $twig->addFunction(new \Twig_SimpleFunction('nomadalink', ['Nomad\\Link', 'a'], ['is_safe' => ['html']]));
        $twig->addFunction(new \Twig_SimpleFunction('nomadelink', ['Nomad\\Link', 'e'], ['is_safe' => ['html']]));
        $twig->addFunction(new \Twig_SimpleFunction('nomadnlink', ['Nomad\\Link', 'n'], ['is_safe' => ['html']]));
        $twig->addFunction(new \Twig_SimpleFunction('nomadjlink', ['Nomad\\Link', 'j'], ['is_safe' => ['html']]));
        $twig->addFunction(new \Twig_SimpleFunction('nomadslink', ['Nomad\\Link', 's'], ['is_safe' => ['html']]));
        $twig->addFunction(new \Twig_SimpleFunction('dump', function($val) { return '<pre>' . print_r($val,1) . '</pre>'; }, ['is_safe' => ['html']]));
        $twig->addFilter(new \Twig_SimpleFilter('human_filesize', function($bytes, $decimals = 2) {
            if ($bytes == 0) {
                return 0;
            }
            $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
            $factor = floor((strlen($bytes) - 1) / 3);
            return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
        }));
        $twig->addFilter(new \Twig_SimpleFilter('cast_to_array', function ($stdClassObject) {
            return (array)$stdClassObject;
        }));
        return $twig;
    }

    // care of: http://www.phpied.com/simultaneuos-http-requests-in-php-with-curl/
    public static function multiRequest($data, $options = array()) {

        // array of curl handles
        $curly = array();
        // data to be returned
        $result = array();

        // multi handle
        $mh = curl_multi_init();

        // loop through $data and create curl handles
        // then add them to the multi-handle
        foreach ($data as $id => $d) {

            $curly[$id] = curl_init();

            $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
            curl_setopt($curly[$id], CURLOPT_URL,            $url);
            curl_setopt($curly[$id], CURLOPT_HEADER,         0);
            curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curly[$id], CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($curly[$id], CURLOPT_TIMEOUT,        3);

            // post?
            if (is_array($d)) {
                if (!empty($d['post'])) {
                    curl_setopt($curly[$id], CURLOPT_POST,       1);
                    curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
                }
            }

            // extra options?
            if (!empty($options)) {
                curl_setopt_array($curly[$id], $options);
            }

            curl_multi_add_handle($mh, $curly[$id]);
        }

        // execute the handles
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while($running > 0);


        // get content and remove handles
        foreach($curly as $id => $c) {
            $result[$id] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
        }

        // all done
        curl_multi_close($mh);

        return $result;
    }


}