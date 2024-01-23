<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // 允许所有来源进行跨域请求
header("Access-Control-Allow-Methods: *"); // 允许 POST 方法
header("Access-Control-Allow-Headers: Content-Type"); // 允许 Content-Type 头部

// 对于预检请求，结束脚本执行并返回 200 状态码
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


switch ($_GET['action']) {
    case 'list':
        checkMethod();
        jlist();
        break;
    case 'show':
        checkMethod();
        show();
        break;
    case 'logs':
        checkMethod();
        logs();
        break;
    case 'save':
        checkMethod('POST');
        save();
        break;
    case 'start':
        checkMethod('POST');
        start();
        break;
    case 'stop':
        checkMethod('POST');
        stop();
        break;
    case 'del':
        checkMethod('DELETE');
        del();
        break;
    case 'create':
        checkMethod('POST');
        create();
        break;
    default:
        failed('Invalid action');
}

function checkMethod($method = 'GET')
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }
}

function checkParam($param)
{
    if (!isset($_GET[$param])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing parameter: " . $_GET[$param]]);
        exit;
    }
    return $_GET[$param];
}

function failed($error)
{
    http_response_code(500);
    echo json_encode(['error' => $error]);
    exit;
}

function success($data = ['status' => 'success'])
{
    http_response_code(200);
    echo json_encode($data);
    exit;
}

function jlist()
{
    exec('sudo -u itk pm2 jlist', $output, $return_var);
    if ($return_var !== 0) {
        failed("Error code: $return_var");
    }

    $data = [];
    $out = json_decode($output[0], true);
    for ($i = 0; $i < count($out); $i++) {
        $item = $out[$i];
        $pm2env = $item['pm2_env'];
        $data[] = [
            'id' => $item['pm_id'],
            'name' => $item['name'],
            'namespace' => $pm2env['namespace'],
            'mode' => $pm2env['exec_mode'],
            'instances' => $pm2env['instances'],
            'uptime' => $pm2env['pm_uptime'],
            'created_at' => $pm2env['created_at'],
            'restarts' => $pm2env['restart_time'],
            'status' => $pm2env['status'],
            'cpu' => $item['monit']['cpu'],
            'mem' => $item['monit']['memory'],
            'watch' => $pm2env['watch'],
            'args' => $pm2env['args'],
            'file' => str_replace($pm2env['pm_exec_path'], '', $pm2env['pm_cwd']),
        ];
    }

    success($data);
}

function show()
{
    $name = checkParam('name');

    exec("pm2 show $name", $output, $return_var);
    if ($return_var !== 0) {
        failed("Error code: $return_var");
    }

    success($output);
}

function logs()
{
    $name = checkParam('name');
    $lines = $_GET['lines'] ?? 10;
    
    exec("pm2 logs $name --nostream --timestamp --raw --lines $lines", $output, $return_var);
    if ($return_var !== 0) {
        failed("Error code: $return_var");
    }

    success($output);
}

function save()
{
    exec("pm2 save --force", $output, $return_var);
    if ($return_var !== 0) {
        failed("Error code: $return_var");
    }

    success();
}

function start()
{
    $name = checkParam('name');

    exec("pm2 start $name", $output, $return_var);
    if ($return_var !== 0) {
        failed("Error code: $return_var");
    }

    success();
}

function stop()
{
    $name = checkParam('name');

    exec("pm2 stop $name", $output, $return_var);
    if ($return_var !== 0) {
        failed("Error code: $return_var");
    }

    success();
}

function del()
{
    $name = checkParam('name');

    exec("pm2 delete $name", $output, $return_var);
    if ($return_var !== 0) {
        failed("Error code: $return_var");
    }

    success();
}

function create()
{
    // 检查是否有文件上传
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file upload']);
        exit;
    }

    // 获取上传的 ZIP 文件信息
    $file = $_FILES['file'];

    // 解压目录
    $filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $unzipDir = './uploads/' . $filename . time() . uniqid();

    // 尝试解压缩文件到新目录
    if (unzipFileWithExec($file['tmp_name'], $unzipDir)) {
        // 解压缩成功，执行 pm2 start
        exec("cd $unzipDir && pm2 start pm2.config.js", $output, $return_var);
        if ($return_var !== 0) {
            failed("Error code: $return_var");
        }

        success();
    } else {
        // 解压缩失败
        failed('Unzip failed or directory creation failed');
    }
}

function unzipFileWithExec($zipFilePath, $targetDir)
{
    // 创建目标目录（包括所有中间目录），如果不存在的话
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // 确保 exec 函数可用并设置命令
    if (function_exists('exec') && is_executable('/usr/bin/unzip')) {
        // 使用相对路径防止路径注入攻击
        $relativeZipPath = str_replace('\\', '/', realpath($zipFilePath));
        $relativeTargetDir = str_replace('\\', '/', realpath($targetDir));

        // 执行解压命令
        $command = "/usr/bin/unzip -oqq '$relativeZipPath' -d '$relativeTargetDir'";
        exec($command, $output, $return_var);

        // 检查返回状态码，0 表示成功
        if ($return_var === 0) {
            return true;
        }
    }

    return false;
}
