<?php

namespace Cybits\Silex\Provider\Controller;


use Monolog\Logger;
use PHPImageWorkshop\ImageWorkshop;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GeneratorController
{
    public function generateAction(Application $app, Request $req, $arguments)
    {
        $expectedWidth = $arguments['width'];
        $expectedHeight = $arguments['height'];

        $largestSide = max($expectedWidth, $expectedHeight);
        $base = ImageWorkshop::initFromPath($arguments['file']);
        $base->resizeInPixel($arguments['width'], $arguments['height'], false);
        $fileName = basename($arguments['file']);
        if (!$arguments['on_the_fly']) {
            $folder = $arguments['web_root'] . $arguments['mount'] .
                '/' . $arguments['width'] . 'x' . $arguments['height'];

            $base->save($folder, $fileName, true);
            $arguments['logger'](Logger::DEBUG, "File saved in '$folder/$fileName'");
        }

        $ext = strtolower(pathinfo($arguments['file'], PATHINFO_EXTENSION));
        if ($ext == 'jpg') {
            $ext = 'jpeg';
        }
        $mimeType = 'image/' . $ext;
        $func = 'image' . $ext;
        if (!function_exists($func)) {
            $arguments['logger'](Logger::CRITICAL, "How this possible?");
            $app->abort(404);
        }

        //I don't know any way to pass an image resource to symfony Response object.
        ob_start();
        $func($base->getResult());
        $result = ob_get_clean();
        return new Response(
            $result,
            200,
            array(
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'filename="'. $fileName . '"'
            )
        );

    }
}
