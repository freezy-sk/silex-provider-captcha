<?php

/*
 * This file is part of the fabschurt/silex-provider-captcha package.
 *
 * (c) 2016 Fabien Schurter <fabien@fabschurt.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FabSchurt\Silex\Provider\Captcha\Tests;

use FabSchurt\Silex\Provider\Captcha\CaptchaServiceProvider;
use Silex\Application;
use Silex\Provider;
use Silex\WebTestCase;

/**
 * @author Fabien Schurter <fabien@fabschurt.com>
 */
abstract class AbstractTestCase extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    public function createApplication()
    {
        $app = new Application(['debug' => true]);
        $app->register(new Provider\SessionServiceProvider(), ['session.test' => true]);
        $app->register(new Provider\FormServiceProvider());
        $app->register(new Provider\ValidatorServiceProvider());
        $app->register(new Provider\LocaleServiceProvider(), ['locale' => 'fr']);
        $app->register(new Provider\TranslationServiceProvider(), [
            'translator.domains' => [
                'validators' => [
                    'fr' => [
                        'Invalid captcha value.' => 'Captcha invalide.',
                    ],
                ],
                'captcha' => [
                    'fr' => [
                        'Load a new image' => 'Charger une nouvelle image',
                    ]
                ],
            ]
        ]);
        $app->register(new Provider\TwigServiceProvider(), [
            'twig.templates' => [
                $this->getTestFormName() => '{{ form(form) }}',
            ],
        ]);
        $app->register(new CaptchaServiceProvider());
        unset($app['exception_handler']);
        $app->boot();
        $app->flush();

        return $app;
    }

    /**
     * Returns a constant test form identifier.
     *
     * @return string
     */
    protected function getTestFormName()
    {
        return 'test_form';
    }
}
