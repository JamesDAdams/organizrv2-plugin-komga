<?php
$app->get('/plugins/komga/books/latest', function ($request, $response, $args) {
    $komgaPlugin = new KomgaPlugin();
    $GLOBALS['api']['response']['data'] = [];

    if ($komgaPlugin->checkRoute($request)) {
        if ($komgaPlugin->qualifyRequest($komgaPlugin->config['KOMGA-minAuth'], true)) {
            $url = $komgaPlugin->config['KOMGA-url'] ?? '';
            $apiKey = $komgaPlugin->config['KOMGA-apikey'] ?? '';
            $libraries = $komgaPlugin->config['KOMGA-libraries'] ?? 'all';

            if ($url && $apiKey) {
                $query = '';
                if ($libraries && $libraries !== 'all') {
                    $query = '?library_id=' . $libraries;
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, rtrim($url, '/') . '/api/v1/books/latest' . $query);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "X-API-Key: $apiKey",
                    "Accept: application/json"
                ]);
                $res = curl_exec($ch);
                curl_close($ch);

                if ($res) {
                    $data = json_decode($res, true);
                    // Provide settings to JS via our API response
                    $GLOBALS['api']['response']['data'] = [
                        'title' => $komgaPlugin->config['KOMGA-title'] ?? 'Livres ajoutés récemment',
                        'baseUrl' => rtrim($url, '/') . '/book/',
                        'books' => (isset($data['content']) ? $data['content'] : $data)
                    ];
                }
            }
        }
    }

    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

$app->get('/plugins/komga/image', function ($request, $response, $args) {
    $komgaPlugin = new KomgaPlugin();

    if ($komgaPlugin->checkRoute($request)) {
        if ($komgaPlugin->qualifyRequest($komgaPlugin->config['KOMGA-minAuth'], true)) {
            $apiUrl = $komgaPlugin->config['KOMGA-url'] ?? '';
            $apiKey = $komgaPlugin->config['KOMGA-apikey'] ?? '';
            
            // Allow full URL (if passed via query string) or just append to Komga API URL
            $urlParams = $request->getQueryParams();
            $thumbnailUrl = $urlParams['url'] ?? '';

            if ($apiUrl && $apiKey && $thumbnailUrl) {
                // Determine if thumbnailUrl is absolute or relative
                if (!preg_match('~^(?:f|ht)tps?://~i', $thumbnailUrl)) {
                    $thumbnailUrl = rtrim($apiUrl, '/') . '/' . ltrim($thumbnailUrl, '/');
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $thumbnailUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "X-API-Key: $apiKey"
                ]);
                $res = curl_exec($ch);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);

                if ($res) {
                    return $response
                        ->withHeader('Content-Type', $contentType ?: 'image/jpeg')
                        ->write($res);
                }
            }
        }
    }

    return $response->withStatus(404);
});
