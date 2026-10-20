<?php
/**
 * 멀티 DRM(DoveRunner) 재생용 플레이어 URL 조립 서비스.
 * User-Agent로 DRM 종류/스트리밍 방식을 판별하고 라이선스 룰을 암호화해 drm_policy를 구성한다.
 */

namespace App\Services;

use JsonException;
use RuntimeException;

class KollusMultiDrmService
{
    /**
     * DoveRunner 라이선스 룰 (고정값).
     * 숫자 값(PlayReady security_level 150, *_protection_level 100 등)은 DoveRunner 라이선스 룰 규격이 정의한 레벨 값이므로
     * 별도 상수화 없이 규격 그대로 둔다.
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
                    'security_level'                  => 150,
                    'digital_video_protection_level'  => 100,
                    'analog_video_protection_level'   => 100,
                    'digital_audio_protection_level'  => 100,
                    'require_hdcp_type_1'             => false,
                ],
                'fairplay' => [
                    'hdcp_enforcement'  => -1,
                    'allow_airplay'     => true,
                    'allow_av_adapter'  => true,
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

    /** 라이선스 룰 암호화 알고리즘 */
    private const CIPHER = 'AES-256-CBC';

    /** 멀티 DRM 전용 Kollus 계정 키로 초기화된 JWT 서비스 */
    private readonly KollusJwtService $_jwtService;

    /** DoveRunner Access Key — 커스텀 헤더 해시 계산에 사용 */
    private readonly string $_doverunnerAccessKey;

    /** DoveRunner Site Key — 라이선스 룰 AES 암호화 키 */
    private readonly string $_doverunnerSiteKey;

    /** DoveRunner Site ID */
    private readonly string $_doverunnerSiteId;

    /** 라이선스 룰 AES-256-CBC 암호화 IV */
    private readonly string $_doverunnerIv;

    /** DoveRunner 라이선스 서버 URL */
    private readonly string $_licenseUrl;

    /** DoveRunner FairPlay 인증서 서버 URL */
    private readonly string $_certificateUrl;

    /**
     * @param KollusJwtService $jwtService          멀티 DRM 전용 Kollus 계정 키로 생성된 JWT 서비스
     * @param string           $doverunnerAccessKey DoveRunner Access Key
     * @param string           $doverunnerSiteKey   DoveRunner Site Key
     * @param string           $doverunnerSiteId    DoveRunner Site ID
     * @param string           $doverunnerIv        라이선스 룰 AES-256-CBC 암호화 IV
     * @param string           $licenseUrl          DoveRunner 라이선스 서버 URL
     * @param string           $certificateUrl      DoveRunner FairPlay 인증서 서버 URL
     */
    public function __construct(
        KollusJwtService $jwtService,
        string $doverunnerAccessKey,
        string $doverunnerSiteKey,
        string $doverunnerSiteId,
        string $doverunnerIv,
        string $licenseUrl,
        string $certificateUrl,
    ) {
        $this->_jwtService          = $jwtService;
        $this->_doverunnerAccessKey = $doverunnerAccessKey;
        $this->_doverunnerSiteKey   = $doverunnerSiteKey;
        $this->_doverunnerSiteId    = $doverunnerSiteId;
        $this->_doverunnerIv        = $doverunnerIv;
        $this->_licenseUrl          = $licenseUrl;
        $this->_certificateUrl      = $certificateUrl;
    }

    /**
     * 멀티 DRM(Widevine/PlayReady/FairPlay) 재생용 플레이어 URL을 생성한다.
     * User-Agent를 분석해 브라우저에 맞는 DRM 종류와 스트리밍 방식을 자동으로 선택한다.
     *
     * @param string $clientUserId    서비스 측 사용자 식별자 (DoveRunner user_id로도 사용)
     * @param string $mediaContentKey Kollus 미디어 콘텐츠 키
     * @param string $contentId       멀티 DRM 콘텐츠 ID (Kollus 업로드 파일 키)
     * @param string $userAgent       요청 User-Agent (DRM/스트리밍 방식 판별용)
     * @return string                 iframe src에 사용할 플레이어 URL
     *
     * @throws RuntimeException JWT 생성 또는 DoveRunner 라이선스 토큰 암호화 실패 시
     */
    public function buildPlayerUrl(string $clientUserId, string $mediaContentKey, string $contentId, string $userAgent): string
    {
        $streamingInfo = $this->_resolveStreamingInfo($userAgent);

        $mediaContentEntry = [
            'mckey'      => $mediaContentKey,
            'drm_policy' => [
                // kind 는 Kollus 플레이어 규격값이므로 'inka' 를 그대로 사용
                'kind'           => 'inka',
                'streaming_type' => $streamingInfo['streaming_type'],
                'data'           => [
                    'license_url'     => $this->_licenseUrl,
                    'certificate_url' => $this->_certificateUrl . '?siteId=' . $this->_doverunnerSiteId,
                    'custom_header'   => [
                        'key'   => 'pallycon-customdata-v2',
                        'value' => $this->_buildCustomHeaderPayload($clientUserId, $contentId, $streamingInfo['drm_type']),
                    ],
                ],
            ],
        ];

        return $this->_jwtService->buildMultiDrmPlayerUrl(
            clientUserId: $clientUserId,
            mediaContentEntries: [$mediaContentEntry],
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
     * pallycon-customdata-v2 헤더에 담을 DoveRunner 커스텀 페이로드를 생성한다.
     *
     * @param string $clientUserId 서비스 측 사용자 식별자
     * @param string $contentId    멀티 DRM 콘텐츠 ID
     * @param string $drmType      Widevine / PlayReady / FairPlay
     * @return string              base64 인코딩된 JSON 페이로드
     *
     * @throws RuntimeException 페이로드 직렬화 또는 라이선스 토큰 생성 실패 시
     */
    private function _buildCustomHeaderPayload(string $clientUserId, string $contentId, string $drmType): string
    {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $token     = $this->_buildEncryptedLicenseToken();

        // DoveRunner 규격: accessKey + drmType + siteId + userId + cid + token + timestamp 를 이어붙여 SHA-256 → base64
        $hash = base64_encode(hash(
            'sha256',
            $this->_doverunnerAccessKey . $drmType . $this->_doverunnerSiteId . $clientUserId . $contentId . $token . $timestamp,
            true,
        ));

        try {
            $payload = json_encode([
                'drm_type'  => $drmType,
                'site_id'   => $this->_doverunnerSiteId,
                'user_id'   => $clientUserId,
                'cid'       => $contentId,
                'token'     => $token,
                'timestamp' => $timestamp,
                'hash'      => $hash,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // 페이로드 값은 모두 문자열이라 직렬화 실패는 인코딩 문제 등 환경/코드 결함 —
            // 호출자(컨트롤러)가 RuntimeException 하나로 처리할 수 있도록 래핑해 재throw
            throw new RuntimeException('DoveRunner 페이로드 직렬화에 실패했습니다: ' . $e->getMessage(), previous: $e);
        }

        return base64_encode($payload);
    }

    /**
     * 라이선스 룰을 AES-256-CBC로 암호화해 DoveRunner 토큰을 생성한다.
     *
     * @return string base64 인코딩된 라이선스 룰 암호문
     *
     * @throws RuntimeException 라이선스 룰 직렬화 또는 암호화 실패 시
     */
    private function _buildEncryptedLicenseToken(): string
    {
        try {
            $rule = json_encode(self::LICENSE_RULE, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // LICENSE_RULE은 고정 상수이므로 실패는 코드 결함 —
            // 호출자가 일관되게 처리할 수 있도록 RuntimeException으로 래핑해 재throw
            throw new RuntimeException('DoveRunner 라이선스 룰 직렬화에 실패했습니다: ' . $e->getMessage(), previous: $e);
        }

        $encrypted = openssl_encrypt($rule, self::CIPHER, $this->_doverunnerSiteKey, OPENSSL_RAW_DATA, $this->_doverunnerIv);

        if ($encrypted === false) {
            throw new RuntimeException('DoveRunner 라이선스 룰 암호화에 실패했습니다.');
        }

        return base64_encode($encrypted);
    }
}
