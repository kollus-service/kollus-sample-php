<?php

namespace App\Libraries\Kollus;

use InvalidArgumentException;
use RuntimeException;

class KollusDrmCallbackService
{
    /** kind1: 다운로드 버튼 클릭 시 권한 및 DRM 정책 할당 */
    public const KIND_DOWNLOAD_APPROVAL = 1;

    /** kind2: 파일 저장 100% 완료 시 결과 통보 */
    public const KIND_DOWNLOAD_COMPLETE = 2;

    /** kind3: 오프라인 저장 콘텐츠 재생 시 권한 확인 */
    public const KIND_OFFLINE_PLAYBACK = 3;

    /** kind1 기본 만료 오프셋 (초) */
    private const DEFAULT_EXPIRY_OFFSET_SECONDS = 600;

    public function __construct(
        private readonly KollusJwtService $jwtService,
    ) {
    }

    /**
     * items 배열을 받아 각 항목별 응답을 조립하고 JWT로 인코딩해 반환한다.
     *
     * 응답 구조: { "data": [ {kind, media_content_key, result, ...}, ... ] }
     *
     * @param array[] $items 요청에서 파싱된 items 배열
     * @return string        JWT 인코딩된 응답
     *
     * @throws RuntimeException JWT 인코딩 실패 시
     */
    public function buildResponse(array $items): string
    {
        $responseItems = array_map(
            fn (array $item): array => $this->buildItemResponse($item),
            $items,
        );

        return $this->jwtService->encodeJwt(['data' => $responseItems]);
    }

    /**
     * 개별 item의 kind에 따라 응답 객체를 조립한다.
     *
     * @throws InvalidArgumentException 지원하지 않는 kind 값인 경우
     */
    private function buildItemResponse(array $item): array
    {
        return match ((int) $item['kind']) {
            self::KIND_DOWNLOAD_APPROVAL => $this->buildKind1Item($item),
            self::KIND_DOWNLOAD_COMPLETE => $this->buildKind2Item($item),
            self::KIND_OFFLINE_PLAYBACK  => $this->buildKind3Item($item),
            default => throw new InvalidArgumentException("지원하지 않는 kind 값입니다: {$item['kind']}"),
        };
    }

    /**
     * kind1: 다운로드 승인 응답.
     * expiration_date는 Kollus 시스템에 기록 후 수정 불가이므로 정확한 값을 전달해야 함.
     */
    private function buildKind1Item(array $item): array
    {
        // TODO: 실제 서비스에서는 client_user_id / media_content_key로 DB 조회 후 result 결정
        return [
            'kind'              => self::KIND_DOWNLOAD_APPROVAL,
            'media_content_key' => $item['media_content_key'],
            'result'            => 1,
            'expiration_date'   => time() + self::DEFAULT_EXPIRY_OFFSET_SECONDS,
        ];
    }

    /**
     * kind2: 다운로드 완료 통보 응답.
     * content_delete=0 기본값 — 다운로드 후 즉시 삭제하지 않음.
     */
    private function buildKind2Item(array $item): array
    {
        return [
            'kind'              => self::KIND_DOWNLOAD_COMPLETE,
            'media_content_key' => $item['media_content_key'],
            'result'            => 1,
            'content_delete'    => 0,
        ];
    }

    /**
     * kind3: 오프라인 재생 권한 확인 응답.
     * start_at은 요청에서 수신한 값을 그대로 echo해야 함 (Kollus 규격).
     */
    private function buildKind3Item(array $item): array
    {
        return [
            'kind'              => self::KIND_OFFLINE_PLAYBACK,
            'session_key'       => $item['session_key'] ?? null,
            'media_content_key' => $item['media_content_key'],
            'start_at'          => (int) ($item['start_at'] ?? 0),
            'result'            => 1,
            'content_expired'   => 0,
        ];
    }
}
