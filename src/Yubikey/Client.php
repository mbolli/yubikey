<?php

namespace Yubikey;

class Client
{
    /**
     * Send the request(s) via curl.
     *
     * @param array|RequestCollection $requests Set of \Yubikey\Request objects
     *
     * @return ResponseCollection instance
     */
    public function send($requests)
    {
        if ($requests::class !== RequestCollection::class) {
            $requests = new RequestCollection($requests);
        }

        return $this->request($requests);
    }

    /**
     * Make the request given the Request set and content.
     *
     * @param RequestCollection $requests Request collection
     *
     * @return ResponseCollection instance
     */
    public function request(RequestCollection $requests)
    {
        $responses = new ResponseCollection();
        $startTime = microtime(true);
        $multi = curl_multi_init();
        $curls = [];

        foreach ($requests as $index => $request) {
            $curls[$index] = curl_init();
            curl_setopt_array($curls[$index], [
                CURLOPT_URL => $request->getUrl(),
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
            ]);
            curl_multi_add_handle($multi, $curls[$index]);
        }

        do {
            while (curl_multi_exec($multi, $active) === CURLM_CALL_MULTI_PERFORM);
            while ($info = curl_multi_info_read($multi)) {
                if ($info['result'] === CURLE_OK) {
                    $return = curl_multi_getcontent($info['handle']);
                    $cinfo = curl_getinfo($info['handle']);
                    $url = parse_url($cinfo['url']);

                    $response = new Response([
                        'host' => $url['host'],
                        'mt' => (microtime(true) - $startTime),
                    ]);
                    $response->parse($return);
                    $responses->add($response);
                }
            }
        } while ($active);

        return $responses;
    }
}
