<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Kollus extends BaseConfig
{
    public string $securityKey = '';
    public string $customKey = '';
    public string $accessToken = '';
    public string $playerUrl = 'https://v.kr.kollus.com/';
    public int $tokenExpireSeconds = 300;
    public string $nextEpisodeMck = '';

    public string $multiDrmSecurityKey = '';
    public string $multiDrmCustomKey = '';

    public string $inkaAccessKey = '';
    public string $inkaSiteKey = '';
    public string $inkaSiteId = '';
    public string $inkaIv = '0123456789abcdef';
    public string $inkaLicenseUrl = 'https://license.pallycon.com/ri/licenseManager.do';
    public string $inkaCertificateUrl = 'https://license.pallycon.com/ri/fpsKeyManager.do';

    public function __construct()
    {
        parent::__construct();

        $this->securityKey = (string) env('KOLLUS_SECURITY_KEY', $this->securityKey);
        $this->customKey = (string) env('KOLLUS_CUSTOM_KEY', $this->customKey);
        $this->accessToken = (string) env('KOLLUS_ACCESS_TOKEN', $this->accessToken);
        $this->playerUrl = (string) env('KOLLUS_PLAYER_URL', $this->playerUrl);
        $this->tokenExpireSeconds = (int) env('KOLLUS_TOKEN_EXPIRE_SECONDS', $this->tokenExpireSeconds);
        $this->nextEpisodeMck = (string) env('KOLLUS_NEXT_EPISODE_MCK', $this->nextEpisodeMck);

        $this->multiDrmSecurityKey = (string) env('KOLLUS_MULTIDRM_SECURITY_KEY', $this->multiDrmSecurityKey);
        $this->multiDrmCustomKey = (string) env('KOLLUS_MULTIDRM_CUSTOM_KEY', $this->multiDrmCustomKey);

        $this->inkaAccessKey = (string) env('KOLLUS_INKA_ACCESS_KEY', $this->inkaAccessKey);
        $this->inkaSiteKey = (string) env('KOLLUS_INKA_SITE_KEY', $this->inkaSiteKey);
        $this->inkaSiteId = (string) env('KOLLUS_INKA_SITE_ID', $this->inkaSiteId);
        $this->inkaIv = (string) env('KOLLUS_INKA_IV', $this->inkaIv);
        $this->inkaLicenseUrl = (string) env('KOLLUS_INKA_LICENSE_URL', $this->inkaLicenseUrl);
        $this->inkaCertificateUrl = (string) env('KOLLUS_INKA_CERTIFICATE_URL', $this->inkaCertificateUrl);
    }
}
