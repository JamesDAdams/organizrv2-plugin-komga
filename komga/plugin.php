<?php
$GLOBALS['plugins']['Komga'] = array(
    'name' => 'Komga',
    'author' => 'JamesAdams',
    'category' => 'Entertainment',
    'link' => '',
    'license' => 'personal',
    'idPrefix' => 'KOMGA',
    'configPrefix' => 'KOMGA',
    'version' => '1.0.4',
    'image' => 'https://komga.org/fr/img/logo.svg',
    'settings' => true,
    'bind' => true,
    'api' => 'api/v2/plugins/komga/settings',
    'homepage' => true
);

class KomgaPlugin extends Organizr
{
    public function __construct()
    {
        parent::__construct();
    }

    public function _pluginGetSettings()
    {
        $libraries = ['all' => 'All Libraries'];

        $url = $this->config['KOMGA-url'] ?? '';
        $apiKey = $this->config['KOMGA-apikey'] ?? '';

        if ($url && $apiKey) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($url, '/') . '/api/v1/libraries');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-API-Key: $apiKey",
                "Accept: application/json"
            ]);
            // 2 second timeout so settings load doesn't hang forever
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $data = json_decode($response, true);
                if (is_array($data) && !isset($data['status'])) {
                    // Komga might wrap in 'content' array
                    $items = isset($data['content']) ? $data['content'] : $data;
                    foreach ($items as $lib) {
                        if (isset($lib['id']) && isset($lib['name'])) {
                            $libraries[$lib['id']] = $lib['name'];
                        }
                    }
                }
            }
        }

        return array(
            'Komga Settings' => array(
                    array(
                    'type' => 'select',
                    'name' => 'KOMGA-minAuth',
                    'label' => 'Minimum authentication to view component',
                    'value' => (string)($this->config['KOMGA-minAuth'] ?? '1'),
                    'options' => $this->groupSelect()
                ),
                    array(
                    'type' => 'input',
                    'name' => 'KOMGA-url',
                    'label' => 'Komga URL',
                    'placeholder' => 'ex: https://komga.domain.com',
                    'value' => (string)($this->config['KOMGA-url'] ?? '')
                ),
                    array(
                    'type' => 'password-alt',
                    'name' => 'KOMGA-apikey',
                    'label' => 'Komga API Key',
                    'value' => (string)($this->config['KOMGA-apikey'] ?? '')
                ),
                    array(
                    'type' => 'input',
                    'name' => 'KOMGA-title',
                    'label' => 'Homepage component title',
                    'value' => (string)($this->config['KOMGA-title'] ?? 'Livres ajoutés récemment')
                ),
                    array(
                    'type' => 'select',
                    'name' => 'KOMGA-libraries',
                    'label' => 'Specific Library',
                    'value' => (string)($this->config['KOMGA-libraries'] ?? 'all'),
                    'options' => $libraries
                )
            )
        );
    }
}