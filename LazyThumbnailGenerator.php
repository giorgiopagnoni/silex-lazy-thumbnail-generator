<?php

namespace Cybits\Silex\Provider;


use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ServiceProviderInterface;

class LazyThumbnailGenerator implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        //Set the input directory for using with route
        $app['lazy.thumbnail.mount_paths'] = array(
            '/images' => array(
                'allowed_ext' => 'jpg,jpeg,png',
                'allowed_size' => array('*.*'),
                'max_size' => '512.512',
                'on_the_fly' => true
            )
        );
        //This is normally correct, but you can customize it
        $app['lazy.thumbnail.web_root'] = dirname($_SERVER['SCRIPT_FILENAME']);
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        $paths = $app['lazy.thumbnail.mount_paths'];
        foreach ($paths as $route => $data) {
            $app->mount($route, $this->connect($app, $route, $data));
        }
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app        An Application instance
     * @param string      $route      the current route
     * @param array       $parameters array of parameter for current directory
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app, $route, $parameters)
    {
        /** @var $controllers ControllerCollection */
        $controllers = $app['controllers_factory'];
        if (isset($app['logger']) && $app['logger'] instanceof LoggerInterface) {
            $logger = array($app['logger'], 'log');
        } else {
            $logger = function () {
            };
        }
        $controllers->match(
            '/{arguments}',
            '\\Cybits\\Silex\\Provider\\Controller\\GeneratorController::generateAction'
        )
            ->assert('arguments', '.*')
            ->convert(
                'arguments',
                function ($arguments) use ($app, $route, $parameters, $logger) {
                    $pattern = explode('/', $arguments, 2);
                    if (count($pattern) != 2 || !preg_match('/^([0-9]*)x([0-9]*)$/', $pattern[0], $matches)) {
                        $logger(Logger::ERROR, "Invalid call, need the '([0-9]*)x([0-9]*)/image.jpg'");
                        $app->abort(404);

                        //Stupid IDE
                        return false;
                    }
                    $file = $pattern[1];
                    $path = $app['lazy.thumbnail.web_root'] . $route . '/' . $file;
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $allowedExt = array_map('trim', explode(',', strtolower($parameters['allowed_ext'])));
                    if (!file_exists($path) ||
                        !in_array($ext, $allowedExt)
                    ) {
                        $logger(Logger::ERROR, "The $path is invalid or its not allowed image.");
                        $app->abort(404);
                    }

                    if (preg_match('/^([0-9]*)\.([0-9]*)$/', $parameters['max_size'], $maxSize)) {
                        if ($maxSize[0] < $matches[0] || $maxSize[1] < $matches[1]) {
                            $logger(Logger::ERROR, "The maximum size is reached.");
                            $app->abort(404);
                        }
                    }
                    $size = $parameters['allowed_size'];
                    foreach ($size as $wDotH) {
                        $wDotH = explode('.', $wDotH);
                        if (
                            count($wDotH) == 2 &&
                            ($wDotH[0] == '*' || $wDotH[0] == $matches[1]) &&
                            ($wDotH[1] == '*' || $wDotH[1] == $matches[2])
                        ) {
                            return array(
                                'file' => $path,
                                'width' => $matches[1],
                                'height' => $matches[2],
                                'on_the_fly' => isset($parameters['on_the_fly']) ? $parameters['on_the_fly'] : true,
                                'web_root' => $app['lazy.thumbnail.web_root'],
                                'mount' => $route,
                                'logger' => $logger
                            );
                        }
                    }

                    $logger(Logger::ERROR, "Not allowed size.");
                    $app->abort(404);

                    //Stupid IDE :)
                    return false;
                }
            );

        return $controllers;
    }
}