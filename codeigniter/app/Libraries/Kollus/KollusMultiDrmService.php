<?php
/**
 * 멀티 DRM(Widevine/PlayReady/FairPlay, inkaDRM/Pallycon) 재생용 플레이어 URL을 조립하는 서비스.
 * User-Agent로 DRM 종류를 판별하고 Pallycon 커스텀 헤더(라이선스 토큰·해시)를 생성한다.
 */

namespace App\Libraries\Kollus;

use JsonException;
use RuntimeException;

class KollusMultiDrmService
{
    /**
     * inkaDRM 라이선스 룰 (고정값).
     * 각 항목의 숫자 값(security_level 150, protection_level 100 등)은 Pallycon(DoveRunner) 라이선스 토큰 규격의 정책 코드다.
     */
    private const LICENSE_RULE = [
        'policy_version'  => 2,
        'playback_policy' => [
            'persistent' => false,
        ],
        'security_policy' => [
            [
                'widevine' => [
                    'security_level'             => 1,
                    'disable_analog_output'      => false,
                    'hdcp_srm_rule'              => 'HDCP_SRM_RULE_NONE',
                    'override_device_revocation' => true,
                ],
                'playready' => [
                    'security_level'                 => 150,
                    'digital_video_protection_level' => 100,
                    'analog_video_protection_level'  => 100,
                    'digital_audio_protection_level' => 100,
                    'require_hdcp_type_1'            => false,
                ],
                'fairplay' => [
                    'hdcp_enforcement' => -1,
                    'allow_airplay'    => true,
                    'allow_av_adapter' => true,
                ],
            ],
        ],
    ];

    /** User-Agent에 포함된 브라우저 식별자 → DRM 종류/스트리밍 방식 매핑 (검사 순서 중요) */
    private const BROWSER_DRM_MAP = [
        'CriOS'   => ['drm_type' => 'FairPlay', 'streaming_type' => 'hls'],
        'Edge'    => ['drm_type' => 'PlayReady', 'streaming_type' => 'dash'],
        'Edg'     => ['drm_type' => 'PlayReady', 'streaming_type' => 'dash'],
        'Firefox' => ['drm_type' => 'Widevine', 'streaming_type' => 'dash'],
        'Chrome'  => ['drm_type' => 'Widevine', 'streaming_type' => 'dash'],
        'Safari'  => ['drm_type' => 'FairPlay', 'streaming_type' => 'hls'],
        'Opera'   => ['drm_type' => 'PlayReady', 'streaming_type' => 'dash'],
        'MSIE'    => ['drm_type' => 'PlayReady', 'streaming_type' => 'dash'],
        'Trident' => ['drm_type' => 'PlayReady', 'streaming_type' => 'dash'],
    ];

    /** 라이선스 룰 암호화 알고리즘 (Pallycon 규격) */
    private const CIPHER = 'AES-256-CBC';

    private readonly KollusJwtService $_jwtService;

    private readonly string $_inkaAccessKey;

    private readonly string $_inkaSiteKey;

    private readonly string $_inkaSiteId;

    private readonly string $_inkaIv;

    private readonly string $_licenseUrl;

    private readonly string $_certificateUrl;

    /**
     * @param KollusJwtService $jwtService     멀티 DRM 전용 계정으로 서명하는 JWT 서비스 (kollusMultiDrmJwtService)
     * @param string           $inkaAccessKey  Pallycon Access Key (KOLLUS_INKA_ACCESS_KEY)
     * @param string           $inkaSiteKey    Pallycon Site Key — 라이선스 룰 암호화 키 (KOLLUS_INKA_SITE_KEY)
     * @param string           $inkaSiteId     Pallycon Site ID (KOLLUS_INKA_SITE_ID)
     * @param string           $inkaIv         라이선스 룰 AES-256-CBC 암호화 IV (KOLLUS_INKA_IV)
     * @param string           $licenseUrl     Pallycon 라이선스 서버 URL (KOLLUS_INKA_LICENSE_URL)
     * @param string           $certificateUrl Pallycon FairPlay 인증서 서버 URL (KOLLUS_INKA_CERTIFICATE_URL)
     */
    public function __construct(
        KollusJwtService $jwtService,
        string $inkaAccessKey,
        string $inkaSiteKey,
        string $inkaSiteId,
        string $inkaIv,
        string $licenseUrl,
        string $certificateUrl,
    ) {
        $this->_jwtService     = $jwtService;
        $this->_inkaAccessKey  = $inkaAccessKey;
        $this->_inkaSiteKey    = $inkaSiteKey;
        $this->_inkaSiteId     = $inkaSiteId;
        $this->_inkaIv         = $inkaIv;
        $this->_licenseUrl     = $licenseUrl;
        $this->_certificateUrl = $certificateUrl;
    }

    /**
     * 멀티 DRM(Widevine/PlayReady/FairPlay) 재생용 플레이어 URL을 생성한다.
     * User-Agent를 분석해 브라우저에 맞는 DRM 종류와 스트리밍 방식을 자동으로 선택한다.
     *
     * @param string $clientUserId    서비스 측 사용자 식별자 (JWT cuid, inkaDRM user_id)
     * @param string $mediaContentKey Kollus 미디어 콘텐츠 키
     * @param string $contentId       멀티 DRM 콘텐츠 ID (Kollus 업로드 파일 키)
     * @param string $userAgent       요청 User-Agent (DRM/스트리밍 방식 판별용)
     * @return string                 iframe src에 사용할 플레이어 URL
     *
     * @throws RuntimeException JWT 생성 또는 inkaDRM 라이선스 토큰 암호화 실패 시
     */
    public function buildPlayerUrl(string $clientUserId, string $mediaContentKey, string $contentId, string $userAgent): string
    {
        $streamingInfo = $this->_resolveStreamingInfo($userAgent);

        // Kollus JWT의 mc 항목에 inkaDRM drm_policy를 포함시켜 플레이어가 라이선스 서버와 직접 통신하도록 구성
        $mediaContentEntry = [
            'mckey'      => $mediaContentKey,
            'drm_policy' => [
                'kind'           => 'inka',
                'streaming_type' => $streamingInfo['streaming_type'],
                'data'           => [
                    'license_url'     => $this->_licenseUrl,
                    'certificate_url' => $this->_certificateUrl . '?siteId=' . $this->_inkaSiteId,
                    'custom_header'   => [
                        'key'   => 'pallycon-customdata-v2',
                        'value' => $this->_buildCustomHeaderPayload($clientUserId, $contentId, $streamingInfo['drm_type']),
                    ],
                ],
            ],
        ];

        return $this->_jwtService->buildMultiDrmPlayerUrl(
            $clientUserId,
            [$mediaContentEntry],
        );
    }

    /**
     * User-Agent 문자열로 브라우저별 DRM 종류와 스트리밍 방식을 판별한다.
     * 매칭되는 브라우저가 없으면 Widevine/dash를 기본값으로 사용한다.
     *
     * @param string $userAgent 요청 User-Agent
     * @return array{drm_type: string, streaming_type: string}
     */
    private function _resolveStreamingInfo(string $userAgent): array
    {
        $matched = self::BROWSER_DRM_MAP['Chrome'];

        foreach (self::BROWSER_DRM_MAP as $browser => $info) {
            if (str_contains($userAgent, $browser)) {
                $matched = $info;
                break;
            }
        }

        // Mac용 Edge는 Chromium 엔진을 사용하므로 PlayReady가 아닌 Widevine으로 재분류
        if (str_contains($userAgent, 'Macintosh') && str_contains($userAgent, 'Edg')) {
            $matched = self::BROWSER_DRM_MAP['Chrome'];
        }

        return $matched;
    }

    /**
     * pallycon-customdata-v2 헤더에 담을 inkaDRM 커스텀 페이로드를 생성한다.
     *
     * @param string $clientUserId 서비스 측 사용자 식별자
     * @param string $contentId    멀티 DRM 콘텐츠 ID
     * @param string $drmType      Widevine / PlayReady / FairPlay
     * @return string              base64 인코딩된 페이로드
     *
     * @throws RuntimeException 페이로드 직렬화 또는 라이선스 토큰 생성 실패 시
     */
    private function _buildCustomHeaderPayload(string $clientUserId, string $contentId, string $drmType): string
    {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $token     = $this->_buildEncryptedLicenseToken();

        // Pallycon 규격: accessKey + drmType + siteId + userId + cid + token + timestamp 를 이어붙여 SHA-256 해시
        $hash = base64_encode(hash(
            'sha256',
            $this->_inkaAccessKey . $drmType . $this->_inkaSiteId . $clientUserId . $contentId . $token . $timestamp,
            true,
        ));

        try {
            $payload = json_encode([
                'drm_type'  => $drmType,
                'site_id'   => $this->_inkaSiteId,
                'user_id'   => $clientUserId,
                'cid'       => $contentId,
                'token'     => $token,
                'timestamp' => $timestamp,
                'hash'      => $hash,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // 페이로드 값이 모두 문자열이라 실패 가능성은 낮지만(잘못된 UTF-8 등), 호출자가 RuntimeException 하나로 처리할 수 있도록 래핑
            throw new RuntimeException('inkaDRM 페이로드 직렬화에 실패했습니다: ' . $e->getMessage(), previous: $e);
        }

        return base64_encode($payload);
    }

    /**
     * 라이선스 룰을 AES-256-CBC로 암호화해 inkaDRM 토큰을 생성한다.
     *
     * @return string base64 인코딩된 암호화 토큰
     *
     * @throws RuntimeException 라이선스 룰 직렬화 또는 암호화 실패 시
     */
    private function _buildEncryptedLicenseToken(): string
    {
        try {
            $rule = json_encode(self::LICENSE_RULE, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // LICENSE_RULE은 고정 상수라 직렬화 실패는 곧 코드 오류 — 설정 문제임을 알 수 있도록 RuntimeException으로 래핑
            throw new RuntimeException('inkaDRM 라이선스 룰 직렬화에 실패했습니다: ' . $e->getMessage(), previous: $e);
        }

        $encrypted = openssl_encrypt($rule, self::CIPHER, $this->_inkaSiteKey, OPENSSL_RAW_DATA, $this->_inkaIv);

        if ($encrypted === false) {
            throw new RuntimeException('inkaDRM 라이선스 룰 암호화에 실패했습니다.');
        }

        return base64_encode($encrypted);
    }
}
