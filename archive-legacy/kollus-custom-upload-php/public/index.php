<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

use Slim\Http\Request;
use Slim\Http\Response;

session_start();

$kollusConfig = [];
$configFilePath = __DIR__ . '/../config.yml';
if (file_exists($configFilePath)) {
    $yamlParser = new \Symfony\Component\Yaml\Parser();
    $parser = $yamlParser->parse(file_get_contents($configFilePath));
    $kollusConfig = $parser['kollus'];
}

// Instantiate the app
$settings = [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],
        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'kollus' => $kollusConfig,
    ],
];
$app = new \Slim\App($settings);

$container = $app->getContainer();

$container['kollusApiClient'] = function ($c) {
    $settings = $c->get('settings')['kollus'];

    $apiClient = null;
    if (isset($settings['domain']) &&
        isset($settings['version']) &&
        isset($settings['service_account']['key']) &&
        isset($settings['service_account']['api_access_token'])
    ) {
        // Get API Client
        $apiClient = new \Kollus\Component\Client\ApiClient($settings['domain'], $settings['version']);
        $serviceAccount = new \Kollus\Component\Container\ServiceAccount($settings['service_account']);
        $apiClient->setServiceAccount($serviceAccount);
        if (isset($settings['api_domain'])) {
            $apiClient->setApiDomain($settings['api_domain']);
        }
        if (isset($settings['upload_api_domain'])) {
            $apiClient->setUploadApiDomain($settings['upload_api_domain']);
        }
//        $apiClient->setSchema('https');
        $apiClient->connect();
    }

    return $apiClient;
};

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// Routes
$app->get('/', function (Request $request, Response $response) use ($container) {
    $kollusSettings = $this->settings['kollus'];
    $kollusApiClient = $container->get('kollusApiClient');
    /** @var \Kollus\Component\Client\ApiClient $kollusApiClient */

    $existsConfig = !empty($kollusSettings);

    $data = [
        'existsConfig' => $existsConfig,
        'kollus' => $kollusSettings,
        'categories' => [],
        'upload_files' => [],
    ];

    if ($kollusApiClient instanceof \Kollus\Component\Client\ApiClient) {
        $data['categories'] = $kollusApiClient->getCategories();
    }

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $data);
});

$app->post('/api/upload/create_url', function (Request $request, Response $response) use ($container) {
    $kollusApiClient = $container->get('kollusApiClient');
    /** @var \Kollus\Component\Client\ApiClient $kollusApiClient */

    $postParams = $request->getParsedBody();

    $categoryKey = empty($postParams['category_key']) ? null : $postParams['category_key'];
    $isEncryptionUpload = empty($postParams['use_encryption']) ? null : $postParams['use_encryption'];
    $isAudioUpload = empty($postParams['is_audio_upload']) ? null : $postParams['is_audio_upload'];
    $title = empty($postParams['title']) ? null : $postParams['title'];

    $apiResponse = $kollusApiClient->getUploadURLResponse(
        $categoryKey,
        $isEncryptionUpload,
        $isAudioUpload,
        $title
    );

    return $response->withJson(['result' => $apiResponse], 200);
})->setName('api-upload-create-url');

$app->get('/api/upload_file', function (Request $request, Response $response) use ($container) {
    $kollusApiClient = $container->get('kollusApiClient');
    /** @var \Kollus\Component\Client\ApiClient $kollusApiClient */

    $result = $kollusApiClient->findUploadFilesByPage(1, ['per_page' => 10]);
    $uploadFiles = $result->items;
    /** @var \Kollus\Component\Container\UploadFile[] $uploadFiles */

    $auto_reload = false;
    $per_page = $result->per_page;
    $count = $result->count;
    $items = [];
    foreach ($uploadFiles as $uploadFile) {
        if (in_array($uploadFile->getTranscodingStage(), [0, 1, 12])) {
            $auto_reload = true;
        }

        $items[] = [
            'upload_file_key' => $uploadFile->getUploadFileKey(),
            'media_content_id' => $uploadFile->getMediaContentId(),
            'title' => $uploadFile->getTitle(),
            'transcoding_stage' => $uploadFile->getTranscodingStage(),
            'transcoding_stage_name' => $uploadFile->getTranscodingStageName(),
            'transcoding_progress' => $uploadFile->getTranscodingProgress(),
            'created_at' => $uploadFile->getCreatedAt(),
            'transcoded_at' => $uploadFile->getTranscodedAt(),
        ];
    }

    return $response->withJson(compact('per_page', 'count', 'items', 'auto_reload'), 200);
})->setName('api-upload-file');

// Run app
$app->run();
