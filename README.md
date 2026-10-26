# Kollus Sample PHP

> ⚠ **보안 안내**: PHP 보안 취약점으로 **8.2.35 / 8.3.36 / 8.4.27 / 8.5.12 이상**으로 업데이트하세요.

Kollus 동영상 서비스(플레이어 재생, 멀티 DRM, 콘텐츠 업로드, 콜백 처리) 연동을 위한 PHP 샘플 코드 모음입니다. 프레임워크별로 동일한 기능을 구현한 샘플을 제공하여, 사용 중인 스택에 맞는 연동 가이드로 참고할 수 있습니다.

## 디렉토리 구성

| 디렉토리 | 설명 |
|----------|------|
| [`laravel/`](laravel/) | Laravel 프레임워크 기반 Kollus 연동 샘플. 플레이어/멀티 DRM 재생, 콘텐츠 업로드, 재생·DRM·다음회차·LMS 콜백 처리를 포함합니다. 자세한 내용은 [laravel/README.md](laravel/README.md) 참고. |
| [`codeigniter/`](codeigniter/) | CodeIgniter 4 프레임워크 기반 Kollus 연동 샘플. Laravel 샘플과 동일한 기능을 CodeIgniter 구조로 구현했습니다. 자세한 내용은 [codeigniter/README.md](codeigniter/README.md) 참고. |
| [`archive-legacy/`](archive-legacy/) | 과거 Kollus PHP 샘플 저장소를 백업용으로 모아둔 레거시 코드입니다. 더 이상 유지보수되지 않으며, 참고용으로만 남겨두었습니다. 표준화 가이드 규칙 적용 대상이 아닙니다(보관용). |

## 어떤 샘플을 봐야 하나요?

- 현재 사용 중인 프레임워크가 **Laravel**이라면 `laravel/` 디렉토리를 참고하세요.
- 현재 사용 중인 프레임워크가 **CodeIgniter**라면 `codeigniter/` 디렉토리를 참고하세요.
- `archive-legacy/`는 신규 개발에는 사용하지 마세요. 과거 샘플(`kollus-samples-php`, `kollus-play-video-php`, `kollus-custom-upload-php`, `Kollus-MultiDrm-sample-php`)을 보관하기 위한 용도이며, 표준화 가이드 규칙 적용 대상이 아닙니다(보관용).

## 공통 사전 요구사항

- Kollus 서비스 계정 및 API Key ([Kollus 관리자 콘솔](https://c-kr.kollus.com/service-account/custom-key) 로그인 필요)
- (선택) 멀티 DRM 재생을 사용하려면 별도의 멀티 DRM 전용 Kollus 계정 키와 DoveRunner 계정 키

설치 및 실행 방법은 각 디렉토리의 README를 참고하세요.

## 관련 문서

- [Kollus API 레퍼런스](https://docs.kollus.com)
- [Kollus 관리자 콘솔](https://c-kr.kollus.com/)
