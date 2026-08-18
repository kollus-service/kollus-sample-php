<?php

namespace App\Libraries\Kollus;

use JsonException;
use RuntimeException;

class KollusJwtService
{
    /** JWT 헤더 고정값 */
    private const JWT_HEADER = ['typ' => 'JWT', 'alg' => 'HS256'];

    /** HMAC 알고리즘 */
    private const HMAC_ALGORITHM = 'sha256';

    public function __construct(
        private readonly string $securityKey,
        private readonly string $customKey,
        private readonly string $playerUrl,
        private readonly int $expireSeconds,
    ) {
    }

    /**
     * 미디어 콘텐츠 키 목록으로 플레이어 URL을 생성한다.
     *
     * @param string   $clientUserId     서비스 측 사용자 식별자
     * @param string[] $mediaContentKeys 재생할 미디어 콘텐츠 키 배열
     * @return string                    iframe src에 사용할 플레이어 URL
     *
     * @throws RuntimeException JWT 생성 실패 시
     */
    public function buildPlayerUrl(string $clientUserId, array $mediaContentKeys): string
    {
        $token = $this->generateToken($clientUserId, $mediaContentKeys);

        return $this->playerUrl
            . 's?jwt=' . $token
            . '&custom_key=' . $this->customKey;
    }

    /**
     * 다음 회차 연속 재생용 플레이어 URL을 생성한다.
     * Kollus 다음회차 콜백은 SI 링크(/si) 형태를 요구함 — /s가 아님.
     *
     * @throws RuntimeException JWT 생성 실패 시
     */
    public function buildNextEpisodeUrl(string $clientUserId, string $mediaContentKey): string
    {
        $token = $this->generateToken($clientUserId, [$mediaContentKey]);

        return $this->playerUrl
            . 'si?jwt=' . $token
            . '&custom_key=' . $this->customKey;
    }

    /**
     * 멀티 DRM(Widevine/PlayReady/FairPlay 등) 재생용 플레이어 URL을 생성한다.
     * mc 항목별 drm_policy는 호출자가 조립해 전달한다 (KollusMultiDrmService 참고).
     *
     * @param array<int, array<string, mixed>> $mediaContentEntries drm_policy를 포함한 mc 항목 배열
     * @param array<string, mixed>|null        $watermarkingPolicy  워터마킹 정책 (미지정 시 미적용)
     *
     * @throws RuntimeException JWT 생성 실패 시
     */
    public function buildMultiDrmPlayerUrl(string $clientUserId, array $mediaContentEntries, ?array $watermarkingPolicy = null): string
    {
        $payload = [
            'cuid' => $clientUserId,
            'mc'   => $mediaContentEntries,
            'expt' => time() + $this->expireSeconds,
        ];

        if ($watermarkingPolicy !== null) {
            $payload['video_watermarking_code_policy'] = $watermarkingPolicy;
        }

        $token = $this->encodeJwt($payload);

        return $this->playerUrl
            . 's?jwt=' . $token
            . '&custom_key=' . $this->customKey;
    }

    /**
     * JWT 토큰을 생성한다.
     *
     * @param string[] $mediaContentKeys
     *
     * @throws RuntimeException
     */
    private function generateToken(string $clientUserId, array $mediaContentKeys): string
    {
        $payload = $this->buildPayload($clientUserId, $mediaContentKeys);

        return $this->encodeJwt($payload);
    }

    /**
     * JWT payload를 조립한다.
     *
     * @param string[] $mediaContentKeys
     */
    private function buildPayload(string $clientUserId, array $mediaContentKeys): array
    {
        // 각 미디어 키를 Kollus mc 클레임 형식으로 변환
        $mediaClaims = array_map(
            static fn (string $key): array => ['mckey' => $key],
            $mediaContentKeys,
        );

        return [
            'cuid' => $clientUserId,
            'mc'   => $mediaClaims,
            'expt' => time() + $this->expireSeconds,
        ];
    }

    /**
     * HS256 서명 방식의 JWT를 인코딩한다.
     * public으로 노출하여 플레이 콜백 응답 등 외부에서도 재사용 가능하도록 함.
     *
     * @throws RuntimeException JSON 직렬화 실패 시
     */
    public function encodeJwt(array $payload): string
    {
        try {
            $header    = $this->base64UrlEncode(json_encode(self::JWT_HEADER, JSON_THROW_ON_ERROR));
            $body      = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
            $signature = $this->base64UrlEncode(
                hash_hmac(self::HMAC_ALGORITHM, $header . '.' . $body, $this->securityKey, true)
            );
        } catch (JsonException $e) {
            // JSON 직렬화 실패는 프로그래밍 오류이므로 RuntimeException으로 래핑
            throw new RuntimeException('JWT payload 직렬화에 실패했습니다: ' . $e->getMessage(), previous: $e);
        }

        return $header . '.' . $body . '.' . $signature;
    }

    /**
     * Base64 URL-safe 인코딩 (패딩 제거 포함).
     * PHP 표준 함수만 사용해 별도 의존성 없이 구현.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
