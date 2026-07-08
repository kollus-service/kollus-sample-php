<?php

namespace App\Providers;

use App\Services\KollusDrmCallbackService;
use App\Services\KollusJwtService;
use App\Services\KollusNextEpisodeCallbackService;
use App\Services\KollusPlayCallbackService;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // KollusJwtService를 싱글턴으로 등록 — config 값을 생성자에 주입
        $this->app->singleton(KollusJwtService::class, function () {
            $securityKey = config('kollus.security_key');
            $customKey   = config('kollus.custom_key');

            // 인증 키가 없으면 서비스 구동 불가 — 명확한 오류 메시지 제공
            if (empty($securityKey) || empty($customKey)) {
                throw new InvalidArgumentException(
                    'KOLLUS_SECURITY_KEY와 KOLLUS_CUSTOM_KEY 환경변수가 설정되지 않았습니다.'
                );
            }

            return new KollusJwtService(
                securityKey:    $securityKey,
                customKey:      $customKey,
                playerUrl:      config('kollus.player_url'),
                expireSeconds:  config('kollus.token_expire_seconds'),
            );
        });

        // KollusPlayCallbackService: KollusJwtService에 의존하므로 컨테이너 해결
        $this->app->singleton(KollusPlayCallbackService::class, function ($app) {
            return new KollusPlayCallbackService(
                jwtService: $app->make(KollusJwtService::class),
            );
        });

        // KollusDrmCallbackService: KollusJwtService에 의존
        $this->app->singleton(KollusDrmCallbackService::class, function ($app) {
            return new KollusDrmCallbackService(
                jwtService: $app->make(KollusJwtService::class),
            );
        });

        // KollusNextEpisodeCallbackService: KollusJwtService에 의존, 샘플 MCK 목록을 config에서 주입
        $this->app->singleton(KollusNextEpisodeCallbackService::class, function ($app) {
            return new KollusNextEpisodeCallbackService(
                jwtService: $app->make(KollusJwtService::class),
                nextEpisodeMck: config('kollus.next_episode_mck'),
            );
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
