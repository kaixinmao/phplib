<?php
/**
 * Pagination
 */
class Helper_Pagination {
    //Defines two types of paging parameters
    const TYPE_PAGE   = 1;
    const TYPE_OFFSET = 2;
    
    //The template variable is {page}, like t.php?p={page}, /page/{page}
    protected $page_url_tpl = '';
    //Max pages, <= 0 means no limit.
    protected $max_pages = 0;

    protected $param_type = self::TYPE_OFFSET;

    protected $offset_param = 'offset';

    protected $page_param = 'page';

    protected $total_rows = 0;

    protected $page_size = 10;

    protected $link_tag  = '<li><a href="{url}" title="第{page}页">{page}</a></li>';

    protected $cur_link_tag = '<li class="active"><a href="javascript:void(0)">{page}</a></li>';

    protected $prev_link_tag = '<li><a href="{url}">上一页</a></li>';
    
    protected $no_prev_link_tag = '';

    protected $next_link_tag = '<li><a href="{url}">下一页</a></li>';

    protected $no_next_link_tag = '';
    
    protected $more_tag = '<li><span>...</span></li>';
    
    protected $prefix = '<ul>';

    protected $suffix = '</ul>';

    protected $before_count = 4;

    protected $after_count = 4;

    protected $first_count = 2;
    
    protected $last_count = 2;

    protected $no_pages = '';
    
    protected $offset = FALSE;
    
    private static $defaultOptions = array();
    
    public function __construct($options = array())
    {
        
        if (self::$defaultOptions) {
            $options = array_merge(self::$defaultOptions, $options);
        }
        
        $this->setOptions($options);
    }
    
    public static function setDefaultOptions($options)
    {
        self::$defaultOptions = array_merge(self::$defaultOptions, $options);
    }
    
    public function setOptions($options)
    {
        foreach ($options as  $name => $val) {
            if (isset($this->$name)) {
                $this->$name = $val;
            }
        }
    }

    private function getDefaultTemplate()
    {
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_uri = preg_replace('@\'"@', '', $request_uri);
        
        $param = $this->param_type == self::TYPE_PAGE ? 
            $this->page_param :
            $this->offset_param;

        $tpl = preg_replace("@$param=[^&]+@", $param.'={page}', $request_uri);
        if ($tpl != $request_uri) {
            return $tpl;
        }

        $tpl = $request_uri;
        $tpl .= strpos($tpl, '?') === FALSE ? '?' : '&';
        $tpl .= "$param={page}";
        return $tpl;
    }

    private function getOffset($tpl)
    {
        $offset = 0;
        $param_type = $this->param_type;
        
        //ex. page={page} or page/{page}
        preg_match('@(\S+\W)\{page\}@', $tpl, $ma);
        
        $param_prefix = urlencode($ma[1]);

        $request_uri = $_SERVER['REQUEST_URI'];

        if (preg_match("@{$param_prefix}(\d+)@", $request_uri, $ma)) {
            $offset = (int) $ma[1];
        } else {
            $param = $param_type == self::TYPE_PAGE ? 
                $this->page_param : $this->offset_param;
            if (isset($_GET[$param])) {
                $offset = (int) $_GET[$param];
            }
        }

        if ($param_type == self::TYPE_PAGE) {
            $offset = max(0, $offset - 1) * $this->page_size;
        }

        return $offset;
    }

    public function toHtml()
    {
        $tpl = $this->page_url_tpl;
        if (!$tpl) {
            $tpl = $this->getDefaultTemplate();
        }
        
        //Replace smoe of charactors would cause problems.
        $tpl = str_replace(
            array('<', '>', '"'), 
            array('%3c', '%3e', '%22'), 
            $tpl
        );

        $offset = $this->offset;
        if ($offset === FALSE) {
            $offset = $this->getOffset($tpl);
        }
        $page_size = $this->page_size;
        $total_rows = $this->total_rows;
        $total_pages = ceil($total_rows / $page_size);
        
        if ($total_pages <= 1) {
            return $this->no_pages;
        }

        if ($this->max_pages > 0 && $total_pages > $this->max_pages) {
            $total_pages = $this->max_pages;
        }
        
        $cur_page = 1 + ($offset / $page_size);

        $pages = $this->getPages($total_pages, $cur_page);

        if (!$pages) {
            return $this->no_pages;
        }

        $html = array();
        $html[] = $this->prefix;
        if ($cur_page == 1) {
            $html[] = $this->no_prev_link_tag;
        } else {
            $this->pageUrl($tpl, $cur_page - 1);
            $html[] = $this->process($this->prev_link_tag);
        }
        foreach ($pages as $page) {
            $this->pageUrl($tpl, $page);
            if ($page == '...') {
                if ($this->more_tag) {
                    $html[] = $this->more_tag;
                }
                continue;
            }
            if ($page == $cur_page) {
                $html[] = $this->process($this->cur_link_tag);
            } else {
                $html[] = $this->process($this->link_tag);
            }                
        }
        if ($cur_page == $total_pages) {
            $html[] = $this->no_next_link_tag;
        } else {
            $this->pageUrl($tpl, $cur_page + 1);
            $html[] = $this->process($this->next_link_tag);
        }
        $html[] = $this->suffix;
        $html = array_filter($html);

        return implode(' ', $html);
    }
    
    private function pageUrl($tpl, $page)
    {
        $this->page = $page;
        
        if ($this->param_type == self::TYPE_OFFSET) {
            $page -= 1;
            $page *= $this->page_size;
        }

        $this->url = str_replace('{page}', $page, $tpl);
    }

    private function process($tpl, $data = array())
    {
        if (! preg_match_all('@\{(\w+)\}@', $tpl, $ma)) {
            return $tpl;
        }

        $tpl_vars = array_unique($ma[1]);
        $tpl_vals = array();
        foreach ($tpl_vars as &$tpl_var) {
            $tpl_vals[] = isset($data[$tpl_var]) ?
                $data[$tpl_var] : 
                (isset($this->$tpl_var) ? $this->$tpl_var : '');
            $tpl_var = '{'.$tpl_var.'}';
        }

        return str_replace($tpl_vars, $tpl_vals, $tpl);
    }

    private function getPages($total_pages, $cur_page)
    {
        $pages = array();

        if ($cur_page > $total_pages) {
            return $pages;
        }

        $first_count = $this->first_count;
        $before_count = $this->before_count;
        $after_count = $this->after_count;
        $last_count = $this->last_count;
        
        $continuous = $before_count + 1 + $after_count;

        if ($total_pages <= $continuous + $after_count) {
            for ($i = 1; $i <= $total_pages; $i++) {
                $pages[] = $i;
            }
        } else {
            //1 2 3 4 5 6 7 8 9 10 
            $after_end = min($total_pages, max($continuous, $cur_page + $after_count));
            $before_start = max(1, min($total_pages - $continuous + 1, $cur_page - $before_count));

            $end = min($first_count, $before_start - 1);
            for ($i = 1; $i <= $end; $i++) {
                $pages[] = $i;
            }

            if ($end + 1 < $before_start) {
                $pages[] = $end + 2 == $before_start ? $end + 1 : '...';
            }

            for ($i = $before_start; $i <= $after_end; $i++) {
                $pages[] = $i;
            }

            $start = max($total_pages - $last_count + 1, $after_end + 1);
            if ($start > $after_end + 1) {
                $pages[] = $after_end + 2 == $start ? $after_end + 1 : '...';
            }

            for ($i = $start; $i <= $total_pages; $i++) {
                $pages[] = $i;
            }
        }
        
        return $pages;
    }

    public function __toString()
    {
        return $this->toHtml();
    }

    public static function getHtml($total_rows, $page_size, $options = array())
    {
        $options['total_rows'] = $total_rows;
        $options['page_size'] = $page_size;
        $p = new self($options);
        return $p->__toString();
    }
}
