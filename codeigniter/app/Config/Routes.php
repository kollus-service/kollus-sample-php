<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

/*
|--------------------------------------------------------------------------
| Kollus 플레이어
|--------------------------------------------------------------------------
| GET /kollus/player?media_content_key=...
|
| 실제 서비스에서는 auth 필터를 추가해 인증된 사용자만 접근하도록 제한할 것.
*/
$routes->get('kollus/player', 'Kollus\PlayerController::show', ['as' => 'kollus.player']);

/*
|--------------------------------------------------------------------------
| Kollus 멀티 DRM 플레이어
|--------------------------------------------------------------------------
| GET /kollus/player/multi-drm?media_content_key=...&content_id=...
|
| Widevine/PlayReady/FairPlay 등 멀티 DRM(DoveRunner) 재생용.
*/
$routes->get('kollus/player/multi-drm', 'Kollus\PlayerController::showMultiDrm', ['as' => 'kollus.player.multi-drm']);

/*
|--------------------------------------------------------------------------
| Kollus 플레이 콜백
|--------------------------------------------------------------------------
| POST /kollus/play-callback
| CSRF 제외는 app/Config/Filters.php의 csrf 필터 except에서 처리
*/
$routes->post('kollus/play-callback', 'Kollus\PlayCallbackController::handle', ['as' => 'kollus.play-callback']);

/*
|--------------------------------------------------------------------------
| Kollus DRM 다운로드 콜백
|--------------------------------------------------------------------------
| POST /kollus/drm-callback
*/
$routes->post('kollus/drm-callback', 'Kollus\DrmCallbackController::handle', ['as' => 'kollus.drm-callback']);

/*
|--------------------------------------------------------------------------
| Kollus 다음 회차 연속 재생 콜백
|--------------------------------------------------------------------------
| POST /kollus/next-episode-callback
|
| Kollus 플레이어(iframe, 별도 origin)의 JS가 브라우저에서 직접 호출하는
| 크로스 오리진 요청이므로 cors 필터를 적용한다. preflight(OPTIONS) 요청은
| 라우팅 단계에서 필터보다 먼저 매칭되어야 하므로 OPTIONS도 함께 등록한다.
*/
$routes->match(['post', 'options'], 'kollus/next-episode-callback', 'Kollus\NextEpisodeCallbackController::handle', [
    'as'     => 'kollus.next-episode-callback',
    'filter' => 'cors',
]);

/*
|--------------------------------------------------------------------------
| Kollus LMS 콜백
|--------------------------------------------------------------------------
| POST /kollus/lms-callback
*/
$routes->post('kollus/lms-callback', 'Kollus\LmsCallbackController::handle', ['as' => 'kollus.lms-callback']);

/*
|--------------------------------------------------------------------------
| Kollus 콘텐츠 업로드
|--------------------------------------------------------------------------
| GET  /kollus/upload            — 업로드 페이지
| POST /kollus/upload/create-url — 일회용 업로드 URL 발급 (AJAX, CSRF 보호)
*/
$routes->get('kollus/upload', 'Kollus\UploadController::index', ['as' => 'kollus.upload']);
$routes->post('kollus/upload/create-url', 'Kollus\UploadController::createUrl', ['as' => 'kollus.upload.create-url']);
