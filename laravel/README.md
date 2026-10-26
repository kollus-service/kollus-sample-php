# Kollus Laravel Player — 플레이어 재생·콘텐츠 업로드·콜백 처리 샘플

> ⚠ **보안 안내**: PHP 보안 취약점으로 **8.3.36 / 8.4.27 / 8.5.12 이상**으로 업데이트하세요.

## 개요

Kollus 동영상 플레이어를 Laravel 프레임워크로 통합한 샘플 프로젝트입니다. 플레이어 재생, 멀티 DRM(DoveRunner) 재생, 콘텐츠 업로드, 재생/DRM/다음회차/LMS 콜백 처리까지 Kollus 연동에 필요한 서버 사이드 로직을 포함합니다.

- **플레이어 재생** — `media_content_key`로 HS256 JWT를 발급하고 플레이어 iframe을 렌더링
- **멀티 DRM 재생** — User-Agent별 DRM 종류(Widevine/PlayReady/FairPlay)를 판별하고 DoveRunner 라이선스 룰을 암호화해 `drm_policy`를 조립
- **콘텐츠 업로드** — 서버가 Kollus Upload API로 일회용 업로드 URL을 발급하고, 브라우저가 파일을 직접 전송(진행률 표시)
- **콜백 처리** — 재생·DRM·다음 회차·LMS 콜백을 수신해 Kollus 규격에 맞는 JWT/JSON 응답 반환

## 사전 요구사항

- PHP 8.3.36 이상 (8.4는 8.4.27, 8.5는 8.5.12 이상 — PHP2Shell 보안 패치 적용 버전) / Composer
- Node.js (Vite 에셋 빌드용)
- Kollus 서비스 계정 및 API Key ([Kollus 관리자 콘솔](https://c-kr.kollus.com/service-account/custom-key) 로그인 필요)
- (선택) 멀티 DRM 재생을 사용하려면 별도의 멀티 DRM 전용 Kollus 계정 키와 DoveRunner 계정 키

> **Windows에서 PHP를 새로 설치한 경우** — `php.ini`(위치는 `php --ini`로 확인)에서 아래 두 가지를 설정해야 합니다.
> - 확장 활성화: `extension=fileinfo`, `extension=intl`, `extension=zip` 줄 앞의 `;`를 제거 (`composer install` 및 프레임워크 동작에 필요)
> - CA 인증서 번들 지정: `curl.cainfo`와 `openssl.cafile`에 CA 번들 경로를 지정 (예: [curl.se/ca/cacert.pem](https://curl.se/ca/cacert.pem)을 받아 지정하거나 Git for Windows의 `C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt` 사용). 지정하지 않으면 Kollus API 호출 시 `cURL error 60: SSL certificate ...` 오류가 발생합니다.

## 환경 설정

### 1. 의존성 설치

```bash
composer install
npm install
```

### 2. 환경변수 설정

```bash
cp .env.example .env
php artisan key:generate
```

> 이 샘플은 DB를 사용하지 않습니다. `.env.example`은 세션·캐시를 파일(`SESSION_DRIVER=file`, `CACHE_STORE=file`)로, 큐를 동기 실행(`QUEUE_CONNECTION=sync`)으로 설정해 두었으므로 DB 파일 생성이나 `php artisan migrate` 없이 실행됩니다.

`.env` 파일에서 Kollus 관련 값을 입력합니다.

| 키 | 설명 | 위치 |
|----|------|------|
| `KOLLUS_SECURITY_KEY` | JWT 서명 키 | 서비스 계정 > 보안키 |
| `KOLLUS_CUSTOM_KEY` | 플레이어 URL 커스텀 키 | 서비스 계정 > 커스텀키 |
| `KOLLUS_ACCESS_TOKEN` | Upload API 액세스 토큰 | 서비스 계정 > API 토큰 |
| `KOLLUS_PLAYER_URL` | 플레이어 기본 URL (기본값: `https://v.kr.kollus.com/`) | - |
| `KOLLUS_TOKEN_EXPIRE_SECONDS` | JWT 만료 시간(초, 기본값: 300) | - |
| `KOLLUS_NEXT_EPISODE_MCKS` | 다음 회차 재생 샘플 미디어 콘텐츠 키 목록 (콤마 구분) | - |
| `KOLLUS_MULTIDRM_SECURITY_KEY` | 멀티 DRM 전용 JWT 서명 키 (일반 재생과 다른 별도 계정) | 멀티 DRM 서비스 계정 > 보안키 |
| `KOLLUS_MULTIDRM_CUSTOM_KEY` | 멀티 DRM 전용 플레이어 URL 커스텀 키 | 멀티 DRM 서비스 계정 > 커스텀키 |
| `KOLLUS_DOVERUNNER_ACCESS_KEY` | DoveRunner Access Key | DoveRunner 콘솔 |
| `KOLLUS_DOVERUNNER_SITE_KEY` | DoveRunner Site Key (라이선스 룰 암호화용) | DoveRunner 콘솔 |
| `KOLLUS_DOVERUNNER_SITE_ID` | DoveRunner Site ID | DoveRunner 콘솔 |
| `KOLLUS_DOVERUNNER_IV` | 라이선스 룰 AES-256-CBC 암호화 IV | - |
| `KOLLUS_DOVERUNNER_LICENSE_URL` | DoveRunner 라이선스 서버 URL | - |
| `KOLLUS_DOVERUNNER_CERTIFICATE_URL` | DoveRunner FairPlay 인증서 서버 URL | - |

## 실행 방법

### 개발 서버 실행

```bash
php artisan serve
npm run dev
```

또는 `composer dev`로 서버/큐/로그/Vite를 한번에 실행할 수 있습니다.

### 동작 확인

브라우저에서 아래 URL에 접근합니다 (`미디어콘텐츠키` 부분을 실제 미디어 콘텐츠 키로 교체):

```
http://localhost:8000/kollus/player?media_content_key=미디어콘텐츠키
```

멀티 DRM 재생은 `content_id`(멀티 DRM 콘텐츠 ID, Kollus 업로드 파일 키)를 추가로 전달합니다:

```
http://localhost:8000/kollus/player/multi-drm?media_content_key=미디어콘텐츠키&content_id=멀티DRM콘텐츠ID
```

플레이어가 정상적으로 로드되면 설정이 완료된 것입니다. 업로드 페이지는 `http://localhost:8000/kollus/upload`에서 확인합니다.

## 주요 파일

| 파일 | 설명 |
|------|------|
| `app/Http/Controllers/KollusPlayerController.php` | 플레이어/멀티 DRM 플레이어 페이지 컨트롤러 |
| `app/Http/Controllers/KollusUploadController.php` | 업로드 페이지 및 업로드 URL 발급 API |
| `app/Http/Controllers/KollusPlayCallbackController.php` | 재생(Play) 콜백 수신 |
| `app/Http/Controllers/KollusDrmCallbackController.php` | DRM 콜백 수신 |
| `app/Http/Controllers/KollusNextEpisodeCallbackController.php` | 다음 회차 콜백 수신 |
| `app/Http/Controllers/KollusLmsCallbackController.php` | LMS 콜백 수신 |
| `app/Http/Requests/KollusPlayCallbackRequest.php` | 재생 콜백 파라미터 검증 |
| `app/Http/Requests/KollusDrmCallbackRequest.php` | DRM 콜백 items 파싱/검증 |
| `app/Http/Requests/KollusNextEpisodeCallbackRequest.php` | 다음 회차 콜백 파라미터 검증 |
| `app/Providers/AppServiceProvider.php` | Kollus 관련 서비스 싱글턴 바인딩 |
| `app/Services/KollusJwtService.php` | JWT 생성 및 플레이어 URL 조립 |
| `app/Services/KollusMultiDrmService.php` | 멀티 DRM(DoveRunner) 재생 URL 조립 |
| `app/Services/KollusUploadService.php` | Kollus Upload API 연동 |
| `app/Services/KollusPlayCallbackService.php` | 재생 콜백 응답(JWT) 조립 |
| `app/Services/KollusDrmCallbackService.php` | DRM 콜백 응답(JWT) 조립 |
| `app/Services/KollusNextEpisodeCallbackService.php` | 다음 회차 URL 조립 |
| `app/Services/KollusLmsCallbackService.php` | LMS payload 요약(시청 진도/스킵 감지) |
| `config/kollus.php` | Kollus 설정 (환경변수 참조) |
| `resources/views/kollus/player.blade.php` | 플레이어 iframe 뷰 |
| `resources/views/kollus/upload.blade.php` | 업로드 페이지 (드래그앤드롭 + 진행률 표시) |
| `routes/web.php` | Kollus 관련 라우트 전체 |
| `bootstrap/app.php` | 콜백 엔드포인트 CSRF 검증 제외 설정 |

## API 흐름

```
클라이언트
  │
  ├── GET /kollus/player?media_content_key=...
  │     └── KollusJwtService → JWT + 플레이어 URL 반환 (iframe src)
  │
  ├── GET /kollus/player/multi-drm?media_content_key=...&content_id=...
  │     └── KollusMultiDrmService → DoveRunner drm_policy 조립 → KollusJwtService(멀티 DRM 계정) → JWT + 플레이어 URL 반환
  │
  ├── GET /kollus/upload
  │     ├── POST /kollus/upload/create-url → Kollus Upload API 프록시
  │     └── XHR → Kollus 업로드 서버 (직접 전송)
  │
  └── Kollus 플레이어 / 서버
        ├── POST /kollus/play-callback
        ├── POST /kollus/drm-callback
        ├── POST /kollus/next-episode-callback
        └── POST /kollus/lms-callback
```

### 주요 기능

#### 샘플 플레이어 `GET /kollus/player`

`media_content_key` 쿼리 파라미터로 JWT를 발급하고 플레이어 iframe(`{player_url}s?jwt=...&custom_key=...`)을 렌더링합니다. 샘플에서는 `client_user_id`를 `guest`로 고정하며, 실제 서비스에서는 사용자 ID를 사용하여 서비스에 활용합니다.

#### 멀티 DRM 플레이어 `GET /kollus/player/multi-drm`

`media_content_key`(재생할 미디어 콘텐츠 키)와 `content_id`(멀티 DRM 콘텐츠 ID, Kollus 업로드 파일 키) 쿼리 파라미터를 받아 Widevine/PlayReady/FairPlay 멀티 DRM 재생용 플레이어를 렌더링합니다. 뷰는 샘플 플레이어와 동일한 `kollus.player` 템플릿을 재사용합니다.

- `KollusMultiDrmService`가 요청 User-Agent를 분석해 브라우저별 DRM 종류/스트리밍 방식을 자동 선택합니다 (Chrome·Firefox → Widevine/dash, Safari·CriOS → FairPlay/hls, 그 외 → PlayReady/dash).
- DoveRunner 라이선스 룰을 AES-256-CBC로 암호화하고 SHA-256 해시를 계산해 `custom_header`(`pallycon-customdata-v2`) 페이로드를 생성합니다.
- 일반 재생과 별도의 Kollus 계정을 사용하므로 JWT 서명/커스텀 키는 `KOLLUS_SECURITY_KEY`/`KOLLUS_CUSTOM_KEY`가 아닌 `KOLLUS_MULTIDRM_SECURITY_KEY`/`KOLLUS_MULTIDRM_CUSTOM_KEY`를 사용합니다.

#### 콘텐츠 업로드 `GET /kollus/upload`

드래그앤드롭 업로드 UI를 제공합니다.

1. `POST /kollus/upload/create-url` — 서버가 Kollus Upload API를 호출해 일회용 업로드 URL 발급 (`{ upload_url, progress_url, upload_file_key, expired_at }`)
2. 브라우저가 발급된 URL로 파일을 직접 전송(XHR, 진행률 표시)
3. 완료 후 Upload File Key 확인

**요청 파라미터** (`POST /kollus/upload/create-url`)

| 파라미터 | 설명 |
|----------|------|
| `title` | 콘텐츠 제목 (미입력 시 파일명 사용) |
| `file_name` | 원본 파일명 |
| `category_key` | 저장할 카테고리 키 (미입력 시 기본 카테고리) |
| `expire_time` | 업로드 URL 유효 시간(초), `KollusUploadService::MIN_EXPIRE_TIME`(60)~`MAX_EXPIRE_TIME`(21600), 기본 `DEFAULT_EXPIRE_TIME`(600) |
| `is_audio` | 오디오 파일 여부 |
| `is_passthrough` | 패스스루 업로드 여부 — `1`인 경우 `profile_key` 필수 |
| `profile_key` | 인코딩 프로파일 키 (패스스루 전용) |

일반 업로드와 패스스루 업로드는 서로 다른 Kollus 엔드포인트를 사용하며, `KollusUploadService`가 두 응답 포맷을 공통 구조로 정규화합니다. 입력 오류는 422, Kollus API 오류는 503으로 응답합니다.

#### JWT / 플레이어 URL 생성

`KollusJwtService`가 HS256 JWT(`cuid`, `mc`, `expt` 클레임)를 생성하고 플레이어(`/s`)와 다음 회차(`/si`) URL을 조립합니다. 콜백 응답에 필요한 JWT 인코딩도 이 서비스를 재사용합니다.

> 별도의 `/jwt/create` API 엔드포인트는 없으며, JWT 생성 로직이 `GET /kollus/player` 컨트롤러에 내장되어 있습니다. 현재 payload는 `cuid`/`mc`/`expt`만 지원하며, `seek`/`intro`/`outro`/`play_section`/`playback_rates`/`subtitle_policy` 등 확장 옵션이 필요하다면 `KollusJwtService::_buildPayload()`에 클레임을 추가해야 합니다. mc 항목별 `drm_policy`가 필요한 멀티 DRM 재생은 `KollusJwtService::buildMultiDrmPlayerUrl()`과 `KollusMultiDrmService`를 참고하세요.

#### 콜백 처리

Kollus 플레이어/서버에서 발생하는 콜백을 수신합니다. 서버 간 통신이므로 CSRF 검증에서 제외되어 있습니다(`bootstrap/app.php`).

| 경로 | 설명 | 응답 |
|------|------|------|
| `POST /kollus/play-callback` | 재생 콜백 (kind1: 만료 설정, kind3: 재생 승인) | JWT |
| `POST /kollus/drm-callback` | DRM 콜백 (다운로드 승인/완료/재생 권한) | JWT |
| `POST /kollus/next-episode-callback` | 다음 회차 콜백 (`KOLLUS_NEXT_EPISODE_MCKS`의 MCK로 SI 링크 생성) | JSON |
| `POST /kollus/lms-callback` | 시청 이력 및 학습 진도 수신 | 204 No Content |

## 관련 문서

- [Kollus API 레퍼런스](https://docs.kollus.com)
- [Kollus 관리자 콘솔](https://c-kr.kollus.com/)
- 저장소 루트 [README](../README.md) — 프레임워크별 샘플 안내
