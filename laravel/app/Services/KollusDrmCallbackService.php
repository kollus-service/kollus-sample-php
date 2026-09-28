<?php
/**
 * Kollus DRM 다운로드 콜백(kind 1/2/3)의 items 배열을 받아 항목별 응답을 조립하고 JWT로 인코딩하는 서비스.
 */

namespace App\Services;

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

    /** 콜백 응답 JWT 인코딩에 사용하는 JWT 서비스 */
    private readonly KollusJwtService $_jwtService;

    /**
     * @param KollusJwtService $jwtService 콜백 응답 JWT 인코딩용 서비스 (일반 재생 계정 키 사용)
     */
    public function __construct(KollusJwtService $jwtService)
    {
        $this->_jwtService = $jwtService;
    }

    /**
     * items 배열을 받아 각 항목별 응답을 조립하고 JWT로 인코딩해 반환한다.
     *
     * 응답 구조: { "data": [ {kind, media_content_key, result, ...}, ... ] }
     *
     * @param array[] $items KollusDrmCallbackRequest에서 파싱된 items 배열
     * @return string        JWT 인코딩된 응답
     *
     * @throws RuntimeException         JWT 인코딩 실패 시
     * @throws InvalidArgumentException 지원하지 않는 kind 값이 포함된 경우
     */
    public function buildResponse(array $items): string
    {
        $responseItems = array_map(
            fn(array $item): array => $this->_buildItemResponse($item),
            $items,
        );

        return $this->_jwtService->encodeJwt(['data' => $responseItems]);
    }

    /**
     * 개별 item의 kind에 따라 응답 객체를 조립한다.
     *
     * @param array<string, mixed> $item 콜백 요청 item
     * @return array<string, mixed>      kind별 응답 객체
     *
     * @throws InvalidArgumentException 지원하지 않는 kind 값인 경우
     */
    private function _buildItemResponse(array $item): array
    {
        return match ((int) $item['kind']) {
            self::KIND_DOWNLOAD_APPROVAL => $this->_buildKind1Item($item),
            self::KIND_DOWNLOAD_COMPLETE => $this->_buildKind2Item($item),
            self::KIND_OFFLINE_PLAYBACK  => $this->_buildKind3Item($item),
            default => throw new InvalidArgumentException("지원하지 않는 kind 값입니다: {$item['kind']}"),
        };
    }

    /**
     * kind1: 다운로드 승인 응답.
     * expiration_date는 Kollus 시스템에 기록 후 수정 불가이므로 정확한 값을 전달해야 함.
     *
     * @param array<string, mixed> $item 콜백 요청 item
     * @return array<string, mixed>
     */
    private function _buildKind1Item(array $item): array
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
     *
     * @param array<string, mixed> $item 콜백 요청 item
     * @return array<string, mixed>
     */
    private function _buildKind2Item(array $item): array
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
     *
     * @param array<string, mixed> $item 콜백 요청 item
     * @return array<string, mixed>
     */
    private function _buildKind3Item(array $item): array
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
