<?php

namespace App\Services;

use RuntimeException;

class KollusPlayCallbackService
{
    /** kind1: 재생 시작 전 만료 일시 및 보안 옵션 설정 */
    public const KIND_PLAY_EXPIRY = 1;

    /** kind3: 플레이어 준비 완료 직후 최종 재생 권한 확인 */
    public const KIND_FINAL_APPROVAL = 3;

    /** 재생 만료 기본 오프셋 (초) */
    private const DEFAULT_EXPIRY_OFFSET_SECONDS = 600;

    public function __construct(
        private readonly KollusJwtService $jwtService,
    ) {}

    /**
     * 콜백 kind에 맞는 JWT 응답 문자열을 반환한다.
     *
     * @param int         $kind        1 또는 3
     * @param bool        $isAllowed   재생 허용 여부
     * @param string|null $denyMessage 차단 시 플레이어에 노출할 메시지
     * @return string                  JWT 인코딩된 응답
     *
     * @throws RuntimeException JWT 인코딩 실패 시
     */
    public function buildResponse(int $kind, bool $isAllowed, ?string $denyMessage = null): string
    {
        $payload = match ($kind) {
            self::KIND_PLAY_EXPIRY    => $this->buildKind1Payload($isAllowed, $denyMessage),
            self::KIND_FINAL_APPROVAL => $this->buildKind3Payload($isAllowed, $denyMessage),
            default => throw new \InvalidArgumentException("지원하지 않는 kind 값입니다: {$kind}"),
        };

        return $this->jwtService->encodeJwt($payload);
    }

    /**
     * kind1: 재생 만료 일시 및 보안 옵션.
     * expiration_date는 Kollus 시스템에 기록된 후 수정 불가이므로 정확한 값을 전달해야 함.
     */
    private function buildKind1Payload(bool $isAllowed, ?string $denyMessage): array
    {
        $data = [
            'result'          => (int) $isAllowed,
            'expiration_date' => time() + self::DEFAULT_EXPIRY_OFFSET_SECONDS,
            'disable_tvout'   => 0,
        ];

        // 차단 시에만 메시지 포함 — 불필요한 필드 전송 방지
        if (! $isAllowed && $denyMessage !== null) {
            $data['message'] = $denyMessage;
        }

        return ['data' => $data];
    }

    /**
     * kind3: 재생 직전 최종 권한 확인.
     */
    private function buildKind3Payload(bool $isAllowed, ?string $denyMessage): array
    {
        $data = [
            'result' => (int) $isAllowed,
        ];

        // 차단 시에만 메시지 포함
        if (! $isAllowed && $denyMessage !== null) {
            $data['message'] = $denyMessage;
        }

        return ['data' => $data];
    }
}
