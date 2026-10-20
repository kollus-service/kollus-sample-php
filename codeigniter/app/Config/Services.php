<?php

namespace Config;

use App\Libraries\Kollus\KollusDrmCallbackService;
use App\Libraries\Kollus\KollusJwtService;
use App\Libraries\Kollus\KollusLmsCallbackService;
use App\Libraries\Kollus\KollusLmsLogger;
use App\Libraries\Kollus\KollusMultiDrmService;
use App\Libraries\Kollus\KollusNextEpisodeCallbackService;
use App\Libraries\Kollus\KollusPlayCallbackService;
use App\Libraries\Kollus\KollusUploadService;
use CodeIgniter\Config\BaseService;
use InvalidArgumentException;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * 일반 재생용 KollusJwtService. config('kollus.security_key')/config('kollus.custom_key')로 서명한다.
     */
    public static function kollusJwtService(bool $getShared = true): KollusJwtService
    {
        if ($getShared) {
            return static::getSharedInstance('kollusJwtService');
        }

        $config = config(Kollus::class);

        // 인증 키가 없으면 서비스 구동 불가 — 명확한 오류 메시지 제공
        if ($config->securityKey === '' || $config->customKey === '') {
            throw new InvalidArgumentException(
                'KOLLUS_SECURITY_KEY와 KOLLUS_CUSTOM_KEY 환경변수가 설정되지 않았습니다.'
            );
        }

        return new KollusJwtService(
            $config->securityKey,
            $config->customKey,
            $config->playerUrl,
            $config->tokenExpireSeconds,
        );
    }

    /**
     * 멀티 DRM은 일반 재생과 다른 Kollus 계정(security_key/custom_key)을 사용하므로
     * 공유 KollusJwtService가 아닌 별도 인스턴스를 사용한다.
     */
    public static function kollusMultiDrmJwtService(bool $getShared = true): KollusJwtService
    {
        if ($getShared) {
            return static::getSharedInstance('kollusMultiDrmJwtService');
        }

        $config = config(Kollus::class);

        if ($config->multiDrmSecurityKey === '' || $config->multiDrmCustomKey === '') {
            throw new InvalidArgumentException(
                'KOLLUS_MULTIDRM_SECURITY_KEY와 KOLLUS_MULTIDRM_CUSTOM_KEY 환경변수가 설정되지 않았습니다.'
            );
        }

        return new KollusJwtService(
            $config->multiDrmSecurityKey,
            $config->multiDrmCustomKey,
            $config->playerUrl,
            $config->tokenExpireSeconds,
        );
    }

    /**
     * 멀티 DRM(Widevine/PlayReady/FairPlay, DoveRunner) 재생용 서비스.
     */
    public static function kollusMultiDrmService(bool $getShared = true): KollusMultiDrmService
    {
        if ($getShared) {
            return static::getSharedInstance('kollusMultiDrmService');
        }

        $config = config(Kollus::class);

        if ($config->doverunnerAccessKey === '' || $config->doverunnerSiteKey === '' || $config->doverunnerSiteId === '') {
            throw new InvalidArgumentException(
                'KOLLUS_DOVERUNNER_ACCESS_KEY, KOLLUS_DOVERUNNER_SITE_KEY, KOLLUS_DOVERUNNER_SITE_ID 환경변수가 설정되지 않았습니다.'
            );
        }

        return new KollusMultiDrmService(
            static::kollusMultiDrmJwtService(),
            $config->doverunnerAccessKey,
            $config->doverunnerSiteKey,
            $config->doverunnerSiteId,
            $config->doverunnerIv,
            $config->doverunnerLicenseUrl,
            $config->doverunnerCertificateUrl,
        );
    }

    /**
     * Kollus 콘텐츠 업로드 API 연동 서비스.
     */
    public static function kollusUploadService(bool $getShared = true): KollusUploadService
    {
        if ($getShared) {
            return static::getSharedInstance('kollusUploadService');
        }

        $config = config(Kollus::class);

        return new KollusUploadService(
            service('curlrequest'),
            $config->accessToken,
        );
    }

    /**
     * 재생 콜백(play-callback) 응답 서비스.
     */
    public static function kollusPlayCallbackService(bool $getShared = true): KollusPlayCallbackService
    {
        if ($getShared) {
            return static::getSharedInstance('kollusPlayCallbackService');
        }

        return new KollusPlayCallbackService(static::kollusJwtService());
    }

    /**
     * DRM 다운로드 콜백(drm-callback) 응답 서비스.
     */
    public static function kollusDrmCallbackService(bool $getShared = true): KollusDrmCallbackService
    {
        if ($getShared) {
            return static::getSharedInstance('kollusDrmCallbackService');
        }

        return new KollusDrmCallbackService(static::kollusJwtService());
    }

    /**
     * 다음 회차 연속 재생 콜백(next-episode-callback) 응답 서비스.
     */
    public static function kollusNextEpisodeCallbackService(bool $getShared = true): KollusNextEpisodeCallbackService
    {
        if ($getShared) {
            return static::getSharedInstance('kollusNextEpisodeCallbackService');
        }

        $config = config(Kollus::class);

        return new KollusNextEpisodeCallbackService(
            static::kollusJwtService(),
            $config->nextEpisodeMcks,
        );
    }

    /**
     * LMS 콜백(lms-callback) 페이로드 요약 서비스.
     */
    public static function kollusLmsCallbackService(bool $getShared = true): KollusLmsCallbackService
    {
        if ($getShared) {
            return static::getSharedInstance('kollusLmsCallbackService');
        }

        return new KollusLmsCallbackService();
    }

    /**
     * LMS 콜백 전용 로그 기록기 (writable/logs/kollus-lms.log).
     */
    public static function kollusLmsLogger(bool $getShared = true): KollusLmsLogger
    {
        if ($getShared) {
            return static::getSharedInstance('kollusLmsLogger');
        }

        return new KollusLmsLogger();
    }
}
