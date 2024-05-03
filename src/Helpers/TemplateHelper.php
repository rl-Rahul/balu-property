<?php

/**
 * This file is part of the Balu 2.0 package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Helpers;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Template Manager
 *
 * Template manager actions.
 *
 * @package         Balu property app 2
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class TemplateHelper
{
    /**
     * @var Environment $twig
     */
    private Environment $twig;

    /**
     * Constructor
     *
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Function to render email template
     *
     * @param string $fileName
     * @param array $params
     * @return string
     * @throws SyntaxError When an error occurred during compilation
     * @throws RuntimeError When an error occurred during rendering
     * @throws LoaderError When the template cannot be found
     */
    public function renderEmailTemplate(string $fileName, array $params = []): string
    {
        $fileName = 'Email/' . $fileName . '.html.twig';
        return $this->twig->render($fileName, $params);
    }
}