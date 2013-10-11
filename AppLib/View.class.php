<?php
//yaf 自己为yaf写的视图类

class View implements Yaf_View_Interface
{
    protected $_tpl_vars = array();
    protected $_script_path = '';

    public function render($view_path, $tpl_vars = NULL)
    {
        if (empty($tpl_vars) or !is_array($tpl_vars)) {
            $tpl_vars = $this->_tpl_vars;
        }

        return $this->_render($view_path, $tpl_vars, TRUE);
    }

    public function renderSingle($view_path, $tpl_vars = NULL)
    {
        if (empty($tpl_vars) or !is_array($tpl_vars)) {
            $tpl_vars = $this->_tpl_vars;
        }
        return $this->_renderSingle($view_path, $tpl_vars, TRUE);
    }


    public function display($view_path, $tpl_vars = NULL)
    {
        echo $this->render($view_path, $tpl_vars);
    }

    public function displaySingle($view_path, $tpl_vars = NULL)
    {
        echo $this->renderSingle($view_path, $tpl_vars);
    }

    public function setScriptPath($view_directory)
    {
        if (is_dir($view_directory)) {
            $this->_script_path = $view_directory;
        }
    }

    public function getScriptPath()
    {
        return $this->_script_path;
    }

    public function assign($name, $value = NULL)
    {
        $this->_tpl_vars[$name] = $value;
    }

    public function __set($name, $value = NULL)
    {
        $this->_tpl_vars[$name] = $value;
        return TRUE;
    }

    public function __get($name)
    {
        if (isset($this->_tpl_vars[$name])) {
            return $this->_tpl_vars[$name];
        } else {
            return NULL;
        }
    }

    protected function _render($path, $data, $return_content = FALSE)
    {
        $view_paths = $this->__getViewPath($path, 'modules', TRUE);
        if (!$view_paths) {
            return;
        }
        $content = '';
        foreach ($view_paths as $vp) {
            if ($content) {
                $data['__content'] = $content;
            }
            $content = $this->__getContentByViewData($vp, $data);
        }

        if ($return_content) {
            return $content;
        } else {
            echo $content;
        }
    }

    protected function _renderSingle($path, $data, $return_content = FALSE)
    {
        $view_path = $this->__getViewPath($path, 'singlepage');
        if (!$view_path) {
            return;
        }
        $content = $this->__getContentByViewData($view_path, $data);
        if ($return_content) {
            return $content;
        } else {
            echo $content;
        }
    }

    /**
     * 返回目标view和可能存在的__base.phtml
     */
    private function __getViewPath($path, $suffix_path = '', $scan_base_view = FALSE)
    {
        $prefix_view_path = $this->_script_path;
        if ($suffix_path) {
            $prefix_view_path .= '/' . $suffix_path;
        }

        $path = trim($path, '/');
        $view_path = $prefix_view_path . '/' . $path;
        if (file_exists($view_path)) {
            $base_view_paths = array();
            if ($scan_base_view) {
                $segs = explode('/', $path);
                $depth = count($segs);
                while($depth > 0) {
                    --$depth;
                    array_pop($segs);
                    $base_view_path = $prefix_view_path;
                    if (!empty($segs)) {
                        $base_view_path .= '/';
                    }
                    $base_view_path .= implode('/', $segs) . '/__base.phtml';
                    if (file_exists($base_view_path)) {
                        $base_view_paths[] = $base_view_path;
                    }
                }
            }
            $base_view_paths[] = $view_path;
            $base_view_paths = array_reverse($base_view_paths);
            if ($scan_base_view) {
                return $base_view_paths;
            } else {
                return $view_path;
            }
        } else {
            return FALSE;
        }
    }

    private function __getContentByViewData($view, $data)
    {
        ob_start();
        extract($data);
        include($view);
        $content = ob_get_contents();
        @ob_end_clean();
        return $content;
    }
}
