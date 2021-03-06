<?php
/*-----------------------------------------------\
| 												 |
|  @Author:       Andrey Brykin (Drunya)         |
|  @Email:        drunyacoder@gmail.com          |
|  @Site:         http://fapos.net			     |
|  @Version:      1.5.9                          |
|  @Project:      CMS                            |
|  @Package       CMS Fapos                      |
|  @Subpackege    Module Class                   |
|  @Copyright     ©Andrey Brykin 2010-2013       |
|  @Last mod.     2013/04/24                     |
\-----------------------------------------------*/

/*-----------------------------------------------\
| 												 |
|  any partial or not partial extension          |
|  CMS Fapos,without the consent of the          |
|  author, is illegal                            |
|------------------------------------------------|
|  Любое распространение                         |
|  CMS Fapos или ее частей,                      |
|  без согласия автора, является не законным     |
\-----------------------------------------------*/


class Module {

	/**
	* @page_title title of the page
	*/
	public $page_title = '';
	/**
	* @var string 
	*/
	public $page_meta_keywords;
	/**
	* @var string
	*/
	public $page_meta_description;
	/**
	* @template layout for module
	*/
	public $template = 'default';
	/**
	* @categories list of categories
	*/
	public $categories = '';
	/**
	* @module_title title for module
	*/
	public $module_title = '';
	/**
	* @module current module
	*/
	public $module = '';
	/**
	* @cacheTags Cache tags
	*/
	public $cacheTags = array();
	/**
	* @cached   use the cache engine
	*/
	protected $cached = true;
	
	/**
	* @var (str)   comments block
	*/
	protected $comments = '';
	
	/**
	* @var (str)   add comments form
	*/
	protected $comments_form = '';
	
	/**
	* @var    database object
	*/
	protected $Database;
	/**
	* uses for work with actions log
	*
	* @var    logination object
	*/
	protected $Log;
	/**
	* uses for work with parser (chuncks, snippets, global markers ...)
	*
	* @var    parser object
	*/
	protected $Parser;
	/**
	* contains system settings
	*
	* @var (array)   system settings
	*/
	public $set;
	
	/**
	* Access control list
	*
	* @var object
	*/
	protected $ACL;
	
	/**
	 * Object for work with text
	 */
	protected $Textarier;
	
	/**
	 * @var object
	 */
	protected $AddFields = false; 
	
	/**
	 * if true - counter not worck
	 *
	 * @var boolean
	 */
	public $counter = true;
	
	/**
	 * Use wrapper?
	 *
	 * @var boolean
	 */
	protected $wrap = true;

    /**
     * @var object
     */
    public $Register;
	
	/**
	 * @var object
	 */
	public $Model;
	 
	
	
	/**
	 * @var array
	 */
	protected $globalMarkers = array(
		'module' => '',
		'navigation' => '',
		'pagination' => '',
		'meta' => '',
		'add_link' => '',
		'comments_pagination' => '',
		'comments' => '',
        'comments_form' => '',
		'fps_curr_page' => 1,
		'fps_pagescnt' => 1,
	);
	
	
	
	
	/**
	 * @param array $params - array with modul, action and params
	 *
	 * Initialize needed objects adn set needed variables
	 */
	function __construct($params)
    {
        $this->Register = Register::getInstance();
        $this->Register['module'] = $params[0];
        $this->Register['action'] = $params[1];
        $this->Register['params'] = $params;
		
		
		// Use for templater (layout)
		$this->template = $this->module;
		
		$this->setModel();
		
		
		//get settings and launch before render
		$this->set = Config::read('all');
		
		//init needed objects. Core...
		$this->View = new Fps_Viewer_Manager($this);
		$this->Parser = new Document_Parser;
		$this->Parser->templateDir = $this->template;
		
		$this->DB = FpsDataBase::get();
		$this->Textarier = new PrintText;
		if ($this->set['secure']['system_log']) $this->Log = new Logination;
		
		// init aditional fields
		if ($this->Register['Config']->read('use_additional_fields')) {
			$this->AddFields = new FpsAdditionalFields;
			$this->AddFields->module = $this->module;
		}

		
		//init Access Control List
		$this->ACL = $this->Register['ACL'];
		$this->beforeRender();
		
		if ($this->Register['Config']->read('active', $params[0]) == 0) {
			if ('chat' === $params[0]) die(__('This module disabled'));
			return $this->showInfoMessageFull(__('This module disabled'), '/');
		}
		
		$this->page_title = ($this->Register['Config']->read('title', $this->module))
            ? h($this->Register['Config']->read('title', $this->module)) : h($this->module);
		$this->params = $params;
		
		//cache
		$this->Cache = new Cache;
		if ($this->Register['Config']->read('cache') == 1) {
			$this->cached = true;
			$this->cacheKey = $this->getKeyForCache($params);
			$this->setCacheTag(array('module_' . $params[0]));
			if (!empty($params[1])) $this->setCacheTag(array('action_' . $params[1]));
		} else {
			$this->cached = false;
		}
		
		//meta tags
		$this->page_meta_keywords = h($this->Register['Config']->read('keywords', $this->module));
		$this->page_meta_description = h($this->Register['Config']->read('description', $this->module));
		
		if (empty($this->page_meta_keywords)) {
			$this->page_meta_keywords = h($this->Register['Config']->read('meta_keywords'));
		}
		if (empty($this->page_meta_description)) {
			$this->page_meta_description = h($this->Register['Config']->read('meta_description'));
		}
		
		
		// 
		//$this->Register['GlobalParams'] = $this->getGlobalMarkers();
	}
	
	
	
	protected function setModel()
	{
		$class = ucfirst($this->module) . 'Model';
		$this->Model = new $class();
	}
	
	
	/**
	 * Uses for before render
	 * All code in this function will be worked before
	 * begin render page and launch controller(module)
	 *
	 * @return none
	 */
	protected function beforeRender()
    {
		if (isset($_SESSION['page'])) unset($_SESSION['page']);
		if (isset($_SESSION['pagecnt'])) unset($_SESSION['pagecnt']);
	}
	

	/**
	 * Uses for after render
	 * All code in this function will be worked after
	 * render page.
	 *
	 * @return none
	 */
	protected function afterRender()
    {
		// Cron
		if ($this->Register['Config']->read('auto_sitemap')) {
			fpsCron('createSitemap', 86400);
		}
		
		
		/*
		* counter ( if active )
		* and if we not in admin panel
		*/
		if (in_array($this->module, array('chat', 'rating'))) return;
		if ($this->counter === false) return;
		
		if (substr($_SERVER['PHP_SELF'], 1, 5) != 'admin') {
			include_once ROOT . '/modules/statistics/index.php';
			if ($this->Register['Config']->read('active', 'statistics') == 1) {
				StatisticsModule::index();
			} else {
				StatisticsModule::viewOffCounter();
			}
		}
	}
	

	/**
	* @param string $content  data for parse and view
	* @access   protected
	*/
	public function _view($content)
    {
        $Register = Register::getInstance();
		
		if (!empty($this->template) && $this->wrap == true) {
            Plugins::intercept('before_parse_layout', $this);
			
		
			$this->View->setLayout($this->template);
			$markers = $this->getGlobalMarkers(file_get_contents($this->View->getTemplateFilePath('main.html')));
            $markers['content'] = $content;
			

			//$html = $this->Parser->headMenu($html, $this->module);
			//$html = $this->Parser->ParseTemplate($html);
			
			
			// Cache global markers
			if ($this->cached) {
				if ($this->Cache->check($this->cacheKey . '_global_markers')) {
					$gdata = $this->Cache->read($this->cacheKey . '_global_markers');
					$this->globalMarkers = array_merge($this->globalMarkers, unserialize($gdata));
				} else {
					$gdata = serialize($this->globalMarkers);
					$this->Cache->write($gdata, $this->cacheKey . '_global_markers', $this->cacheTags);
				}
			}
			
			
			$boot_time = round(getMicroTime() - $Register['fps_boot_start_time'], 4);
			$markers = array_merge($markers, array('boot_time' => $boot_time));
			
			$output = $this->render('main.html', $markers);
		} else {
            $output = $content;
		}
        
		
		//$output = Plugins::intercept('before_view', $output);
		$this->afterRender();
		
		echo $output;

		
		
		if (Config::read('debug_mode') == 1 && !empty($_SESSION['db_querys'])) {
			$n = 1;
			$debug = '<div id="sql_querys" style="clear:both;position:relative; margin-top:40px;">
			<table align="center" style="position:relative;">';
			foreach ($_SESSION['db_querys'] as $query) {
				$debug .= '<tr><td width="5">' . $n . '</td><td>' . $query . '</td></tr>';
				$n++;
			}
			$debug .= '</table></div>';
			echo $debug;
		}
	}
	

	public function render($fileName, array $markers = array())
	{
        $additionalMarkers = $this->getGlobalMarkers();
        $this->_globalize($additionalMarkers);
		$source = $this->View->view($fileName, array_merge($markers, $this->globalMarkers));
		return $source;
	}
	
	
	public function renderString($string, array $markers = array())
	{
        $additionalMarkers = $this->getGlobalMarkers();
        $this->_globalize($additionalMarkers);
		$source = $this->View->parseTemplate($string, array_merge($markers, $this->globalMarkers));
		return $source;
	}


    protected function getGlobalMarkers($html = '')
    {
        $Register = Register::getInstance();
		$markers1 = $this->Parser->getGlobalMarkers($html);
        $markers2 = array(
            'module' => $this->module,
            'title' => $this->page_title,
            'meta_description' => $this->page_meta_description,
            'meta_keywords' => $this->page_meta_keywords,
            'module_title' => $this->module_title,
            'categories' => $this->categories,
            'comments' => $this->comments,
			'comments_form' => $this->comments_form,
            'fps_curr_page' => (!empty($Register['page'])) ? intval($Register['page']) : 1,
            'fps_pagescnt' => (!empty($Register['pagescnt'])) ? intval($Register['pagescnt']) : 1,
            // 'fps_user' => (!empty($_SESSION['user'])) ? $_SESSION['user'] : array(),
        );
		if (isset($this->Register['params']) && is_array($this->Register['params'])) {
			$markers2['action'] = count($this->Register['params']) > 1 ? $this->Register['params'][1] : 'index';
			$markers2['current_id'] = count($this->Register['params']) > 2 ? $this->Register['params'][2] : null;
		}
		$markers = array_merge($markers1, $markers2);
        return $markers;
    }


    /**
     * Save markers. Get list of markers
     * and content wthat will be instead markers
     * Before view this markers will be replaced in
     * all content
     *
     * @param array $markers - marker->value
     * @return none
     */
	protected function _globalize($markers = array())
    {
		$this->globalMarkers = array_merge($this->globalMarkers, $markers);
	}
	

	/**
	* @param  mixed $tag
    * @return boolean
	*/
	public function setCacheTag($tag)
    {
		if ((Config::read('cache') == true || Config::read('cache') == 1) && $this->cached === true) {
			if (is_array($tag)) {
				foreach ($tag as $_tag) {
					$this->setCacheTag($_tag);
				}
			} else {
				$this->cacheTags[] = $tag;
				return true;
			}
		}
		return false;
	}
	

	/**
	* create unique id for cache file
     *
	* @param array $params <module>[ action [ param1 [ param2 ]]]
    * @return string
	*/
	private function getKeyForCache($params)
    {
		$cacheId = '';
		foreach ($params as $value) {
			if (is_array($value)) {
				foreach ($value as $_value) {
					$cacheId = $cacheId . $_value . '_';
				}
				continue;
			}
			$cacheId = $cacheId . $value . '_';
		}
		
		if (!empty($_GET['page']) && is_numeric($_GET['page'])) {
				$cacheId = $cacheId . intval($_GET['page']) . '_';
		}
		if (!empty($_GET['order'])) {
			$order = (string)$_GET['order'];
			if (!empty($order)) {
				$order = substr($order, 0, 10);
				$cacheId .= $order . '_';
			}
		}
		if (!empty($_GET['asc'])) {
			$cacheId .= 'asc_';
		}
		$cacheId = (!empty($_SESSION['user']['status'])) ? $cacheId . $_SESSION['user']['status'] : $cacheId . 'guest';
		return $cacheId;
	}
	
	private function getCatChildren($cat_ids) {
		if (!$cat_ids || (!is_array($cat_ids) && $cat_ids < 1)) return array();
		
		$conditions = array('parent_id IN (' . implode(',', (array)$cat_ids) . ')');
		$cats = $this->DB->select($this->module . '_sections', DB_ALL, array('cond' => $conditions, 'fields' => array('id')));
		
		if ($cats && is_array($cats) && count($cats)) {
			$new_ids = array();
			foreach ($cats as $cat) {
				if (isset($cat['id'])) $new_ids[] = intval($cat['id']);
			}
			$children_ids = $this->getCatChildren(array_unique($new_ids));
			$cat_ids = array_unique(array_merge((array)$cat_ids, $children_ids));
		}
		return (array)$cat_ids;
	}
	
	private function getEntriesCount($cat_id) {
		$cat_id = intval($cat_id);
		if ($cat_id < 1)  return 0;
		
		$cat_ids = $this->getCatChildren($cat_id);
		if ($cat_ids && is_array($cat_ids) && count($cat_ids)) {
			$entriesModel = $this->Register['ModManager']->getModelInstance($this->module);
			$total = $entriesModel->getTotal(array('cond' => array('category_id IN (' . implode(',', $cat_ids) . ')')));
			return ($total ? $total : 0);
		} else {
			return 0;
		}
	}
	
	/**
	 * Build categories list ({CATEGORIES})
	 *
	 * @param mixed $cat_id
	 * @return string.
	 */
	protected function _getCatsTree($cat_id = false) 
	{
		// Check cache
		if ($this->cached && $this->Cache->check('category_' . $this->cacheKey)) {
			$this->categories = $this->Cache->read('category_' . $this->cacheKey);
			return;
		}
	
	
		// get mat id
		$id = (!empty($cat_id)) ? intval($cat_id) : false;
		if ($id < 1) $id = false;
		
	
		// Get current action
		if (empty($this->params[1])) $action = 'index';
		else $action = trim($this->params[1]);
		$output = '';
		
		
		// type o tree
		if (!empty($id)) {
			switch ($action) {
				case 'category':
				case 'view':
					$conditions = array('parent_id' => intval($id));
					$cats = $this->DB->select($this->module . '_sections', DB_ALL, 
					array('cond' => $conditions));
					break;
				default:
					break;
			}
		}
		if (empty($cats)) {
			if (!$id) {
				$select = array(
					'cond' => array(
						'`parent_id` = 0 OR `parent_id` IS NULL ',
					),
				);
			} else {
				$select = array(
					'joins' => array(
						array(
							'alias' => 'b',
							'type' => 'LEFT',
							'table' => $this->module . '_sections',
							'cond' => 'a.`parent_id` = b.`parent_id`',
						),
					),
					'fields' => array('b.*'),
					'alias' => 'a',
					'cond' => array('a.id' => intval($id)),
				);
			}
			
			$cats = $this->DB->select($this->module . '_sections', DB_ALL, $select);
		}
		
		
		$calc_count = $this->Register['Config']->read('calc_count', $this->module);
		// Build list
		if (count($cats) > 0) {
			foreach ($cats as $cat) {
				$output .= '<li>' . ($id && $cat['id'] == $id ? '<b>' : '') . get_link(h($cat['title']), '/' . $this->module . '/category/' . $cat['id']) . ($id && $cat['id'] == $id ? '</b>' : '') . ($calc_count ? ' [' . $this->getEntriesCount($cat['id']) . ']' : '') . '</li>';
			}
		}
		
		
		$this->categories = $output;
		
		if ($this->cached)
			$this->Cache->write($this->categories, 'category_' . $this->cacheKey
			, array('module_' . $this->module, 'category_block'));
	}
	

	protected  function _buildBreadCrumbs($cat_id = false)
    {
		$tree = array();
		$output = '<ul>';
		
		// Check cache
		if ($this->cached && $this->Cache->check('category_tree_' . $this->cacheKey)) {
			$tree = $this->Cache->read('category_tree_' . $this->cacheKey);
			$tree = unserialize($tree);
			return $tree;
		} else {
			$tree = $this->DB->select($this->module . '_sections', DB_ALL);
		}
		
		
		if (!empty($tree) && count($tree) > 0) {
			if ($this->cached)
				$this->Cache->write($this->categories, 'category_tree_' . $this->cacheKey
				, array('module_' . $this->module, 'category_block'));
		
			$output = $this->_buildBreadCrumbsNode($tree, $cat_id);
			return $output;
		}
		return '';
	}
	
	
	/**
	 * Build bread crumbs
	 * Use separator for separate links
	 * 
	 * @param array $tree
	 * @param mixed $cat_id
	 * @param mixed $parent_id
	 * @return string
	 */
	protected  function _buildBreadCrumbsNode($tree, $cat_id = false, $parent_id = false)
    {
		$output = '';
		
		
		if (empty($cat_id)) {
			$output = h($this->module_title);	
			
		} else {
			foreach ($tree as $key => $node) {
				if ($node['id'] == $cat_id  && !$parent_id) {
					$output = h($node['title']);
					if (!empty($node['parent_id']) && $node['parent_id'] != 0) {
						$output = $this->_buildBreadCrumbsNode($tree, $cat_id, $node['parent_id']) . $output;
					}
					break;
				} else if ($parent_id && $parent_id == $node['id']) {
					$output = get_link(h($node['title']), '/' . $this->module . '/category/' . $node['id']) . __('Separator');
					if (!empty($node['parent_id']) && $node['parent_id'] != 0) {
						$output = $this->_buildBreadCrumbsNode($tree, $cat_id, $node['parent_id']) . $output;
					}
					break;
				}
			}
			
			if (!$parent_id)
				$output = get_link(h($this->module_title), '/' . $this->module . '/') . __('Separator') . $output;
		}
		
		if (true && !$parent_id) $output = get_link(__('Home'), '/') . __('Separator') . $output;
		return $output;
	}
	

	/**
	 * Build categories list for select input
	 *
	 * @param array $cats
	 * @param mixed $curr_category
	 * @param mixed $id
	 * @param string $sep
	 * @return string
	 */
	protected function _buildSelector($cats, $curr_category = false, $id = false, $sep = '- ')
    {
		$out = '';
		foreach ($cats as $key => $cat) {
			$parent_id = $cat->getParent_id();
			if (($id === false && empty($parent_id))
			|| (!empty($id) && $parent_id == $id)) {
				$out .= '<option value="' . $cat->getId() . '" ' 
				. (($curr_category !== false && $curr_category == $cat->getId()) ? "selected=\"selected\"" : "") 
				. '>' . $sep . h($cat->getTitle()) . '</option>';
				unset($cats[$key]);
				$out .= $this->_buildSelector($cats, $curr_category, $cat->getId(), $sep . '- ');
			}
		}
		
		return $out;
	}
	
	
	// Вспомогательная функция - после выполнения пользователем каких-либо действий
	// выдает информационное сообщение и делает редирект на нужную страницу с задержкой
	function showInfoMessage($message, $queryString = null, $urlMode = null) 
	{
		$jsredirect = '';
		if ($urlMode == 1) {
			$queryString = null;
		} elseif ($urlMode == 2) {
			$jsredirect = '<script>setTimeout("document.location.href=\''.$queryString.'\'", 3000);</script>';
		} elseif ($urlMode == 3) {
			$jsredirect = '<script>setTimeout("document.location.href=\''.$queryString.'\'", 0);</script>';
		}
		header( 'Refresh: ' . $this->Register['Config']->read('redirect_delay') . '; url=http://' . $_SERVER['SERVER_NAME'] . get_url($queryString));
		$output = $this->render('infomessage.html', array('data' => array('info_message' => $message, 'error_message' => null, 'url' => $queryString, 'jsredirect' => $jsredirect)));
		echo $output;
		die();
	}


	function showInfoMessageFull($message, $queryString = null) 
	{
		header( 'Refresh: ' . $this->Register['Config']->read('redirect_delay') . '; url=http://' . $_SERVER['SERVER_NAME'] . get_url($queryString));
		$output = $this->render('infomessagegrand.html', array('data' => array('info_message' => $message, 'error_message' => null)));
		echo $output;
		die();
	}

	
	// Функция возвращает путь к модулю
	function getModuleURL($page = null)
	{
		$url = '/' . $this->module . '/' . (!empty($page) ? $page : '');
		return $url;
	}

	
	// Функция возвращает путь к файлам модуля
	function getFilesPath($file = null, $module = null)
	{
		if (!isset($module)) $module = $this->module;
		$path = '/sys/files/' . $module . '/' . (!empty($file) ? $file : '');
		return $path;
	}

	
	// Функция возвращает путь к временным файлам модуля
	function getTmpPath($file = null)
	{
		$path = '/sys/tmp/' . $this->module . '/' . (!empty($file) ? $file : '');
		return $path;
	}

	
	// Функция возвращает максимально допустимый размер файла
	function getMaxSize($param = 'max_file_size')
	{
		$max_size = Config::read($param, $this->module);
		if (empty($max_size) || !is_numeric($max_size)) $max_size = Config::read('max_file_size');
		if (empty($max_size) || !is_numeric($max_size)) $max_size = 1048576;
		return $max_size;
	}
	
	
	// Функция обрабатывает метку изображения
	function insertImageAttach($message, $filename, $number, $module = null)
	{
		if (!isset($module)) $module = $this->module;
		$image_link = get_url($this->getFilesPath($filename, $module));
		
		if (Config::read('use_local_preview', $this->module)) {
            $preview = Config::read('use_preview', $this->module);
            $size_x = Config::read('img_size_x', $this->module);
            $size_y = Config::read('img_size_y', $this->module);
        } else {
            $preview = Config::read('use_preview');
            $size_x = Config::read('img_size_x');
            $size_y = Config::read('img_size_y');
        }
        $preview_link = ($preview ? get_url('/image/' . $module . '/' . $filename) : $image_link);
		
		$str = 
			($preview ? '<a class="gallery" href="' . $image_link . '">' : '') .
			'<img %s style="max-width:' . ($size_x > 150 ? $size_x : 150) . 'px; max-height:' . ($size_y > 150 ? $size_y : 150) . 'px;" src="' . $preview_link . '" />' .
			($preview ? '</a>' : '');
		$from = array(
			'{IMAGE' . $number . '}', 
			'{LIMAGE' . $number . '}', 
			'{RIMAGE' . $number . '}', 
		);
		$to = array(
			sprintf($str, ''), 
			sprintf($str, 'align="left"'), 
			sprintf($str, 'align="right"'), 
		);
		return str_replace($from, $to, $message);
	}


    // Функция обрабатывает метку изображения в шаблоне
    function markerImageAttach($filename, $number, $module = null)
    {
        if (!isset($module)) $module = $this->module;
        $image_link = get_url($this->getFilesPath($filename, $module));
        return $image_link;
    }


    // Функция обрабатывает метку превью на изображение в шаблоне
    function markerSmallImageAttach($filename, $number, $module = null)
    {
        if (!isset($module)) $module = $this->module;
        $preview_link = get_url('/image/' . $module . '/' . $filename);

        return $preview_link;
    }
}