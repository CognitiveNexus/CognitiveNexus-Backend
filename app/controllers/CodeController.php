<?php

use Docker\Docker;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\HostConfig;

class CodeController {
    public static function runCode($command) {
        ['code' => $code, 'stdin' => $stdin, 'tests' => $tests] = Flight::request()->data;

        try {
            $uuid = uuid_create();
            $projRoot = realpath(__DIR__.'/../../');
            $tmpDir = "{$projRoot}/storage/coderun-{$uuid}";
            mkdir($tmpDir, 0700);

            file_put_contents("{$tmpDir}/code.c", $code);
            file_put_contents("{$tmpDir}/stdin", $stdin);
            file_put_contents("{$tmpDir}/tests.json", json_encode($tests));

            $dockerLog = '';
            $docker = Docker::create();
            $containerConfig = new ContainersCreatePostBody();
            $containerConfig->setImage('code-runner');
            $containerConfig->setCmd(['/scripts/start.sh', $command]);
            $containerConfig->setUser(posix_getuid().':'.posix_getgid());
            $containerConfig->setAttachStdout(true);
            $containerConfig->setAttachStderr(true);
            $hostConfig = new HostConfig();
            $hostConfig->setCapDrop(['ALL']);
            $hostConfig->setSecurityOpt(['no-new-privileges']);
            $hostConfig->setNetworkMode('none');
            $hostConfig->setMemory(256 * 1024 * 1024);
            $hostConfig->setNanoCpus(200000000);
            $hostConfig->setBinds(["{$tmpDir}:/sandbox"]);
            $hostConfig->setAutoRemove(true);
            $containerConfig->setHostConfig($hostConfig);
            $container = $docker->containerCreate($containerConfig);
            $docker->containerStart($container->getId());
            $docker->containerWait($container->getId());
            $data = file_get_contents("{$tmpDir}/result.json");
            if ($data === false) {
                throw new Exception('empty result');
            }

            $result = [
                'success' => '运行完成',
                'data' => json_decode($data, true),
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
            ];
        } finally {
            $result['logs'] = [
                'compile' => file_get_contents("{$tmpDir}/compile.log"),
                'run' => file_get_contents("{$tmpDir}/run.log"),
            ];
            Flight::json($result);
            array_map('unlink', glob("{$tmpDir}/*"));
            rmdir($tmpDir);
        }

    }
}