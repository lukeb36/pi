<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Module\Demo\Controller\Front;

use Exception;
use Module\Demo\Form\FileForm;
use Pi;
use Pi\Mvc\Controller\ActionController;

class FileController extends ActionController
{
    /**
     * File list
     */
    public function indexAction()
    {
        $path   = $this->getUploadPath();
        $filter = function ($fileinfo) {
            if (!$fileinfo->isFile()) {
                return false;
            }
            $filename = $fileinfo->getFilename();
            if ('index.html' == $filename) {
                return false;
            }
            return $filename;
        };
        $list   = Pi::service('file')->getList($path, $filter);
        $files  = [];
        foreach ($list as $file) {
            $files[] = [
                'filename'     => $file,
                'url_download' => $this->url('', [
                    'action' => 'download',
                    'file'   => $file,
                ]),
                'url_delete'   => $this->url('', [
                    'action' => 'delete',
                    'file'   => $file,
                ]),
            ];
        }

        $form = new FileForm;
        $form->setAttribute('action', $this->url('', ['action' => 'upload']));
        $this->view()->assign([
            'files' => $files,
            'form'  => $form,
        ]);

        $this->view()->setTemplate('file-list');
    }

    public function uploadAction()
    {
        if (!$this->request->isPost()) {
            $result = [
                'status'  => 'error',
                'message' => __('Error action.'),
            ];
        } else {
            //$rename         = '%random%';
            $rename       = '';
            $destination  = $this->getUploadPath();
            $extensions   = '';
            $maxImageSize = [];
            $maxFileSize  = 0;

            $post = $this->request->getPost();
            if ('overwrite' != $post['rename']) {
                $rename = '%random%';
            }
            $config = $this->config();
            if (!empty($config['image_extension'])) {
                $exts       = explode(',', $config['image_extension']);
                $exts       = array_filter(array_walk($exts, 'trim'));
                $extensions = implode(',', $exts);
            }
            if (!empty($config['file_max_size'])) {
                $maxFileSize = (int)$config['file_max_size'] * 1024;
            }
            if (!empty($config['image_max_width'])) {
                $maxImageSize['width'] = (int)$config['image_max_width'];
            }
            if (!empty($config['image_max_height'])) {
                $maxImageSize['height'] = (int)$config['image_max_height'];
            }
            $options                = [];
            $options['rename']      = $rename;
            $options['destination'] = $destination;
            if ($extensions) {
                $options['extension'] = $extensions;
            }
            if ($maxFileSize) {
                $options['size'] = $maxFileSize;
            }
            if ($maxImageSize) {
                $options['image_size'] = $maxImageSize;
            }
            $uploader = Pi::service('file')->upload($options);

            if ($uploader->isValid()) {
                $file   = $uploader->getUploaded();
                $result = [
                    'status'  => 'success',
                    'message' => sprintf(__('File uploaded: %s'), $file),
                ];
            } else {
                $messages = $uploader->getMessages();
                $result   = [
                    'status'  => 'error',
                    'message' => $messages ? implode('; ', $messages) : __('File not uploaded.'),
                ];
            }
        }

        $redirect = $this->url('', ['action' => 'index']);
        $this->jump($redirect, $result['message'], $result['status']);
    }

    /**
     * Download a file
     */
    public function downloadAction()
    {
        $path     = $this->getUploadPath(true);
        $filename = _get('file');
        $file     = $path . '/' . $filename;
        try {
            Pi::service('file')->download($file);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Delete a file
     */
    public function deleteAction()
    {
        $path     = $this->getUploadPath(true);
        $filename = _get('file');
        $file     = $path . '/' . $filename;
        try {
            Pi::service('file')->remove($file);
            $message = sprintf(__('File "%s" deleted.'), $filename);
            $status  = 'success';
        } catch (Exception $e) {
            $message = sprintf(
                __('File "%s" not deleted: %s.'),
                $filename,
                $e->getMessage()
            );
            $status  = 'error';
        }

        $this->jump(['action' => 'index'], $message, $status);
    }

    /**
     * Get relative path for upload
     *
     * @param bool $returnAbsolute Return absolute path
     *
     * @return string
     */
    protected function getUploadPath($returnAbsolute = false)
    {
        $path = 'upload/' . $this->getModule();
        if ($returnAbsolute) {
            $path = Pi::path($path);
        }

        return $path;
    }
}