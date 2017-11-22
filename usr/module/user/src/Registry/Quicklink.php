<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 * @package         Registry
 */

namespace Module\User\Registry;

use Pi;
use Pi\Application\Registry\AbstractRegistry;

/**
 * Pi user quicklink registry
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Quicklink extends AbstractRegistry
{
    /** @var string Module name */
    protected $module = 'user';

    /**
     * {@inheritDoc}
     */
    protected function loadDynamic($options = [])
    {
        $list = [];

        $model  = Pi::model('quicklink', $this->module);
        $where  = ['active' => 1, 'display > 0'];
        $select = $model->select()->where($where)->order('display');
        $rowset = $model->selectWith($select);
        foreach ($rowset as $row) {
            $list[$row['name']] = [
                'title'  => $row['title'],
                'module' => $row['module'],
                'icon'   => $row['icon'],
                'link'   => $row['link'],
            ];
        }

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function read()
    {
        $options = [];
        $result  = $this->loadData($options);

        return $result;
    }

    /**
     * {@inheritDoc}
     * @param string $name
     */
    public function create($name = '')
    {
        $this->clear('');
        $this->read($name);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function setNamespace($meta = '')
    {
        return parent::setNamespace('');
    }

    /**
     * {@inheritDoc}
     */
    public function flush()
    {
        return $this->clear('');
    }
}
