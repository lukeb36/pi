<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 * @package         View
 */

namespace Pi\View\Helper;

use Pi;

/**
 * Helper for loading Backbone files
 *
 * Usage inside a phtml template
 *
 * ```
 *  // Load basic backbone and underscore
 *  $this->backbone();
 *
 *  // Load specific file
 *  $this->backbone('some.js');
 *
 *  // Load specific file with attributes
 *  $this->backbone('some.js',
 *      array('conditional' => '...', 'position' => 'prepend'));
 *
 *  // Load a list of files
 *  $this->backbone(array(
 *      'a.js',
 *      'b.js',
 *  ));
 *
 *  // Load a list of files with corresponding attributes
 *  $this->backbone(array(
 *      'a.js' => array('media' => '...', 'conditional' => '...'),
 *      'b.js',
 *  ));
 * ```
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Backbone extends AssetCanonize
{
    /** @var string Backbone root directory */
    const DIR_ROOT = 'vendor/backbone';

    /** @var bool Backbone basic file is loaded */
    protected static $rootLoaded;

    /**
     * Load backbone files
     *
     * @param   null|string|array $files
     * @param   array $attributes
     * @param   bool|null $appendVersion
     *
     * @return  $this
     */
    public function __invoke(
        $files = null,
        $attributes = [],
        $appendVersion = null
    )
    {
        $files = $this->canonize($files, $attributes);
        if (!static::$rootLoaded) {
            $autoLoad = [];
            // Required underscore js
            if (!isset($files['underscore-min.js'])) {
                $autoLoad += [
                    'underscore-min.js' =>
                        $this->canonizeFile('underscore-min.js'),
                ];
            }
            // Required primary js
            if (!isset($files['backbone-min.js'])) {
                $autoLoad += [
                    'backbone-min.js' => $this->canonizeFile('backbone-min.js'),
                ];
            }
            $files              = $autoLoad + $files;
            static::$rootLoaded = true;
        }

        foreach ($files as $file => $attrs) {
            $file     = static::DIR_ROOT . '/' . $file;
            $url      = Pi::service('asset')->getStaticUrl($file, $appendVersion);
            $position = isset($attrs['position'])
                ? $attrs['position'] : 'append';
            if ($attrs['ext'] == 'css') {
                $attrs['href'] = $url;
                if ('prepend' == $position) {
                    $this->view->headLink()->prependStylesheet($attrs);
                } else {
                    $this->view->headLink()->appendStylesheet($attrs);
                }
            } else {
                if ('prepend' == $position) {
                    $this->view->headScript()
                        ->prependFile($url, 'text/javascript', $attrs);
                } else {
                    $this->view->headScript()
                        ->appendFile($url, 'text/javascript', $attrs);
                }
            }
        }

        return $this;
    }
}
