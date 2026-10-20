<?php
/**
 * Kollus 연동 설정 — .env의 KOLLUS_* 환경변수를 읽어 설정 객체로 노출한다 (Services.php에서 서비스 조립 시 참조).
 */

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Kollus extends BaseConfig
{
    public string $securityKey = '';
    public string $customKey = '';
    public string $accessToken = '';
    public string $playerUrl = 'https://v.kr.kollus.com/';
    public int $tokenExpireSeconds = 300;
    /** 다음 회차 연속 재생 샘플 미디어 콘텐츠 키 — 콤마 구분 목록 (KOLLUS_NEXT_EPISODE_MCKS) */
    public string $nextEpisodeMcks = '';

    public string $multiDrmSecurityKey = '';
    public string $multiDrmCustomKey = '';

    public string $doverunnerAccessKey = '';
    public string $doverunnerSiteKey = '';
    public string $doverunnerSiteId = '';
    public string $doverunnerIv = '0123456789abcdef';
    public string $doverunnerLicenseUrl = 'https://drm-license.doverunner.com/ri/licenseManager.do';
    public string $doverunnerCertificateUrl = 'https://drm-license.doverunner.com/ri/fpsKeyManager.do';

    public function __construct()
    {
        parent::__construct();

        $this->securityKey = (string) env('KOLLUS_SECURITY_KEY', $this->securityKey);
        $this->customKey = (string) env('KOLLUS_CUSTOM_KEY', $this->customKey);
        $this->accessToken = (string) env('KOLLUS_ACCESS_TOKEN', $this->accessToken);
        $this->playerUrl = (string) env('KOLLUS_PLAYER_URL', $this->playerUrl);
        $this->tokenExpireSeconds = (int) env('KOLLUS_TOKEN_EXPIRE_SECONDS', $this->tokenExpireSeconds);
        $this->nextEpisodeMcks = (string) env('KOLLUS_NEXT_EPISODE_MCKS', $this->nextEpisodeMcks);

        $this->multiDrmSecurityKey = (string) env('KOLLUS_MULTIDRM_SECURITY_KEY', $this->multiDrmSecurityKey);
        $this->multiDrmCustomKey = (string) env('KOLLUS_MULTIDRM_CUSTOM_KEY', $this->multiDrmCustomKey);

        $this->doverunnerAccessKey = (string) env('KOLLUS_DOVERUNNER_ACCESS_KEY', $this->doverunnerAccessKey);
        $this->doverunnerSiteKey = (string) env('KOLLUS_DOVERUNNER_SITE_KEY', $this->doverunnerSiteKey);
        $this->doverunnerSiteId = (string) env('KOLLUS_DOVERUNNER_SITE_ID', $this->doverunnerSiteId);
        $this->doverunnerIv = (string) env('KOLLUS_DOVERUNNER_IV', $this->doverunnerIv);
        $this->doverunnerLicenseUrl = (string) env('KOLLUS_DOVERUNNER_LICENSE_URL', $this->doverunnerLicenseUrl);
        $this->doverunnerCertificateUrl = (string) env('KOLLUS_DOVERUNNER_CERTIFICATE_URL', $this->doverunnerCertificateUrl);
    }
}
