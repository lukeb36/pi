<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

namespace Module\System\Controller\Module;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Pi\Application\Bootstrap\Resource\AdminMode;

/**
 * Module dashboard action controller
 *
 * @author Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class DashboardController extends ActionController
{
    public function permissionException()
    {
        return array('nav');
    }

    /**
     * Entry page for module admin
     *
     * Display module meta information and logo
     */
    public function indexAction()
    {
        $module = $this->params('module');
        if (!$module) {
            $this->redirect('', array('action' => 'system'));
            return;
        }

        $directory = Pi::service('module')->directory($module);
        $callback = sprintf(
            'Module\\%s\Dashboard::summary',
            ucfirst($directory)
        );
        $summary = '';
        if (is_callable($callback)) {
            $summary = call_user_func($callback, $module);
        }

        $modules = Pi::registry('modulelist')->read();
        $data = $modules[$module];
        $meta = Pi::service('module')->loadMeta($directory, 'meta');
        $author = Pi::service('module')->loadMeta($directory, 'author');
        $data['description'] = $meta['description'];
        $data['author'] = $author;
        if (empty($meta['logo'])) {
            $data['logo'] = Pi::url('static/image/module.png');
        } else {
            $data['logo'] = Pi::service('asset')->getModuleAsset(
                $meta['logo'],
                $module
            );
        }
        if (empty($data['update'])) {
            $data['update'] = __('Never updated.');
        } else {
            $data['update'] = _date($data['update']);
        }

        $this->view()->assign('summary', $summary);
        $this->view()->assign('data', $data);
        $this->view()->assign('title', __('Dashboard'));
        $this->view()->setTemplate('dashboard-module', 'system');
    }

    /**
     * Admin operation mode switch
     *
     * @return void
     */
    public function modeAction()
    {
        $mode = $this->params('mode', AdminMode::MODE_ACCESS);
        // Set run mode
        if (!empty($mode)) {
            $_SESSION['PI_BACKOFFICE'] = array(
                'mode'      => $mode,
                'changed'   => 1,
            );
        }

        $modules = Pi::registry('modulelist')->read();
        $moduleList = array_keys($modules);
        $allowed = Pi::service('permission')->moduleList($mode);
        if (null === $allowed || !is_array($allowed)) {
            $allowed = $moduleList;
        } else {
            $allowed = array_intersect($moduleList, $allowed);
        }
        if (!$allowed) {
            $this->redirect('', array('action' => 'system'));
            return;
        }

        $name = array_shift($allowed);
        $link = '';
        switch ($mode) {
            case AdminMode::MODE_ACCESS:
                $link = $this->url('admin', array(
                    'module'        => $name,
                    'controller'    => 'dashboard',
                ));
                break;
            case AdminMode::MODE_ADMIN:
                $link = $this->url('admin', array(
                    'module'        => 'system',
                    'controller'    => 'component',
                    'name'          => $name,
                ));
                break;
            case AdminMode::MODE_DEPLOYMENT:
            default:
                break;
        }
        if (!$link) {
            $this->jump(
                array('action' => 'system'),
                __('No permitted operation available.'),
                'error'
            );

            return;

        }
        $this->redirect()->toUrl($link);

        return;
    }

    /**
     * Default action for site admin entry
     *
     * @return void
     */
    public function systemAction()
    {
        $uid  = Pi::service('user')->getId();

        $_SESSION['PI_BACKOFFICE'] = array(
            'mode'      => '',
            'changed'   => 1,
            'component' => '',
            'module'    => '',
        );

        // Fetch all permitted modules
        $modules = Pi::registry('modulelist')->read('active');
        $modulesPermitted = Pi::service('permission')->moduleList('admin');
        foreach (array_keys($modules) as $name) {
            if (null !== $modulesPermitted
                && !in_array($name, $modulesPermitted)
            ) {
                unset($modules[$name]);
            }
        }

        // Get module summary callbacks
        // Get hidden modules
        $summaryList = array();
        $list = (array) Pi::user()->data->get($uid, 'module-summary');

        $summaryEnabled = array();
        $summaryHidden = array();
        // Enabled explicitly
        if (isset($list['active'])) {
            $summaryEnabled = array_intersect(
                (array) $list['active'],
                array_keys($modules)
            );
            $summaryEnabled = array_unique($summaryEnabled);
        }
        // Disabled explicitly
        if (isset($list['inactive'])) {
            $summaryHidden = array_intersect(
                (array) $list['inactive'],
                array_keys($modules)
            );
            $summaryHidden = array_unique($summaryHidden);
        }
        $new = $list
            ? array_diff(array_keys($modules), $summaryEnabled, $summaryHidden)
            : array_keys($modules);
        $keys = array_unique($summaryEnabled + $new);

        foreach ($keys as $name) {
            $callback = sprintf(
                'Module\\%s\Dashboard::summary',
                ucfirst($modules[$name]['directory'])
            );
            if (is_callable($callback)) {
                $summaryList[] = array(
                    'name'      => $name,
                    'content'   => call_user_func($callback, $name),
                    'title'     => $modules[$name]['title'],
                    'logo'      => $modules[$name]['logo'],
                    'active'    => 1
                );
            }
        }
        foreach ($summaryHidden as $name) {
            $callback = sprintf(
                'Module\\%s\Dashboard::summary',
                ucfirst($modules[$name]['directory'])
            );
            if (is_callable($callback)) {
                $summaryList['inactive'][] = array(
                    'name'      => $name,
                    'title'     => $modules[$name]['title'],
                    'active'    => 0
                );
            }
        }

        // Get user quick links
        $links = (array) Pi::user()->data->get($uid, 'admin-link');

        // Get system message, only admins have access
        $content = Pi::user()->data(0, 'admin-message', true);
        if (!$content) {
            $content = Pi::user()->data(0, 'admin-welcome', true);
        }

        $message = array(
            'time'      => _date($content['time']),
            'content'   => Pi::service('markup')->render(
                $content['value'],
                'text'
            ),
        );
        $messagePerm = false;
        if (Pi::service('user')->getUser()->isAdmin()) {
            $messagePerm = true;
        }

        $this->view()->assign('summaryList', $summaryList);
        $this->view()->assign('links', $links);
        $this->view()->assign('message', $message);
        $this->view()->assign('messagePerm', $messagePerm);

        $this->view()->assign('title', __('Dashboard'));
        $this->view()->setTemplate('dashboard-system', 'system');
    }

    /**
     * AJAX method for module admin entries
     *
     * @return int
     */
    public function entryAction()
    {
        $this->saveAjax('module-admin');

        return 1;
    }

    /**
     * AJAX method for module summary list
     *
     * @return int
     */
    public function summaryAction()
    {
        $this->saveAjax('module-summary');

        return 1;
    }

    /**
     * AJAX method for adding a module summary
     *
     * @return int
     */
    public function getSummaryAction()
    {
        $this->saveAjax('module-summary');
        $name = $this->params()->fromPost('name');

        $directory = Pi::service('module')->directory($name);
        $callback = sprintf(
            'Module\\%s\Dashboard::summary',
            ucfirst($directory)
        );
        if (is_callable($callback)) {
            $content = call_user_func($callback, $name);
        } else {
            $content = '';
        }

        return $content;
    }

    /**
     * AJAX method for quick links
     *
     * @return int
     */
    public function linkAction()
    {
        $this->saveAjax('admin-link');

        return 1;
    }

    /**
     * AJAX method for system message
     *
     * @return int
     */
    public function messageAction()
    {
        $name = 'admin-message';

        $content = $this->params()->fromPost('content');
        if (Pi::service('permission')->isAdmin()) {
            Pi::user()->data->set(0, $name, $content);
        }

        if ($content) {
            $data = array(
                'value' => $content,
                'time'  => time(),
            );
        } else {
            $data = Pi::user()->data->get(0, 'admin-welcome', true);
        }

        $message = array(
            'time'      => _date($data['time']),
            'content'   => Pi::service('markup')->render(
                $data['value'],
                'text'
            ),
        );

        return $message;
    }

    /**
     * AJAX method for personal memo
     *
     * @return array
     */
    public function memoAction()
    {
        $type = 'admin-memo';
        $uid  = Pi::service('user')->getId();

        $content = $this->params()->fromPost('content');
        $data = array(
            'value' => $content,
            'time'   => time(),
        );
        Pi::user()->data->set($uid, $type, $content);

        $memo = array(
            'time'      => _date($data['time']),
            'content'   => $data['value'],
        );

        return $memo;
    }

    /**
     * Save data submitted from AJAX
     *
     * @param string $type
     * @return bool
     */
    protected function saveAjax($type)
    {
        $uid  = Pi::service('user')->getId();
        $content = $this->params()->fromPost('content');
        Pi::user()->data->set($uid, $type, $content);

        return true;
    }
}
