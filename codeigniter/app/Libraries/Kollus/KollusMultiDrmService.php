<?php

namespace App\Libraries\Kollus;

use JsonException;
use RuntimeException;

class KollusMultiDrmService
{
    /** inkaDRM 라이선스 룰 (고정값) */
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

    private const CIPHER = 'AES-256-CBC';

    public function __construct(
        private readonly KollusJwtService $jwtService,
        private readonly string $inkaAccessKey,
        private readonly string $inkaSiteKey,
        private readonly string $inkaSiteId,
        private readonly string $inkaIv,
        private readonly string $licenseUrl,
        private readonly string $certificateUrl,
    ) {
    }

    /**
     * 멀티 DRM(Widevine/PlayReady/FairPlay) 재생용 플레이어 URL을 생성한다.
     * User-Agent를 분석해 브라우저에 맞는 DRM 종류와 스트리밍 방식을 자동으로 선택한다.
     *
     * @param string $mediaContentKey Kollus 미디어 콘텐츠 키
     * @param string $contentId       멀티 DRM 콘텐츠 ID (Kollus 업로드 파일 키)
     * @param string $userAgent       요청 User-Agent (DRM/스트리밍 방식 판별용)
     *
     * @throws RuntimeException JWT 생성 또는 inkaDRM 라이선스 토큰 암호화 실패 시
     */
    public function buildPlayerUrl(string $clientUserId, string $mediaContentKey, string $contentId, string $userAgent): string
    {
        $streamingInfo = $this->resolveStreamingInfo($userAgent);

        $mediaContentEntry = [
            'mckey'      => $mediaContentKey,
            'drm_policy' => [
                'kind'           => 'inka',
                'streaming_type' => $streamingInfo['streaming_type'],
                'data'           => [
                    'license_url'     => $this->licenseUrl,
                    'certificate_url' => $this->certificateUrl . '?siteId=' . $this->inkaSiteId,
                    'custom_header'   => [
                        'key'   => 'pallycon-customdata-v2',
                        'value' => $this->buildCustomHeaderPayload($clientUserId, $contentId, $streamingInfo['drm_type']),
                    ],
                ],
            ],
        ];

        return $this->jwtService->buildMultiDrmPlayerUrl(
            $clientUserId,
            [$mediaContentEntry],
        );
    }

    /**
     * User-Agent 문자열로 브라우저별 DRM 종류와 스트리밍 방식을 판별한다.
     * 매칭되는 브라우저가 없으면 Widevine/dash를 기본값으로 사용한다.
     *
     * @return array{drm_type: string, streaming_type: string}
     */
    private function resolveStreamingInfo(string $userAgent): array
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
     */
    private function buildCustomHeaderPayload(string $clientUserId, string $contentId, string $drmType): string
    {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $token     = $this->buildEncryptedLicenseToken();

        $hash = base64_encode(hash(
            'sha256',
            $this->inkaAccessKey . $drmType . $this->inkaSiteId . $clientUserId . $contentId . $token . $timestamp,
            true,
        ));

        try {
            $payload = json_encode([
                'drm_type'  => $drmType,
                'site_id'   => $this->inkaSiteId,
                'user_id'   => $clientUserId,
                'cid'       => $contentId,
                'token'     => $token,
                'timestamp' => $timestamp,
                'hash'      => $hash,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('inkaDRM 페이로드 직렬화에 실패했습니다: ' . $e->getMessage(), previous: $e);
        }

        return base64_encode($payload);
    }

    /**
     * 라이선스 룰을 AES-256-CBC로 암호화해 inkaDRM 토큰을 생성한다.
     */
    private function buildEncryptedLicenseToken(): string
    {
        try {
            $rule = json_encode(self::LICENSE_RULE, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('inkaDRM 라이선스 룰 직렬화에 실패했습니다: ' . $e->getMessage(), previous: $e);
        }

        $encrypted = openssl_encrypt($rule, self::CIPHER, $this->inkaSiteKey, OPENSSL_RAW_DATA, $this->inkaIv);

        if ($encrypted === false) {
            throw new RuntimeException('inkaDRM 라이선스 룰 암호화에 실패했습니다.');
        }

        return base64_encode($encrypted);
    }
}
