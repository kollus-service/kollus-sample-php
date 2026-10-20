<?php
/**
 * Kollus 연동 설정. 보안키/커스텀키/Access Token, 플레이어 URL, JWT 만료 시간, 다음 회차 MCK 목록,
 * 멀티 DRM(DoveRunner) 계정 키를 .env 환경변수에서 읽는다.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Kollus 보안 키 (JWT 서명용)
    |--------------------------------------------------------------------------
    | KOLLUS_SECURITY_KEY: Kollus 대시보드 > 채널 설정에서 발급
    */
    'security_key' => env('KOLLUS_SECURITY_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Kollus 커스텀 키
    |--------------------------------------------------------------------------
    | KOLLUS_CUSTOM_KEY: Kollus 대시보드 > 채널 설정에서 발급
    */
    'custom_key' => env('KOLLUS_CUSTOM_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Kollus API Access Token
    |--------------------------------------------------------------------------
    | Kollus VOD 콘솔 > 서비스 계정 > API 에서 발급
    */
    'access_token' => env('KOLLUS_ACCESS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Kollus 플레이어 기본 URL
    |--------------------------------------------------------------------------
    */
    'player_url' => env('KOLLUS_PLAYER_URL', 'https://v.kr.kollus.com/'),

    /*
    |--------------------------------------------------------------------------
    | JWT 만료 시간 (초)
    |--------------------------------------------------------------------------
    | 기본값: 300초 (5분)
    */
    'token_expire_seconds' => (int) env('KOLLUS_TOKEN_EXPIRE_SECONDS', 300),

    /*
    |--------------------------------------------------------------------------
    | 다음 회차 샘플 MCK 목록
    |--------------------------------------------------------------------------
    | 다음 회차 연속 재생 기능 샘플용 미디어 콘텐츠 키 목록 (콤마로 구분).
    | 실제 서비스에서는 currentMck 기준으로 DB/API 조회로 대체할 것.
    */
    'next_episode_mcks' => env('KOLLUS_NEXT_EPISODE_MCKS', ''), // 콤마 구분 목록 (예: mck1,mck2)

    /*
    |--------------------------------------------------------------------------
    | 멀티 DRM 전용 Kollus 계정 키
    |--------------------------------------------------------------------------
    | 멀티 DRM(DoveRunner) 재생은 별도 Kollus 계정을 사용하므로
    | 위 security_key/custom_key 대신 이 값을 사용한다.
    */
    'multidrm' => [
        'security_key' => env('KOLLUS_MULTIDRM_SECURITY_KEY'),
        'custom_key'   => env('KOLLUS_MULTIDRM_CUSTOM_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 멀티 DRM (DoveRunner) 설정
    |--------------------------------------------------------------------------
    | DoveRunner 콘솔에서 발급받은 값을 입력하세요.
    */
    'doverunner' => [
        'access_key'      => env('KOLLUS_DOVERUNNER_ACCESS_KEY'),
        'site_key'        => env('KOLLUS_DOVERUNNER_SITE_KEY'),
        'site_id'         => env('KOLLUS_DOVERUNNER_SITE_ID'),
        'iv'              => env('KOLLUS_DOVERUNNER_IV', '0123456789abcdef'),
        'license_url'     => env('KOLLUS_DOVERUNNER_LICENSE_URL', 'https://drm-license.doverunner.com/ri/licenseManager.do'),
        'certificate_url' => env('KOLLUS_DOVERUNNER_CERTIFICATE_URL', 'https://drm-license.doverunner.com/ri/fpsKeyManager.do'),
    ],

];
