# Kollus CodeIgniter Player — 플레이어 재생·콘텐츠 업로드·콜백 처리 샘플

## 개요

Kollus 동영상 플레이어를 CodeIgniter 4 프레임워크로 통합한 샘플 프로젝트입니다. 플레이어 재생, 멀티 DRM(inkaDRM/Pallycon) 재생, 콘텐츠 업로드, 재생/DRM/다음회차/LMS 콜백 처리까지 Kollus 연동에 필요한 서버 사이드 로직을 포함합니다.

- **플레이어 재생** — `media_content_key`로 JWT를 발급해 플레이어 iframe을 렌더링 (`GET /kollus/player`)
- **멀티 DRM 재생** — inkaDRM/Pallycon `drm_policy`를 조립해 Widevine/PlayReady/FairPlay 재생 (`GET /kollus/player/multi-drm`)
- **콘텐츠 업로드** — Kollus Upload API로 일회용 업로드 URL을 발급하고 브라우저가 파일을 직접 전송 (`GET /kollus/upload`)
- **콜백 처리** — play / drm / next-episode / lms 콜백 수신 및 JWT·JSON 응답

## 사전 요구사항

- PHP 8.2 이상 / Composer
- Kollus 서비스 계정 및 API Key ([Kollus 관리자 콘솔](https://c-kr.kollus.com/service-account/custom-key) 로그인 필요)
- (선택) 멀티 DRM 재생을 사용하려면 별도의 멀티 DRM 전용 Kollus 계정 키와 Pallycon(inkaDRM) 계정 키

## 환경 설정

### 1. 의존성 설치

```bash
composer install
```

### 2. 환경변수 설정

```bash
cp env .env
```

> CodeIgniter 4는 설정 템플릿 파일명을 확장자 없는 `env`로 두는 프레임워크 관례를 따르며, 복사한 `.env`만 런타임에 로드됩니다.

`.env` 파일에서 Kollus 관련 값을 입력합니다.

| 키 | 설명 | 위치 |
|----|------|------|
| `KOLLUS_SECURITY_KEY` | JWT 서명 키 | 서비스 계정 > 보안키 |
| `KOLLUS_CUSTOM_KEY` | 플레이어 URL 커스텀 키 | 서비스 계정 > 커스텀키 |
| `KOLLUS_ACCESS_TOKEN` | Upload API 액세스 토큰 | 서비스 계정 > API 토큰 |
| `KOLLUS_PLAYER_URL` | 플레이어 기본 URL (기본값: `https://v.kr.kollus.com/`) | - |
| `KOLLUS_TOKEN_EXPIRE_SECONDS` | JWT 만료 시간(초, 기본값: 300) | - |
| `KOLLUS_NEXT_EPISODE_MCKS` | 다음 회차 재생 샘플 미디어 콘텐츠 키 (콤마 구분 목록) | - |
| `KOLLUS_MULTIDRM_SECURITY_KEY` | 멀티 DRM 전용 JWT 서명 키 (일반 재생과 다른 별도 계정) | 멀티 DRM 서비스 계정 > 보안키 |
| `KOLLUS_MULTIDRM_CUSTOM_KEY` | 멀티 DRM 전용 플레이어 URL 커스텀 키 | 멀티 DRM 서비스 계정 > 커스텀키 |
| `KOLLUS_INKA_ACCESS_KEY` | inkaDRM(Pallycon) Access Key | DoveRunner 콘솔 |
| `KOLLUS_INKA_SITE_KEY` | inkaDRM(Pallycon) Site Key (라이선스 룰 암호화용) | DoveRunner 콘솔 |
| `KOLLUS_INKA_SITE_ID` | inkaDRM(Pallycon) Site ID | DoveRunner 콘솔 |
| `KOLLUS_INKA_IV` | 라이선스 룰 AES-256-CBC 암호화 IV | - |
| `KOLLUS_INKA_LICENSE_URL` | DoveRunner 라이선스 서버 URL | - |
| `KOLLUS_INKA_CERTIFICATE_URL` | DoveRunner FairPlay 인증서 서버 URL | - |

`app/Config/Kollus.php`가 위 환경변수를 읽어 설정 객체로 노출하며, `app/Config/Services.php`가 이 설정으로 `kollusJwtService`, `kollusMultiDrmService`, `kollusUploadService` 등의 서비스를 조립합니다.

## 실행 방법

```bash
php spark serve
```

### 동작 확인

브라우저에서 아래 URL에 접근합니다 (`미디어콘텐츠키` 부분을 실제 미디어 콘텐츠 키로 교체):

```
http://localhost:8080/kollus/player?media_content_key=미디어콘텐츠키
```

멀티 DRM 재생은 `content_id`(멀티 DRM 콘텐츠 ID, Kollus 업로드 파일 키)를 추가로 전달합니다:

```
http://localhost:8080/kollus/player/multi-drm?media_content_key=미디어콘텐츠키&content_id=멀티DRM콘텐츠ID
```

플레이어가 정상적으로 로드되면 설정이 완료된 것입니다.

## 주요 파일

| 파일 | 설명 |
|------|------|
| `app/Controllers/Kollus/PlayerController.php` | 플레이어/멀티 DRM 플레이어 페이지 컨트롤러 |
| `app/Controllers/Kollus/UploadController.php` | 업로드 페이지 및 업로드 URL 발급 API |
| `app/Controllers/Kollus/PlayCallbackController.php` | Play 콜백 수신 |
| `app/Controllers/Kollus/DrmCallbackController.php` | DRM 콜백 수신 |
| `app/Controllers/Kollus/NextEpisodeCallbackController.php` | 다음 회차 콜백 수신 |
| `app/Controllers/Kollus/LmsCallbackController.php` | LMS 콜백 수신 |
| `app/Libraries/Kollus/KollusJwtService.php` | JWT 생성 및 플레이어 URL 조립 |
| `app/Libraries/Kollus/KollusMultiDrmService.php` | 멀티 DRM(inkaDRM/Pallycon) 재생 URL 조립 |
| `app/Libraries/Kollus/KollusUploadService.php` | Kollus Upload API 연동 |
| `app/Libraries/Kollus/KollusPlayCallbackService.php` | 재생 콜백 응답(JWT) 조립 |
| `app/Libraries/Kollus/KollusDrmCallbackService.php` | DRM 콜백 응답(JWT) 조립 |
| `app/Libraries/Kollus/KollusNextEpisodeCallbackService.php` | 다음 회차 URL 조립 |
| `app/Libraries/Kollus/KollusLmsCallbackService.php` | LMS payload 요약(시청 진도/스킵 감지) |
| `app/Libraries/Kollus/KollusLmsLogger.php` | LMS 콜백 전용 로그 기록기 |
| `app/Config/Kollus.php` | Kollus 설정 (환경변수 참조) |
| `app/Config/Services.php` | Kollus 관련 서비스(kollusJwtService 등) 팩토리 바인딩 |
| `app/Config/Routes.php` | Kollus 관련 라우트 전체 |
| `app/Config/Filters.php` | Kollus 콜백 경로 CSRF 제외 / next-episode-callback cors 필터 |
| `app/Views/kollus/player.php` | 플레이어 iframe 뷰 |
| `app/Views/kollus/upload.php` | 업로드 페이지 (드래그앤드롭 + 진행률 표시) |
| `env` | 환경변수 템플릿 (`cp env .env`로 복사해 사용) |

## API 흐름

```
클라이언트
  │
  ├── GET /kollus/player?media_content_key=...
  │     └── KollusJwtService → JWT + 플레이어 URL 반환 (iframe src)
  │
  ├── GET /kollus/player/multi-drm?media_content_key=...&content_id=...
  │     └── KollusMultiDrmService → inkaDRM drm_policy 조립 → KollusJwtService(멀티 DRM 계정) → JWT + 플레이어 URL 반환
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

`media_content_key` 쿼리 파라미터로 JWT를 발급하고 플레이어 iframe(`{player_url}s?jwt=...&custom_key=...`)을 렌더링합니다(`PlayerController::show`). 샘플에서는 `client_user_id`를 `guest`로 고정하며, 실제 서비스에서는 사용자 ID를 사용하여 서비스에 활용합니다.

#### 멀티 DRM 플레이어 `GET /kollus/player/multi-drm`

`media_content_key`(재생할 미디어 콘텐츠 키)와 `content_id`(멀티 DRM 콘텐츠 ID, Kollus 업로드 파일 키) 쿼리 파라미터를 받아 Widevine/PlayReady/FairPlay 멀티 DRM 재생용 플레이어를 렌더링합니다(`PlayerController::showMultiDrm`). 뷰는 샘플 플레이어와 동일한 `kollus/player` 템플릿을 재사용합니다.

- `KollusMultiDrmService`가 요청 User-Agent를 분석해 브라우저별 DRM 종류/스트리밍 방식을 자동 선택합니다 (Chrome·Firefox → Widevine/dash, Safari·CriOS → FairPlay/hls, 그 외 → PlayReady/dash).
- inkaDRM(Pallycon) 라이선스 룰을 AES-256-CBC로 암호화하고 SHA-256 해시를 계산해 `custom_header`(`pallycon-customdata-v2`) 페이로드를 생성합니다.
- 일반 재생과 별도의 Kollus 계정을 사용하므로 JWT 서명/커스텀 키는 `KOLLUS_SECURITY_KEY`/`KOLLUS_CUSTOM_KEY`가 아닌 `KOLLUS_MULTIDRM_SECURITY_KEY`/`KOLLUS_MULTIDRM_CUSTOM_KEY`를 사용합니다.

#### 콘텐츠 업로드 `GET /kollus/upload`

드래그앤드롭 업로드 UI를 제공합니다.

1. `POST /kollus/upload/create-url` — 서버(`UploadController::createUrl`)가 Kollus Upload API를 호출해 일회용 업로드 URL 발급 (`{ upload_url, progress_url, upload_file_key, expired_at }`)
2. 브라우저가 발급된 URL로 파일을 직접 전송(XHR, 진행률 표시)
3. 완료 후 Upload File Key 확인

**요청 파라미터** (`POST /kollus/upload/create-url`)

| 파라미터 | 설명 |
|----------|------|
| `title` | 콘텐츠 제목 (미입력 시 파일명 사용) |
| `file_name` | 원본 파일명 |
| `category_key` | 저장할 카테고리 키 (미입력 시 기본 카테고리) |
| `expire_time` | 업로드 URL 유효 시간(초), 60~21600 |
| `is_audio` | 오디오 파일 여부 |
| `is_passthrough` | 패스스루 업로드 여부 — `1`인 경우 `profile_key` 필수 |
| `profile_key` | 인코딩 프로파일 키 (패스스루 전용) |

일반 업로드와 패스스루 업로드는 서로 다른 Kollus 엔드포인트를 사용하며, `KollusUploadService`가 두 응답 포맷을 공통 구조로 정규화합니다.

#### JWT / 플레이어 URL 생성

`KollusJwtService`가 HS256 JWT(`cuid`, `mc`, `expt` 클레임)를 생성하고 플레이어(`/s`)와 다음 회차(`/si`) URL을 조립합니다. 콜백 응답에 필요한 JWT 인코딩도 이 서비스를 재사용합니다.

> 별도의 `/jwt/create` API 엔드포인트는 없으며, JWT 생성 로직이 `GET /kollus/player` 컨트롤러에 내장되어 있습니다. 현재 payload는 `cuid`/`mc`/`expt`만 지원하며, `seek`/`intro`/`outro`/`play_section`/`playback_rates`/`subtitle_policy` 등 확장 옵션이 필요하다면 `KollusJwtService::_buildPayload()`에 클레임을 추가해야 합니다. mc 항목별 `drm_policy`가 필요한 멀티 DRM 재생은 `KollusJwtService::buildMultiDrmPlayerUrl()`과 `KollusMultiDrmService`를 참고하세요.

일반 재생용 JWT는 `KOLLUS_SECURITY_KEY`/`KOLLUS_CUSTOM_KEY`로 서명하는 `kollusJwtService` 인스턴스를 사용하고, 멀티 DRM 재생은 `KOLLUS_MULTIDRM_SECURITY_KEY`/`KOLLUS_MULTIDRM_CUSTOM_KEY`로 서명하는 별도의 `kollusMultiDrmJwtService` 인스턴스를 사용합니다(`app/Config/Services.php`).

#### 콜백 처리

Kollus 플레이어/서버에서 발생하는 콜백을 수신합니다. 서버 간 통신이므로 CSRF 검증에서 제외되어 있습니다(`app/Config/Filters.php`의 `csrf` 필터 `except`).

| 경로 | 설명 | 응답 |
|------|------|------|
| `POST /kollus/play-callback` | 재생 콜백 (kind1: 만료 설정, kind3: 재생 승인) | JWT |
| `POST /kollus/drm-callback` | DRM 콜백 (다운로드 승인/완료/재생 권한) | JWT |
| `POST /kollus/next-episode-callback` | 다음 회차 콜백 | JSON |
| `POST /kollus/lms-callback` | 시청 이력 및 학습 진도 수신 | 204 No Content |

`next-episode-callback`은 Kollus 플레이어(iframe, 별도 origin)의 JS가 브라우저에서 직접 호출하는 크로스 오리진 요청이므로 `cors` 필터가 적용되며, preflight(OPTIONS) 요청도 함께 라우팅되어 있습니다(`app/Config/Routes.php`).

## 관련 문서

- [Kollus API 레퍼런스](https://docs.kollus.com)
- [Kollus 관리자 콘솔](https://c-kr.kollus.com/)
