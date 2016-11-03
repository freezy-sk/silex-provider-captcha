<?php

/*
 * This file is part of the fabschurt/silex-provider-captcha package.
 *
 * (c) 2016 Fabien Schurter <fabien@fabschurt.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FabSchurt\Silex\Provider\Captcha;

use FabSchurt\Silex\Provider\Captcha\Form\Type\CaptchaType;
use FabSchurt\Silex\Provider\Captcha\Service\Captcha;
use FabSchurt\Silex\Provider\Captcha\Service\CaptchaBuilderFactory;
use Gregwar\Captcha\PhraseBuilder;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Fabien Schurter <fabien@fabschurt.com>
 */
final class CaptchaServiceProvider implements ServiceProviderInterface, BootableProviderInterface, ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Container $container)
    {
        // Dependencies
        if (!isset($container['session'])) {
            throw new \RuntimeException(
                'You must register SessionServiceProvider before being able to use this provider.'
            );
        }

        // Parameters
        $container['captcha.url']         = '/captcha';
        $container['captcha.route_name']  = 'captcha';
        $container['captcha.session_key'] = 'captcha.current';
        $container['captcha.width']       = 120;
        $container['captcha.height']      = 32;
        $container['captcha.quality']     = 90;

        // Services
        $container['captcha'] = function (Container $container) {
            return new Captcha(
                $container['captcha.builder_factory'],
                $container['session'],
                $container['captcha.session_key'],
                $container['captcha.width'],
                $container['captcha.height'],
                $container['captcha.quality']
            );
        };
        $container['captcha.builder_factory'] = function (Container $container) {
            return new CaptchaBuilderFactory($container['captcha.phrase_builder']);
        };
        $container['captcha.phrase_builder'] = function (Container $container) {
            return new PhraseBuilder();
        };

        // Plug-ins
        if (isset($container['form.factory'])) {
            $container['form.types'] = $container->extend(
                'form.types',
                function (array $formTypes, Container $container) {
                    $types[] = new CaptchaType(
                        $container['captcha'],
                        $container['url_generator']->generate(
                            $container['captcha.route_name'],
                            ['ts' => microtime()]
                        ),
                        $container['captcha.width'],
                        $container['captcha.height']
                    );

                    return $types;
                }
            );
            if (isset($container['twig'])) {
                $container['twig.path'] = array_merge(
                    $container['twig.path'],
                    [__DIR__.'/Resources/views']
                );
                $container['twig.form.templates'] = array_merge(
                    $container['twig.form.templates'],
                    ['captcha_block.html.twig']
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        $app->mount('', $this);
    }

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->get($app['captcha.url'], function (Application $app) {
            return new Response(
                $app['captcha']->generate(),
                Response::HTTP_OK,
                ['Content-Type' => 'image/jpeg']
            );
        })->bind($app['captcha.route_name']);

        return $controllers;
    }
}
