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
        $this->nextEpisodeMcks = (string) env('KOLLUS_NEXT_EPISODE_MCKS', $this->nextEpisodeMcks);

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
