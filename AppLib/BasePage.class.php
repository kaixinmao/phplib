<?php
/**
 * 页面基础page类
 */
class BasePage extends Yaf_Controller_Abstract
{
    protected $_user_id = NULL;
    protected $_user_ext_data = NULL;


    protected $_is_ajax = FALSE;
    protected $_is_post = FALSE;

    protected $_base_view_path = NULL;

    //视图加载数据时使用的变量
    protected $_data = array();

    public function init()
    {
        $this->_initSession();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
        ) {
            $this->_is_ajax = TRUE;
        }

        if (!empty($_POST)) {
            $this->_is_post = TRUE;
        }

        if (defined('ROOT_APP')) {
            $this->_base_view_path = ROOT_APP . '/views';
        }
    }

    /**
     * 初始化用户会话信息
     * 填充_user_id和_user_ext_data信息
     */
    protected function _initSession()
    {
        return;
    }


    protected function _domain()
    {
        $domain = $_SERVER['HTTP_HOST'];
        $port_pos = strpos($domain, ':');
        if ($port_pos !== FALSE) {
            $domain = substr($domain, 0, $port_pos);
        }

        return $domain;
    }

    protected function _requestUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * 获得分页偏移量
     */
    protected function _getOffsetParam()
    {
        $offset = @$_GET['offset'];
        if (is_numeric($offset)) {
            return (int) $offset;
        }

        $offset = @$_POST['offset'];
        
        if (is_numeric($offset)) {
            return (int) $offset;
        }

        return 0;
    }


    /**
     * 根目录为视图目录/singlepage
     */
    public function renderSingle($view_path, $tpl_vars = NULL)
    {
        return $this->getView()->renderSingle($view_path, $tpl_vars);
    }

    /**
     * 根目录为视图目录/singlepage
     */
    public function displaySingle($view_path, $tpl_vars = NULL)
    {
        $this->getView()->displaySingle($view_path, $tpl_vars);
        return;
    }

    public function getRedirectUri()
    {
        $redirect_uri = @$_REQUEST['redirect_uri'];
        return $redirect_uri;
    }

    /**
     * 在参数中覆盖redirect_uri
     */
    public function setRedirectUri($redirect_uri)
    {
        $_REQUEST['redirect_uri'] = $redirect_uri;
    }

    /**
     * 页面跳转，默认302跳转
     */
    public function redirect($redirect_uri = NULL, $code = 302)
    {
        if (empty($redirect_uri)) {
            $redirect_uri = $this->getRedirectUri();
        }

        if (empty($redirect_uri)) {
            return;
        }

        Helper_Http::redirect($redirect_uri, $code);
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
     * 返回目标view和可能存在的__base.tpl.php
     */
    private function __getViewPath($path, $suffix_path = '', $scan_base_view = FALSE)
    {
        $prefix_view_path = $this->_base_view_path;
        if ($suffix_path) {
            $prefix_view_path .= '/' . $suffix_path;
        }

        $path = trim($path, '/');
        $view_path = $prefix_view_path . '/' . $path . '.tpl.php';
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
                    $base_view_path .= implode('/', $segs) . '/__base.tpl.php';
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
