<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Pi\Filter;

use Pi;
use Laminas\Filter\AbstractFilter;

/**
 * URI filter
 *
 * Transform URI to full URI
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Uri extends AbstractFilter
{
    /**
     * Filter options
     *
     * @var array
     */
    protected $options
        = [
            'allowRelative' => false,
        ];

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->setOptions($options);
    }

    /**
     * Transform text
     *
     * @param string $value
     *
     * @return string
     */
    public function filter($value)
    {
        if ($this->options['allowRelative'] || empty($value)) {
            return $value;
        }

        if (!preg_match('/^(http[s]?:\/\/|\/\/)/i', $value)) {
            $value = Pi::url('www') . '/' . ltrim($value, '/');
        }

        return $value;
    }
}
