<?php
/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @author        Ian den Hartog (https://iandh.nl)
 * @link          https://iandh.nl
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace SendgridEmail;

use Cake\Core\BasePlugin;

class Plugin extends BasePlugin
{
    /**
     * Load routes or not
     *
     * @var bool
     */
    protected $routesEnabled = false;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected $middlewareEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected $consoleEnabled = false;
}
