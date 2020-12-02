<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Pi\Filter\File;

use Closure;
use Laminas\Filter\File\Rename as LaminasRename;

/**
 * File name filter
 *
 * Rename a uploaded file with renaming strategy:
 *
 * - Specified name: use specified string as new filename
 * - Closure for generating name: use callable Closure to generate new filename
 * - Replace pattern for generating name: following tags are supported:
 *  - %term%: file source name
 *  - %source%: file source name
 *  - %random%: unique text generated by {@uniqid()}
 *  - %date:l%: long date format generated by {@date('YmdHis')}
 *  - %date:m%: medium date format generated by {@date('Ymd')}
 *  - %date:s%: short date format generated by {@date('Ym')}
 *  - %time%: time stamp generated by {@time()}
 *  - %microtime%: time stamp in micro-seconds generated by {@microtime()}
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Rename extends LaminasRename
{
    /**
     * File source attributes
     * @var array
     */
    protected $source;

    /** @var  Closure */
    protected $closure;

    /** @var string */
    const CLOSURE_TOKEN = '__CLOSURE__';

    /**
     * Class constructor
     *
     * {@inheritDoc}
     * @param  string|Closure|array|Traversable $options
     *      Target file or directory to be renamed
     */
    public function __construct($options)
    {
        if ($options instanceof Closure) {
            $options = ['target' => $options];
        }

        parent::__construct($options);
    }

    /**
     * {@inheritDoc}
     */
    protected function _convertOptions($options)
    {
        if (isset($options['target']) && $options['target'] instanceof Closure) {
            $this->closure     = $options['target'];
            $options['target'] = static::CLOSURE_TOKEN;
        } else {
            $this->closure = null;
        }
        parent::_convertOptions($options);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function filter($value)
    {
        if (is_array($value)) {
            $this->setSource($value);
        }

        return parent::filter($value);
    }

    /**
     * {@inheritDoc}
     */
    protected function _getFileName($file)
    {
        $rename = parent::_getFileName($file);
        $this->parseStrategy($rename);

        return $rename;
    }

    /**#@+
     * Added by Taiwen Jiang
     */
    /**
     * Generate upload target by parsing specified file target strategy
     *
     * @param array $file
     * @return void
     */
    protected function parseStrategy(&$file)
    {
        if (is_array($this->source)
            && $this->source['tmp_name'] == $file['source']
        ) {
            $name = $this->source['name'];
        } else {
            $name = $file['source'];
        }
        if (static::CLOSURE_TOKEN == $file['target'] && $this->closure) {
            $closure        = $this->closure;
            $file['target'] = $closure($name);
        } elseif (false !== strpos($file['target'], '%')) {
            $extension      = pathinfo($name, PATHINFO_EXTENSION);
            $terms          = [
                '%source%'    => $name,
                '%term%'      => $name,
                '%random%'    => uniqid(),
                '%date:l%'    => date('YmdHis'),
                '%date:m%'    => date('Ymd'),
                '%date:s%'    => date('Ym'),
                '%time%'      => time(),
                '%microtime%' => microtime(),
            ];
            $file['target'] = str_replace(
                    array_keys($terms),
                    array_values($terms),
                    $file['target']
                ) . '.' . $extension;
        }
    }

    /**
     * Set resource
     *
     * @param array $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }
    /**#@-*/
}
