<?php
/*-----------------------------------------------\
| 												 |
|  @Author:       Andrey Brykin (Drunya)         |
|  @Version:      1.6.50                         |
|  @Project:      CMS                            |
|  @package       CMS Fapos                      |
|  @subpackege    Forum Module                   |
|  @copyright     ©Andrey Brykin 2010-2013       |
|  @last mod.     2013/01/10                     |
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


/**
* forum functionaly
*
* @author      Andrey Brykin 
* @package     CMS Fapos
* @subpackage  Forum module
* @link        http://cms.develdo.com
*/
Class ForumModule extends Module {

	/**
	* @module_title  title of module
	*/
	public $module_title = 'Форум';
	/**
	* @template  layout for module
	*/
	public $template = 'forum';
	/**
	* @module module indentifier
	*/
	public $module = 'forum';
	
	/**
	 * Wrong extention for download files
	 */
	private $denyExtentions = array('.php', '.phtml', '.php3', '.html', '.htm', '.pl', '.PHP', '.PHTML', '.PHP3', '.HTML', '.HTM', '.PL', '.js', '.JS');
	
	
	/**
	 * @return main forum page content
	 */
	public function index($cat_id = null) 
	{
		//turn access
		$this->ACL->turn(array($this->module, 'view_forums_list'));
		$this->page_title = h(Config::read('title', $this->module)) . __('Forums list');
		
		
		// navigation block
		$markers = array();
		$markers['navigation'] = get_link(__('Home'), '/') . __('Separator') 
		. get_link(__('Forums list'), $this->getModuleURL()) . "\n";
		$markers['pagination'] = '';
		$markers['add_link'] = '';
		$markers['meta'] = '';
		$this->_globalize($markers);
		
		
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$html = $this->Cache->read($this->cacheKey) . $this->_get_stat();
			return $this->_view($html);
		} 
		
		

		$conditions = array();
		if (!empty($cat_id) && is_numeric($cat_id)) {
			$cat_id = (int)$cat_id;
			if ($cat_id > 0) {
				$conditions['id'] = $cat_id;
			}
		}
		
		//get forums categories records
		$catsModel = $this->Register['ModManager']->getModelName('ForumCat');
		$catsModel = new $catsModel;
		$cats = $catsModel->getCollection(array($conditions), array('order' => 'previev_id'));
		if (empty($cats)) {
			$html = __('No categories') . "\n" . $this->_get_stat();
			return $this->_view($html);
		}
		//pr($cats); die();

		

		
		
		$conditions = (!empty($conditions)) ? array('in_cat' => $cat_id) : array();
		$conditions[] = array("`parent_forum_id` IS NULL OR `parent_forum_id` = '0'");
		$this->Model->bindModel('last_theme');
		$this->Model->bindModel('subforums');
		$_forums = $this->Model->getCollection($conditions, array(
			'order' => 'pos',
		));
		$_forums = $this->Model->addLastAuthors($_forums);
		
		

		//pr($_forums); die();
		
		//sort forums and subforums
		//after this we will be have $categories array with all cats, forum and subforums
		$forums = array();
		$categories = array();
		if (count($_forums) > 0) {
			foreach ($_forums as $forum) {
				$forums[$forum->getIn_cat()][] = $forum;
			}
		}
		
		
		foreach ($cats as $category) {
			$categories[$category->getId()] = $category;
			$categories[$category->getId()]->setForums(array());
			if (array_key_exists($category->getId(), $forums)) {
				$categories[$category->getId()]->setForums($forums[$category->getId()]);
				unset($forums[$category->getId()]); //clean memory
			} else {
				unset($categories[$category->getId()]); //we needen't empty categories
			}
		}

		
	
		foreach ($categories as $cat) {
			$cat->setCat_url(get_url($this->getModuleURL('index/' . $cat->getId())));
			$forums = $cat->getForums();	
			if (!empty($forums)) {
				foreach ($forums as $forum) {
					$subforums = $forum->getSubforums();
					//forming forums
					$forum = $this->_parseForumTable($forum);
				}
			} else {
				$info = __('Subforum is empty');
			}
		}
		
		
		//write to cache ( only if records detected )
		if ($this->cached)
			$this->Cache->write($html, $this->cacheKey, $this->cacheTags);		

	

		$source = $this->render('catlist.html', array('forum_cats' => $categories));
		$source .= $this->_get_stat();
		return $this->_view($source);
	}


	
	/**
	 * @param array $forum
	 * @retrun string HTML forum table wiht replaced markers
	 */
	private function _parseForumTable($forum) 
	{
		// Summ posts and themes
		if ($forum->getSubforums() && count($forum->getSubforums()) > 0) {
			foreach ($forum->getSubforums() as $subforum) {
				$forum->setPosts($forum->getPosts() + $subforum->getPosts());
				$forum->setThemes($forum->getThemes() + $subforum->getThemes());
			}
		}
		

		$forum->setForum_url(get_url($this->getModuleURL('view_forum/' . $forum->getId())));

		
		
		//выводим название темы в которой было добавлено последнее сообщение и дату его добавления
		if (!$forum->getLast_theme()/* ||  !$forum->getLast_author()*/) {
			$last_post = __('No posts');
			
		} else {
			$last_post_title = (mb_strlen($forum->getLast_theme()->getTitle()) > 30) 
			? mb_substr($forum->getLast_theme()->getTitle(), 0, 30) . '...' : $forum->getLast_theme()->getTitle();
			
			
			$last_theme_author = __('Guest');
			if ($forum->getLast_author()) {
				$last_theme_author = get_link(h($forum->getLast_author()->getName()), 
				getProfileUrl($forum->getLast_author()->getId()), array('title' => __('To profile')));
			}
			
			
			$last_post = $forum->getLast_theme()->getLast_post() . '<br>' . get_link(h($last_post_title), 
				$this->getModuleURL('view_theme/' . $forum->getLast_theme()->getId() . '?page=999'), 
				array('title' => __('To last post')))
			    . __('Post author') . $last_theme_author;
		}
		$forum->setLast_post($last_post);
		

		
		// Ссылка "Править форум"
		$admin_bar = '';
		if ($this->ACL->turn(array($this->module, 'replace_forums'), false)) {
			$admin_bar .= get_link(get_img('/sys/img/up_arrow_16x16.png'), $this->getModuleURL('forum_up/' . $forum->getId()))
				. '&nbsp;' . get_link(get_img('/sys/img/down_arrow_16x16.png'), $this->getModuleURL('forum_down/' .$forum->getId())) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'edit_forums'), false)) {
			$admin_bar .= get_link(get_img('/sys/img/edit_16x16.png'), $this->getModuleURL('edit_forum_form/' . $forum->getId())) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'delete_forums'), false)) {
			$admin_bar .= get_link(get_img('/sys/img/delete_16x16.png'), $this->getModuleURL('delete_forum/' . $forum->getId()),
			array('onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		$forum->setAdmin_bar($admin_bar);
		
		
		/* forum icon */
		$forum_icon = get_url('/sys/img/guest.gif');
		if (file_exists(ROOT . '/sys/img/forum_icon_' . $forum->getId() . '.jpg')) {
			$forum_icon = get_url('/sys/img/forum_icon_' . $forum->getId() . '.jpg');
		}
		$forum->setIcon_url($forum_icon);
		return $forum;
	}
	
	


	/**
	 * View threads list (forum)
	 */
	public function view_forum($id_forum = null) 
	{
		//turn access
		$this->ACL->turn(array($this->module, 'view_forums'));
		$id_forum = intval($id_forum);
		if (empty($id_forum) || $id_forum < 1) redirect($this->getModuleURL());

		
		
		//who is here
		$who = array();	
		$dir = ROOT . '/sys/logs/forum/';
		$forumFile = $dir . $id_forum . '.dat';
		if (!file_exists($dir)) mkdir($dir, 0777, true);
		if (file_exists($forumFile)) {
			$who = unserialize(file_get_contents($forumFile));
		}
		
		
		if (isset($_SESSION['user'])) {
			if (!isset($who[$_SESSION['user']['id']])) {
				$who[$_SESSION['user']['id']]['profile_link'] = get_link(h($_SESSION['user']['name']), '/users/info/' . $_SESSION['user']['id']);
				$who[$_SESSION['user']['id']]['expire'] = time() + 1000;
			}
		}
		
		
		$who_is_here = '';
		foreach ($who as $key => $val) {
			if ($val['expire'] < time()) {
				unset($who[$key]);
				continue;
			}
			$who_is_here .= $val['profile_link'] . ', ';
		}
		file_put_contents($forumFile, serialize($who));
		//$context = array('who_is_here', substr($who_is_here, 0, -2));

		
		//are we have cache?
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$html = $this->Cache->read($this->cacheKey);
		} else {

		
			// Получаем информацию о форуме
			$forum = $this->Model->getById($id_forum);
			if (empty($forum)) {
				return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
			}
			
			
			// Check access to this forum. May be locked by pass or posts count
			$this->__checkForumAccess($forum);
			$this->page_title = h($forum->getTitle()) . ' - ' . $this->page_title;
			
			
			
			// reply link
			$addLink = ($this->Register['ACL']->turn(array($this->module, 'add_themes'), false)) 
			? get_link(get_img('/template/' . Config::read('template').'/img/add_theme_button.png', 
			array('alt' => __('New topic'))), '/forum/add_theme_form/' . $id_forum) : '';
			
			
			// count themes for page nav
			$this->Model->bindModel('subforums');
			$this->Model->bindModel('category');
			$this->Model->bindModel('last_theme');
			$forum = $this->Model->getById($id_forum);
			$themesClassName = $this->Register['ModManager']->getModelName('Themes');
			$themesClass = new $themesClassName;
			$themesClass->bindModel('author');
			$themesClass->bindModel('last_author');
			$themes = $themesClass->getCollection(array('id_forum' => $id_forum));
			
			
            list($pages, $page) = pagination(
				count($themes), 
				Config::read('themes_per_page', $this->module), 
				$this->getModuleURL('view_forum/' . $id_forum)
			);
			$this->page_title .= ' (' . $page . ')';
			
			
			// Nav block
			$markers = array();
			$markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') 
			. get_link(h($forum->getTitle()), $this->getModuleURL('view_forum/' . $id_forum));
			$markers['pagination'] = $pages;
			$markers['add_link'] = '';
			$markers['meta'] = $addLink;
			$this->_globalize($markers);
			
			
			$subforums = $forum->getSubforums();
			if (count($subforums) > 0) {
				foreach($subforums as $subforum) {
					$subforum = $this->_parseForumTable($subforum);
				}
	
				$forum->setCat_name(__('Subforums title'));
			}
		
			
			
			
			$cnt_themes_here = count($themes);
			if ($cnt_themes_here > 0 && is_array($themes)) {
				foreach ($themes as $theme) {
				
					$theme = $this->__parseThemeTable($theme);
					
					//set cache tags
					$this->setCacheTag(array(
						'theme_id_' . $theme->getId(),
					));
				}			
				$this->setCacheTag(array(
					'forum_id_' . $id_forum,
				));
				
				
			}	
			
			
			$forum->setCount_themes_here($cnt_themes_here);
			$forum->setWho_is_here(substr($who_is_here, 0, -2));
			//$forum->setCount_themes(count($themes));


			//write cache
			if ($this->cached)
				$this->Cache->write($html, $this->cacheKey, $this->cacheTags);
		}
		
		

		$source = $this->render('themes_list.html', array(
			'themes' => $themes,
			$this->module => $forum,
		));
		return $this->_view($source);
	}


	
	/**
	 * Check access to this forum. 
	 * May be locked by pass or posts count
	 *
	 * @param array $forum
	 */
	private function __checkForumAccess($forum) 
	{
		if (!$forum->getLock_passwd() && !$forum->getLock_posts()) return;
		
		
		if ($forum->getLock_passwd()) {
			if (isset($_SESSION['access_forum_' . $forum->getId()])) {
				return;
				
			// if we have two generally meet one of them
			} else if ($forum->getLock_posts() && 
			(isset($_SESSION['user']['posts']) && $_SESSION['user']['posts'] >= $forum->getLock_posts())) {
				return;
				
			// Check sended password
			} else if (isset($_POST['forum_lock_pass'])) {
				if ($_POST['forum_lock_pass'] == $forum->getLock_passwd()) {
					$_SESSION['access_forum_' . $forum->getId()] = true;
					return;
				}
				$this->showInfoMessage(__('Wrong pass for forum'), $this->getModuleURL());
			} else {
				echo $this->render('forum_passwd_form.html', array());
				die();
			}
			
			
		// For lock by posts count
		} else if ($forum->getLock_posts()) {
			if (isset($_SESSION['user']['posts']) && $_SESSION['user']['posts'] >= $forum->getLock_posts()) {
				return;
			}
			$this->showInfoMessage(sprintf(__('locked forum by posts'), $forum->getLock_posts()), $this->getModuleURL());
		}
	}
	
	

	/**
	 * @param array $theme
	 * @param string $template
	 * If $template = FALSE, use default template file
	 * @retrun string HTML theme table with replaced markers
	 */
	private function __parseThemeTable($theme, $template = false) 
	{
		$htmltheme = null;
		$markers = array();
		
		//ICONS
		$themeicon = $this->__getThemeIcon($theme); 
			
		$theme->setTheme_url(get_url('/forum/view_theme/' . $theme->getId()));

		
		//ADMINBAR 
		$adminbar = '';
		if ($this->ACL->turn(array($this->module, 'edit_themes'), false) 
		|| (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'edit_mine_themes'), false))) {
			$adminbar .= get_link(get_img('/sys/img/edit_16x16.png', array('alt' => __('Edit'), 'title' => __('Edit'))),
				$this->getModuleURL('edit_theme_form/' . $theme->getId()));
		}
		if ($this->ACL->turn(array($this->module, 'close_themes'), false)) {
			if ( $theme->getLocked() == 0 ) { // заблокировать тему
				$adminbar .= get_link(get_img('/sys/img/lock_16x16.png', array('alt' => __('Lock'), 'title' => __('Lock'))),
					$this->getModuleURL('lock_theme/' . $theme->getId()));
			} else { // разблокировать тему
				$adminbar .= get_link(get_img('/sys/img/unlock_16x16.png', array('alt' => __('Unlock'), 'title' => __('Unlock'))),
					$this->getModuleURL('unlock_theme/' . $theme->getId()));
			}
		}
		
		
		if ($this->ACL->turn(array($this->module, 'important_themes'), false)) {
			if ($theme->getImportant() == 1) {
				$adminbar .= get_link(get_img('/sys/img/unpush.png', array('alt' => __('Important'), 'title' => __('Important'))),
					$this->getModuleURL('unimportant/' . $theme->getId()));
			} else {
				$adminbar .= get_link(get_img('/sys/img/push.png', array('alt' => __('Not important'), 'title' => __('Not important'))),
					$this->getModuleURL('important/' . $theme->getId()));
			}
		}
		
		
		if ($this->ACL->turn(array($this->module, 'delete_themes'), false) 
		|| (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'delete_mine_themes'), false))) {
			$adminbar .= get_link(get_img('/sys/img/delete_16x16.png', array('alt' => __('Delete'), 'title' => __('Delete'))),
				$this->getModuleURL('delete_theme/' . $theme->getId()), array('onClick' => "return confirm('" . __('Are you sure') . "')"));
		}
		$theme->setAdminbar($adminbar);
		
		
		//USER PROFILE
		$author_url = __('Guest');
		if ($theme->getId_author()) {
			$author_url = get_link(h($theme->getAuthor()->getName()), getProfileUrl($theme->getId_author()));
		}
		$theme->setAuthorUrl($author_url); 
	
		
		// Last post author
		$last_user = __('Guest');
		if ($theme->getId_last_author()) {
			$last_user = get_link(h($theme->getLast_author()->getName()), getProfileUrl($theme->getId_last_author()));
		}
		$last_page = get_link(__('To last'), $this->getModuleURL('view_theme/' . $theme->getId() . '&page=99999'));
		
		
		//NEAR PAGES
		$near_pages = '';
		if (($theme->getPosts() + 1) > Config::read('posts_per_page', $this->module)) {
			$cnt_near_pages = ceil(($theme->getPosts() + 1) / Config::read('posts_per_page', $this->module));
			if ($cnt_near_pages > 1) {
				$near_pages .= '&nbsp;(';
				for ($n = 1; $n < ($cnt_near_pages + 1); $n++) { 
					if ($cnt_near_pages > 5 && $n > 3) {
						$near_pages .= '...&nbsp;' . get_link(($cnt_near_pages - 1), $this->getModuleURL('view_theme/' . $theme->getId() . '?page=' 
									. ($cnt_near_pages - 1))) . '&nbsp;' . get_link($cnt_near_pages, $this->getModuleURL('view_theme/' 
									. $theme->getId() . '?page=' . $cnt_near_pages)) . '&nbsp;';				
						break;
					} else {
						if ($n > 5) break;
						$near_pages .= get_link($n, $this->getModuleURL('view_theme/' . $theme->getId() . '?page=' . $n)) . '&nbsp;';
					}
				}
				$near_pages .= ')';
			}
		}
		
		
		$theme->setLast_page($last_page);
		$theme->setLast_user($last_user);
		$theme->setThemeicon($themeicon);
		$theme->setFps_css_class(($theme->getImportant()) ? 'fps-theme-important' : '');
		$theme->setNear_pages($near_pages);
		$theme->setImportantly(($theme->getImportant() == 1) ? __('Important2') : '');
		

		return $theme;
	}
	
	

	/**
	 * Return theme icon
	 *
	 * @param array $theme
	 * @return string img HTML tag with URL to needed icon
	 */
	private function __getThemeIcon($theme) 
	{
		$hot_theme_limit = 20;
	
		if (isset($_SESSION['user'])) { // это для зарегистрированного пользователя
			// Если есть новые сообщения (посты) - только для зарегистрированных пользователей
			if (isset($_SESSION['newThemes']) and in_array($theme->getiId(), $_SESSION['newThemes'])) {
				if ($theme->getLocked() == 0) // тема открыта
					if ($theme->getPosts() > $hot_theme_limit)
						$themeicon = get_img('/template/'.getTemplateName().'/img/folder_hot_new.gif'
						, array('width' => '19', 'height' => '18', 'alt' => __('New posts'), 'title' => __('New posts')));
					else
						$themeicon = get_img('/template/'.getTemplateName().'/img/folder_new.gif'
						, array('width' => '19', 'height' => '18', 'alt' => __('New posts'), 'title' => __('New posts')));
				else // тема закрыта
					$themeicon = get_img('/template/'.getTemplateName().'/img/folder_lock_new.gif'
					, array('width' => '19', 'height' => '18', 'alt' => __('New posts'), 'title' => __('New posts')));
						
						
			} else {
				if ( $theme->getLocked() == 0 ) // тема открыта
					if ($theme->getPosts() > $hot_theme_limit)
						$themeicon = get_img('/template/'.getTemplateName().'/img/folder_hot.gif'
						, array('width' => '19', 'height' => '18', 'alt' => __('No new posts'), 'title' => __('No new posts')));
					else
						$themeicon = get_img('/template/'.getTemplateName().'/img/folder.gif'
						, array('width' => '19', 'height' => '18', 'alt' => __('No new posts'), 'title' => __('No new posts')));
				else // тема закрыта
					$themeicon = get_img('/template/'.getTemplateName().'/img/folder_lock.gif'
					, array('width' => '19', 'height' => '18', 'alt' => __('No new posts'), 'title' => __('No new posts')));
			}
			
			
		} else { // это для не зарегистрированного пользователя
			if ( $theme->getLocked() == 0 ) // тема открыта
				if ($theme->getPosts() > $hot_theme_limit)
					$themeicon = get_img('/template/'.getTemplateName().'/img/folder_hot.gif'
					, array('width' => '19', 'height' => '18'));
				else
					$themeicon = get_img('/template/'.getTemplateName().'/img/folder.gif'
					, array('width' => '19', 'height' => '18'));
			else // тема закрыта
				$themeicon = get_img('/template/'.getTemplateName().'/img/folder_lock.gif'
				, array('width' => '19', 'height' => '18'));
		}
		
		return $themeicon;
	}
	

	/**
	 * Return posts list
	 */
	public function view_theme($id_theme = null) 
	{	
		//turn access
		$this->ACL->turn(array($this->module, 'view_themes'));
		$id_theme = (int)$id_theme;
		if (empty($id_theme) || $id_theme < 1) redirect($this->getModuleURL());

		
		
		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themeModel->bindModel($this->module);
		$theme = $themeModel->getById($id_theme);
		if (!$theme->getForum())  return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL() );

		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($theme->getForum());
		$id_forum = $theme->getId_forum();
		
		$this->__checkThemeAccess($theme);
		
		
		//pr($theme); die();
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
		} else {
		
			
			// Если запрошенной темы не существует - возвращаемся на форум
			if (empty($theme)) return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL() );


			// Заголовок страницы (содержимое тега title)
			$this->page_title = h($theme->getTitle()) . ' - ' . $this->page_title;
			
			
			
			$markers = array();
			$markers['navigation'] = get_link(__('Home'), '/') . __('Separator') 
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . get_link($theme->getForum()->getTitle(), 
			$this->getModuleURL('view_forum/' .  $id_forum)) . __('Separator') . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme));
			$description = h($theme->getDescription());
			if (!empty($description)) {
				$markers['navigation'] .= ' (' . $theme->getDescription() . ')';
			}
			
			
			// Page nav
			$postsModelName = $this->Register['ModManager']->getModelName('Posts');
			$postsModel = new $postsModelName;
			$total = $postsModel->getTotal(array('cond' => array('id_theme' => $id_theme)));
			
			if ($total === 0) {
				$this->__delete_theme($id_theme);
				return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL('view_forum/' . $id_forum));
			}
			list($pages, $page) = pagination($total, Config::read('posts_per_page', $this->module), $this->getModuleURL('view_theme/' . $id_theme));
			$markers['pagination'] = $pages;
			$this->page_title .= ' (' . $page . ')';
		
			
			
			// SELECT posts
			$postsModel->bindModel('author');
			$postsModel->bindModel('editor');
			$postsModel->bindModel('attacheslist');
			$posts = $postsModel->getCollection(array(
				'id_theme' => $id_theme,
			), array(
				'order' => 'time ASC, id ASC',
				'page' => $page,
				'limit' => Config::read('posts_per_page', $this->module),
			));

			
			
			// Ссылка "Ответить" (если тема закрыта - выводим сообщение "Тема закрыта")
			if ($theme->getLocked() == 0) {
				$markers['add_link'] = get_link(get_img('/template/' 
				. getTemplateName().'/img/reply.png', array('alt' => __('Answer'), 
				'title' => __('Answer'))), $this->getModuleURL('view_theme/' . $id_theme . '#sendForm'));
			} else {
				$markers['add_link'] = get_img('/sys/img/reply_locked.png', 
				array('alt' => __('Theme locked'), 'title' => __('Theme locked')));
			}
			
			
			//if (!isset($_SESSION['user'])) $markers['add_link'] = '';
			if (!$this->Register['ACL']->turn(array($this->module, 'add_posts'), false)) $markers['add_link'] = '';
			$markers['meta'] = '';
			$this->_globalize($markers);
			
			
			$post_num = (($page - 1) * Config::read('posts_per_page', $this->module));
			
			
			//serialize rating settings
			$settingsModelName = $this->Register['ModManager']->getModelName('UsersSettings');
			$settingsModel = new $settingsModelName;
			$rating_settings = $settingsModel->getCollection(array('type' => 'rating'));
			$rating_settings = (count($rating_settings) > 0) ? $rating_settings[0]->getValues() : ''; 
			
			
			
			foreach ($posts as $post) {
				// Если автор сообщения (поста) - зарегистрированный пользователь
				$postAuthor = $post->getAuthor();
				if ($post->getId_author()) {

					// Аватар
					if (is_file(ROOT . '/sys/avatars/' . $post->getId_author() . '.jpg')) {
						$postAuthor->setAvatar(get_url('/sys/avatars/' . $post->getId_author() . '.jpg'));
					} else {
						$postAuthor->setAvatar(get_url('/sys/img/noavatar.png'));
					}


					// Статус пользователя
					$status = $this->Register['ACL']->get_group_info();
					$user_status = $status[$postAuthor->getStatus()];
					$postAuthor->setStatus_title($user_status['title']);
					

					// Рейтинг пользователя (по количеству сообщений)
					$rating = $postAuthor->getPosts();
					$rank_star = getUserRating($rating, $rating_settings);
					$postAuthor->setRank($rank_star['rank']);
					if ($postAuthor->getState()) $postAuthor->setRank($postAuthor->getState());
					$postAuthor->setUser_rank(get_img('/sys/img/' . $rank_star['img']));
					

					// Если автор сообщения сейчас "на сайте"
					$users_on_line = getOnlineUsers(); 
					if (isset($users_on_line) &&  isset($users_on_line[$post->getId_author()])) {
						$postAuthor->setStatus_on(__('Online'));
						$postAuthor->setStatus_line('Online');
					} else {
						$postAuthor->setStatus_on(__('Offline'));
						$postAuthor->setStatus_line('Offline');
					}


					// Если пользователь заблокирован
					if ($postAuthor->getBlocked()) {
						$postAuthor->setStatus_on('<span class="statusBlock">' . __('Banned') . '</span>');
						$postAuthor->setStatus_line('')
					}



                // Если автор сообщения - незарегистрированный пользователь
				} else {
				    $postAuthor->setAvatar(get_url('/sys/img/noavatar.png'));
				    $postAuthor->setName(__('Guest'));
				}
				
				
                $author_status = ($post->getAuthor()) ? $post->getAuthor()->getStatus() : 0;
				
				
				$message = $this->Textarier->print_page($post->getMessage(), $author_status);

				
				$attachment = null;
				if ($post->getAttacheslist()) {
					foreach ($post->getAttacheslist() as $attach) {
						$collizion = false;
						if (file_exists(ROOT . '/sys/files/forum/' . $attach->getFilename())) {
							$attachment .= __('Attachment') . $attach->getAttach_number() 
								. ': ' . get_img('/sys/img/file.gif', array('alt' => __('Open file'), 'title' => __('Open file'))) 
								. '&nbsp;' . get_link(($attach->getSize() / 1000) .' Кб', '/forum/download_file/' 
								. $attach->getFilename(), array('target' => '_blank')) . '<br />';
								
								
							//if attach is image and isset markers for this image
							if ($attach->getIs_image() == 1) {
							
							
								$message = str_replace('{IMAGE'.$attach->getAttach_number().'}'
								, '<a class="gallery" href="' . get_url('/sys/files/' . $this->module . '/' . $attach->getFilename()) 
								. '"><img src="' . get_url('/image/' . $this->module . '/' . $attach->getFilename()) . '" /></a>'
								, $message);
							
							
								//$message = str_replace('{IMAGE' . $attach->getAttach_number() . '}', 
								//	'[img]' . get_url('/sys/files/forum/' . $attach->getFilename()) . '[/img]',
								//	$post->getMessage());
								//$post->setMessage($message);
							}
							$collizion = true;
							continue;
						}
					}
					/* may be collizion (paranoya mode) */
					if (!$collizion) $this->deleteCollizions($post);
				} else {
					$this->deleteCollizions($post);
				}
				
				$post->setMessage($message);
				

				$signature = ($postAuthor->getSignature())
				? $this->Textarier->getSignature($postAuthor->getSignature(), $postAuthor->getStatus()) : '' ;
                $postAuthor->setSignature($signature);

				
				// If author is authorized user. 
				$email = '';
				$privat_message = '';
				$author_site = '';
                $user_profile = '';
				$icon_params = array('class' => 'user-details');
				
				
				if ($post->getId_author()) {
					$user_profile = '&nbsp;' . get_link(
						get_img(
							'/template/' . getTemplateName() . '/img/icon_profile.png', 
							array('alt' => __('View profile'), 'title' => __('View profile'))
						), 
						'/users/info/' . $post->getId_author(), 
						$icon_params
					);
					
					
					if (isset($_SESSION['user'])) {
						$email = '&nbsp;' . get_link(
							get_img(
								'/template/' . getTemplateName() . '/img/icon_email.png', 
								array('alt' => __('Send mail'), 'title' => __('Send mail'))
							), 
							'/users/send_mail_form/' . $post->getId_author(), 
							$icon_params
						);
						$privat_message = '&nbsp;' . get_link(
							get_img(
								'/template/' . getTemplateName() . '/img/icon_pm.png', 
								array('alt' => __('PM'), 'title' => __('PM'))
							), 
							'/users/send_msg_form/' . $post->getId_author(), 
							$icon_params
						);
					}
					
					
					$author_site = ($postAuthor->getUrl()) 
						? '&nbsp;' . get_link(
							get_img(
								'/template/' . getTemplateName() . '/img/icon_www.png', 
								array('alt' => __('Author site'), 'title' => __('Author site'))
							), 
							h($postAuthor->getUrl()), 
							array_merge($icon_params, array('target' => '_blank')), true) 
						: '';
				}



				$post->getAuthor()->setAuthor_site($author_site);
				$post->getAuthor()->setProfile_url($user_profile);
				$post->getAuthor()->setEmail_url($email);
				$post->getAuthor()->setPm_url($privat_message);
				
				
				// Если сообщение редактировалось...
				if ($post->getId_editor()) {
					if ($post->getId_author() && $post->getId_author() == $post->getId_editor()) {
						$editor = __('Edit by author') . ' ' . $post->getEdittime();
					} else {
						$status_info = $this->ACL->get_user_group($post->getEditor()->getStatus());
						$editor = __('Edited') . $post->getEditor()->getName() . '(' 
							. $status_info['title'] . ') ' . $post->getEdittime();
					}
				} else {
					$editor = '';
				}
				$post->setEditor_info($editor);
				
				
				//edit and delete links
				$edit_link = '';
				$delete_link = '';
				if (!empty($_SESSION['user'])) {
					if ($this->ACL->turn(array($this->module, 'edit_posts'), false) 
					|| (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id'] 
					&& $this->ACL->turn(array($this->module, 'edit_mine_posts'), false))) {
						$edit_link = get_link(__('Edit'), $this->getModuleURL('edit_post_form/' . $post->getId()));
					} 
					if ($this->ACL->turn(array($this->module, 'delete_posts'), false) 
					|| (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id'] 
					&& $this->ACL->turn(array($this->module, 'delete_mine_posts'), false))) {
						$delete_link = get_link(__('Delete'), $this->getModuleURL('delete_post/' . $post->getId()), array('onClick' => "return confirm('" . __('Are you sure') . "')"));
					}
				}
				$on_top = get_link(__('To top'), '#top', array(), true);
				$post->setOn_top_link($on_top);
				$post->setEdit_link($edit_link);
				$post->setDelete_link($delete_link);
				
				
				
				//message number
				$post_num++; $post->setPost_number($post_num);
				$post_number_url = 'http://' . $_SERVER['HTTP_HOST'] 
				. get_url($this->getModuleURL('view_theme/' . $id_theme . '?page=' . $page . '#post' . $post_num), true);
				$post->setPost_number_url($post_number_url);
		

				

				//set tags for cache
				$this->setCacheTag(array(
					'post_id_' . $post->getId(),
					'user_id_' . $post->getId_author(),
				));
			}
			$this->setCacheTag('theme_id_' . $id_theme);
			
			
			
			$markers = array(
				'reply_form' => $this->add_post_form($theme),
			);
			$this->_globalize($markers);
			
			
			$source = $this->render('posts_list.html', array(
				'posts' => $posts,
				'theme' => $theme,
			));
			
			
			//write into cache
			if ($this->cached)
				$this->Cache->write($source, $this->cacheKey, $this->cacheTags);
		}

		
		// Если страницу темы запросил зарегистрированный пользователь, значит он ее просмотрит
		if ( isset( $_SESSION['user'] ) and isset( $_SESSION['newThemes'] ) ) {
			if ( count( $_SESSION['newThemes'] ) > 0 ) {
				if ( in_array( $id_theme, $_SESSION['newThemes'] ) ) {
					unset( $_SESSION['newThemes'][$id_theme] );
				}
			} else {
				unset( $_SESSION['newThemes'] );
			}
		} 
		
		if (empty($_SESSION['VIEW_PAGE']) || $_SESSION['VIEW_PAGE'] != 'theme' . $id_theme) {
			$theme->setViews($theme->getViews() + 1);
			$theme->save();	
			$_SESSION['VIEW_PAGE'] = 'theme' . $id_theme;
		}
		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('action_viev_forum', 'theme_id_' . $id_theme));
		return $this->_view($source);
	}

	
	private function __checkThemeAccess($theme)
	{
		$fid = $theme->getForum()->getId();
		$rules = $theme->getGroup_access();
		$id = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;
		
		foreach ($rules as $k => $v) if ('' === $v) unset($rules[$k]);
		
		if (in_array($id, $rules)) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $fid));
		}
	}

	
	/**
	 * View last posts and last themes
	 * Build list with themes ordered by add date
	 *
	 * @return string html content
	 */
	public function last_posts() {
	
		$this->page_title .= ' - ' . __('Last update');

    
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$html = $this->Cache->read($this->cacheKey);
			return $this->_view($html);
        }
		
		
		// Page nav
		$nav = array();
		$themesModelName = $this->Register['ModManager']->getModelName('Themes');
		$themesModel = new $themesModelName;
		$total = $themesModel->getTotal();
		$perPage = Config::read('themes_per_page', $this->module);
        list($pages, $page) = pagination($total, $perPage, $this->getModuleURL('last_posts/'));
        $nav['pagination'] = $pages;
     	$this->page_title .= ' (' . $page . ')';

		
		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$nav['navigation'] = get_link(__('Home'), '/') . __('Separator') 
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('Last update');
		$nav['meta'] = __('Count all topics') . $total . '. ' . __('Count visible') . $recOnPage;
		$this->_globalize($nav);
		
		if ($total < 1) return $this->_view(__('No topics'));
		
		
		
		//get records
		$themesModel->bindModel($this->module);
		$themesModel->bindModel('author');
		$themesModel->bindModel('last_author');
		$themes = $themesModel->getCollection(array(), array(
			'order' => 'last_post DESC',
			'page' => $page,
			'limit' => Config::read('themes_per_page', $this->module),
		));
		
		
		foreach ($themes as $theme) {
			$theme_pf = get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
			$theme->setParent_forum($theme_pf);
			$theme = $this->__parseThemeTable($theme);
			
			//set cache tags
			$this->setCacheTag(array(
				'theme_id_' . $theme->getId(),
			));
		}
		
		
		// write into cache
		if ($this->cached) {
			$this->Cache->write($html, $this->cacheKey, $this->cacheTags);
		}
		
		//pr($themes); die();
		$source = $this->render('lastposts_list.html', array(
			'context' => array(
				'forum_name' => __('Last update'),
			),
			'themes' => $themes
		));
		$this->_view($source);
	}
	
	



	/**
	 * Create HTML form for edit forum and paste current values into inputs
	 */
	public function edit_forum_form($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'edit_forums'));
		if (!isset($_SESSION['user'])) redirect($this->getModuleURL());
		$id_forum = intval($id_forum);
		if (empty($id_forum) || $id_forum < 1) redirect($this->getModuleURL());

		
		
		$html = '';
		// Получаем из БД информацию о форуме
		$forum = $this->Model->getById($id_forum);
		$action = get_url($this->getModuleURL('update_forum/' . $id_forum));

		
		// Если при заполнении формы были допущены ошибки
		if (isset($_SESSION['editForumForm'])) {
			$info        = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editForumForm']['error'],
				),
			));
			$html        = $html . $info . "\n";
			$title       = h($_SESSION['editForumForm']['title']);
			$description = h($_SESSION['editForumForm']['description']);			
			unset($_SESSION['editForumForm']);
		} else {
			$title       = h($forum->getTitle());
			$description = h($forum->getDescription());
		}
		
		
		// Считываем в переменную содержимое файла,
		// содержащего форму для редактирования форума
		$source = $this->render('editforumform.html', array(
			'context' => array(
				'action' => $action,
				'title' => $title,
				'description' => $description,
			),
		));

		
		// nav block
		$navi = array(
			'navigation' => get_link(__('Home'), '/') . __('Separator') 
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('Edit forum'),
		);
		$this->_globalize($navi);
		
		
		$html = $html . $source;
		return $this->_view($html);
	}





	/**
	 * Get request and work for it. Validate data and update record
	 */
	public function update_forum($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'edit_forums'));
		if (!isset($_SESSION['user']))  redirect($this->getModuleURL());
		$id_forum = intval($id_forum);
		if (empty($id_forum) || $id_forum < 1) redirect($this->getModuleURL());

		
		$forum = $this->Model->getById($id_forum);
		if (!$forum) return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
		

		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title       = trim(mb_substr($_POST['title'], 0, 120 ));
		$description = trim(mb_substr($_POST['description'], 0, 250 ));

		
		// Check fields fo empty values and valid chars
		$error = '';
		$valobj = $this->Register['Validate'];
		if (empty($title))                          
			$error = $error .'<li>'. __('Empty field "forum name"') .'</li>'. "\n";
		elseif (!$valobj->cha_val($title, V_TITLE)) 
			$error = $error .'<li>'. __('Wrong chars in "forum name"') .'</li>'. "\n";


		// if an errors
		if (!empty($error)) {
			$_SESSION['editForumForm'] = array();
			$_SESSION['editForumForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'.
					"\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['editForumForm']['title'] = $title;
			$_SESSION['editForumForm']['description'] = $description;
			
			redirect($this->getModuleURL('edit_forum_form/' . $id_forum));
		}

		
		
		$forum->setTitle($title);
		$forum->setDescription($description);
		$forum->save();

		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('editing forum', 'forum id(' . $id_forum . ')');
		return $this->showInfoMessage(__('Forum update is successful'), $this->getModuleURL() );
	}





	/**
	* raise forum
	*
	* @id_forum (int)    forum ID
	* @return           info message
	*/
	public function forum_up($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'replace_forums'));
		if (!isset($_SESSION['user']))  redirect($this->getModuleURL());
		$id_forum = intval($id_forum);
		if (empty($id_forum) || $id_forum < 1) redirect($this->getModuleURL());
		

		
		// upper forum
		$id_forum_up = intval($id_forum);
		$forum = $this->Model->getById($id_forum_up);
		if (!$forum) return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
		// upper position
		$order_up = $forum->getPos();
		
		
		
		$dforum = $this->Model->getCollection(array(
			'pos < ' . $order_up, 
			'in_cat' => $forum->getIn_cat()
		), array(
			'order' => 'pos DESC',
			'limit' => 1,
		));
		if (!$dforum) return $this->showInfoMessage(__('Forum is above all'), $this->getModuleURL() );
		$dforum = $dforum[0];
		
	
		// Порядок следования и ID форума, который находится выше и будет "опущен" вниз
		// ( поменявшись местами с форумом, который "поднимается" вверх )
		$id_forum_down = $dforum->getId();
		$order_down    = $dforum->getPos();
		
		// replace forums
		$dforum->setPos($order_up);
		$res1 = $dforum->save();
		
		$forum->setPos($order_down);
		$res2 = $forum->save();

		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
		$this->Register['DB']->cleanSqlCache();
		
		if ($this->Log) $this->Log->write('uping forum', 'forum id(' . $id_forum . ')');
		if ($res1 && $res2)
			return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL() );
		else
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL() );
	}




	/**
	* down forum
	*
	* @id_forum (int)    forum ID
	* @return           info message
	*/
	public function forum_down($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'replace_forums'));
		if (!isset($_SESSION['user']))  redirect($this->getModuleURL());
		$id_forum = intval($id_forum);
		if (!isset($id_forum)) redirect($this->getModuleURL());
	
	
		
		// downing forum
		$id_forum_down = $id_forum;
		$forum = $this->Model->getById($id_forum_down);
		if (!$forum) return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
		// upper position
		$order_down = $forum->getPos();
		
		
		$dforum = $this->Model->getCollection(array(
			'pos > ' . $order_down, 
			'in_cat' => $forum->getIn_cat()
		), array(
			'order' => 'pos',
			'limit' => 1,
		));
		if (!$dforum) return true;
		$dforum = $dforum[0];
		
	
		// Порядок следования и ID форума, который находится ниже и будет "поднят" вверх
		// ( поменявшись местами с форумом, который "опускается" вниз )
		$id_forum_up = $dforum->getId();
		$order_up    = $dforum->getPos();
		
		// replace forums
		$dforum->setPos($order_down);
		$res1 = $dforum->save();
		
		$forum->setPos($order_up);
		$res2 = $forum->save();

		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
		$this->Register['DB']->cleanSqlCache();
		
		if ($this->Log) $this->Log->write('down forum', 'forum id(' . $id_forum . ')');
		if ($res1 && $res2)
			return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL() );
		else
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL() );
	}




	/**
	* delete forum
	*
	* @id_forum (int)    forum ID
	* @return            info message
	*/
	public function delete_forum($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'delete_forums'));
		$id_forum = (int)$id_forum;
		if (empty($id_forum) || $id_forum < 1) redirect($this->getModuleURL());
		
	
		$forum = $this->Model->getById($id_forum);
		if (!$forum) return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
		
		
		// Можно удалить только форум, который не содержит тем (в целях безопасности)
		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themes = $themeModel->getTotal(array('cond' => array('id_forum' => $id_forum)));
		if ($themes > 0) {
			return $this->showInfoMessage(__('Can not delete forum with themes'), $this->getModuleURL() );
		} else {
			$forum->delete();
		}
		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('delete forum', 'forum id(' . $id_forum . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL() );
	}





	/**
	* form per add theme into forum
	*
	* @id_forum (int)    forum ID
	* @return            html content
	*/
	public function add_theme_form($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'add_themes'));
		$id_forum = intval($id_forum);
		if (empty($id_forum) || $id_forum < 1) redirect($this->getModuleURL());
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;

		
		
		$forum = $this->Model->getById($id_forum);
		if (!$forum) redirect($this->getModuleURL());

		
		
		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($forum);
		
		
		$html    = '';
		$markers = array();
		
		// preview
		if (isset($_SESSION['viewMessage']) and !empty($_SESSION['viewMessage']['message'])) {
			$view = $this->render('previewmessage.html', array(
				'context' => array(
					'message' => $this->Textarier->print_page($_SESSION['viewMessage']['message'], $writer_status),
				),
			));
			$html = $html . $view . "\n";
			$theme = h($_SESSION['viewMessage']['theme']);
			$description = h($_SESSION['viewMessage']['description']);
			$message = $_SESSION['viewMessage']['message'];
			$gr_access = $_SESSION['viewMessage']['gr_access'];			
			unset( $_SESSION['viewMessage'] );
		}

		// errors
		if (isset($_SESSION['addThemeForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['addThemeForm']['error'],
				),
			));
			$html    = $html . $info . "\n";
			$theme   = h($_SESSION['addThemeForm']['theme']);
			$description = h($_SESSION['addThemeForm']['description']); 
			$message = $_SESSION['addThemeForm']['message'];			
			$gr_access = $_SESSION['addThemeForm']['gr_access'];			
			unset($_SESSION['addThemeForm']);
		}

		
		$markers = array(
			'action' => get_url($this->getModuleURL('add_theme/' . $id_forum)),
			'theme' => (!empty($theme)) ? $theme : '',
			'description' => (!empty($description)) ? $description : '',
			'main_text' => (!empty($message)) ? $message : '',
			'gr_access' => (!empty($gr_access)) ? $gr_access : array(),
		);

		$markers['users_groups'] = $this->Register['ACL']->getGroups();
		
		// nav block
		$navi = array();
		$navi['navigation'] = get_link(__('Home'), '/') . __('Separator') 
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') 
			. get_link(h($forum->getTitle()), $this->getModuleURL('view_forum/' . $id_forum));
		$this->_globalize($navi);
		
		
		$source = $this->render('addthemeform.html', array(
			'context' => $markers,
		));
		return $this->_view($source);
	}





	/**
	* add theme into forum
	*
	* @id_forum (int)    forum ID
	* @return            info message
	*/
	public function add_theme($id_forum = null) 
	{
		//check access
		$this->ACL->turn(array($this->module, 'add_themes'));
		if (!isset($id_forum) || !isset($_POST['theme']) || !isset($_POST['mainText'])) redirect($this->getModuleURL());
		$id_forum = intval($id_forum);
		if (empty($id_forum) || $id_forum < 1) redirect($this->getModuleURL());
		


		$forum = $this->Model->getById($id_forum);
		if (!$forum) redirect($this->getModuleURL());

		
		
		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($forum);
		
		
		// cut lenght
		$theme   = mb_substr($_POST['theme'], 0, 55);
		$message = $_POST['mainText'];
		$theme   = trim($theme);
		$description = trim(mb_substr($_POST['description'], 0, 128)); 
		$message = trim($message);
		
		$gr_access = array();
		$groups = $this->Register['ACL']->getGroups();
		foreach ($groups as $grid => $grval) {
			if (isset($_POST['gr_access_' . $grid])) $gr_access[] = $grid;
		}
		
		
		// preview
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage']['theme']   = $theme;
			$_SESSION['viewMessage']['description'] = $description; 
			$_SESSION['viewMessage']['message'] = $message;
			$_SESSION['viewMessage']['gr_access'] = $gr_access;
			redirect($this->getModuleURL('add_theme_form/' . $id_forum));
		}

		// Check fields of empty values and valid chars
		$error = '';
		$valobj = $this->Register['Validate'];
		if (empty($theme)) 
			$error = $error . '<li>' . __('Empty field "theme"') . '</li>'."\n";
		elseif (!$valobj->cha_val($theme, V_TITLE)) 
			$error = $error. '<li>' . __('Wrong chars in "theme"').'</li>'."\n";
		if (empty($message)) 
			$error = $error. '<li>' . __('Empty field "message"').'</li>'."\n";
		if (mb_strlen($message) > Config::read('max_post_lenght', $this->module)) 
			$error = $error . '<li>' . sprintf(__('Field "message" contains more symbols')
			, Config::read('max_post_lenght', $this->module)) . '</li>'."\n";

		for ($i = 1; $i < 6; $i++) {
			if (!empty($_FILES['attach' . $i]['name'])) {
				if ($_FILES['attach' . $i]['size'] > $this->getMaxSize()) {
					$error = $error . '<li>' . sprintf(__('Wery big file'), $i, ($this->getMaxSize() / 1024)) . '</li>'."\n";
				}
			}
		}
		// errors
		if (!empty($error)) {
			$_SESSION['addThemeForm'] = array();
			$_SESSION['addThemeForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'.
				"\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['addThemeForm']['theme'] = $theme;
			$_SESSION['addThemeForm']['description'] = $description; 
			$_SESSION['addThemeForm']['message'] = $message;
			$_SESSION['addThemeForm']['gr_access'] = $gr_access;
			redirect($this->getModuleURL('add_theme_form/' . $id_forum));
		}
		
		$message = mb_substr($message, 0, Config::read('max_post_lenght', $this->module));
		
		
		$user_id = (!empty($_SESSION['user'])) ? $_SESSION['user']['id'] : 0;
		
		$data = array(
			'title'          => $theme,
			'description'    => $description,
			'id_author'      => $user_id,
			'time'           => new Expr('NOW()'),
			'id_last_author' => $user_id,
			'last_post'      => new Expr('NOW()'),
			'id_forum'       => $id_forum, 
			'group_access'   => $gr_access, 
		);
		$theme = new ThemesEntity($data);
		$id_theme = $theme->save();
		if (!is_int($id_theme)) {
			$id_theme = mysql_insert_id();
		}

		
		// add first post
		$postData = array(
			'message'        => $message,
			'id_author'      => $user_id,
			'time'           => new Expr('NOW()'),
			'edittime'       => new Expr('NOW()'),
			'id_theme'       => $id_theme 
		);
		$post = new PostsEntity($postData);
		$post_id = $post->save();
		if (!is_int($post_id)) {
			$post_id = mysql_insert_id();
		}
		
		
		/***** END ATTACH *****/
		$attaches_exists = 0;
		// Массив недопустимых расширений файла вложения
		$extentions = $this->denyExtentions;
		$img_extentions = array('.png','.jpg','.gif','.jpeg', '.PNG','.JPG','.GIF','.JPEG');
		/* delete collizions if exists */
		$this->deleteCollizions($post, true);
		for ($i = 1; $i < 6; $i++) {
			$attach_name = 'attach' . $i;
			if (!empty($_FILES[$attach_name]['name'])) {
				// Извлекаем из имени файла расширение
				$ext = strrchr($_FILES[$attach_name]['name'], ".");
                $ext = strtolower($ext);
				// Формируем путь к файлу
				if (in_array(strtolower($ext), $extentions))
					$file = $post_id . '-' . $i . '-' . date("YmdHi") . '.txt';
				else
					$file = $post_id . '-' . $i . '-' . date("YmdHi") . $ext;
				$is_image = (in_array(strtolower($ext), $img_extentions)) ? 1 : 0;
				// Перемещаем файл из временной директории сервера в директорию files
				if (move_uploaded_file($_FILES[$attach_name]['tmp_name'], ROOT . '/sys/files/forum/' . $file)) {
					chmod(ROOT . '/sys/files/forum/' . $file, 0644);
					$attach_file_data = array(
						'post_id'       => $post_id,
						'theme_id'      => $id_theme,
						'user_id'       => $user_id,
						'attach_number' => $i,
						'filename'      => $file,
						'size'          => $_FILES[$attach_name]['size'],
						'date'          => new Expr('NOW()'),
						'is_image'      => $is_image,
					);
					
					$theme = new ThemesEntity($attach_file_data);
					if ($theme->save()) {
						$attaches_exists = 1;
					}
				}
			}
		}
		if ($attaches_exists == 1) {
			$postModel = $this->Register['ModManager']->getModelInstance('Posts');
			$post = $postModel->getById($post_id);
			$post->setAttaches(1);
			$post->save();
		}
		/***** END ATTACH *****/
		
		
		// Обновляем число оставленных сообщений и созданных тем
		if (!empty($_SESSION['user'])) {
			$userModel = $this->Register['ModManager']->getModelInstance('Users');
			$user = $userModel->getById($_SESSION['user']['id']);
			$user->setThemes($user->getThemes() + 1);
			$user->setPosts($user->getPosts() + 1);
			$user->save();
		}
		
		
		$forum = $this->Model->getById($id_forum);
		$forum->setThemes($forum->getThemes() + 1);
		$forum->setPosts($forum->getPosts() + 1);
		$forum->setLast_theme_id($id_theme);
		$forum->save();
		
		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array(
			'user_id_' . $user_id,
			'forum_id_' . $id_forum,
		));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('adding theme', 'theme id(' . $id_theme . '), post id(' . $post_id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $id_forum));
	}






	/**
	* form per edit theme
	*
	* @id_forum (int)    theme ID
	* @return            html content
	*/
	public function edit_theme_form($id_theme = null) 
	{
		$id_theme = (int)$id_theme;
		if (empty($id_theme) || $id_theme < 1) redirect($this->getModuleURL());


		// Получаем из БД информацию о редактируемой теме
		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themeModel->bindModel('author');
		$theme = $themeModel->getById($id_theme);
		if (!$theme) redirect($this->getModuleURL());
		
		
		$id_forum = $theme->getId_forum();
		$html = '';
		$markers = array();
		
		
		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_themes'), false) 
		&& (empty($_SESSION['user']['id']) || $theme->getId_author() != $_SESSION['user']['id'] 
		|| !$this->ACL->turn(array($this->module, 'edit_mine_themes'), false))) {
			return $this->showInfoMessage(__('Permission denied'), '/forum/view_forum/' . $id_forum );
		}

		
		// Если при заполнении формы были допущены ошибки
		if (isset($_SESSION['editThemeForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editThemeForm']['error'],
				),
			));
			$html    = $info . $html . "\n";
			$name = h($_SESSION['editThemeForm']['theme']);
			$description = h($_SESSION['editThemeForm']['description']); 
			$gr_access = $_SESSION['editThemeForm']['gr_access'];
			unset($_SESSION['editThemeForm']);
		} else {
			$name = h($theme->getTitle());
			$description = h($theme->getDescription()); 
			$gr_access = $theme->getGroup_access(); 
		}

		
		// Формируем список форумов, чтобы можно было переместить тему в другой форум
		$forums = $this->Model->getCollection(array(), array('order' => 'pos'));
		if (!$forums) redirect($this->getModuleURL());


		$options = '';
		foreach ($forums as $forum) {
			if ($forum->getId() == $theme->getId_forum())
				$options = $options.'<option value="'.$forum->getId().'" selected>'.h($forum->getTitle()).'</option>'."\n";
			else
				$options = $options.'<option value="'.$forum->getId().'">'.h($forum->getTitle()).'</option>'."\n";
		}


		$author_name = ($theme->getId_author()) ? h($theme->getAuthor()->getName()) : __('Guest');
		$data = array(
			'action' => get_url($this->getModuleURL('update_theme/' . $id_theme)),
			'theme' => $name,
			'description' => $description,
			'author' => $author_name,
			'options' => $options,
			'gr_access' => (!empty($gr_access)) ? $gr_access : array(),
		);
			
		$data['users_groups'] = $this->Register['ACL']->getGroups();
		
		// nav block
		$navi = array();
		$navi['navigation'] = get_link(__('Home'), '/') . __('Separator') 
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('Edit theme');
		$this->_globalize($navi);
		
		
		$source = $this->render('editthemeform.html', array(
			'context' => $data,
		));
		return $this->_view($source);
	}





	/**
	* update theme
	*
	* @id_forum (int)    theme ID
	* @return            info message
	*/
	public function update_theme($id_theme = null) {
		
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($id_theme) || !isset($_POST['id_forum']) || !isset($_POST['theme'])) {
			redirect($this->getModuleURL());
		}
		$id_theme = (int)$id_theme;
		$id_forum = (int)$_POST['id_forum'];
		if ($id_theme < 1 || $id_forum < 1) redirect($this->getModuleURL());
		
		
		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themeModel->getById($id_theme);
		if (!$theme) return $this->showInfoMessage(__('Theme does not exists'), $this->getModuleURL() );

		
		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$from_forum = $theme->getId_forum();
		$name = trim(mb_substr($_POST['theme'], 0, 55));
		$description = trim(mb_substr($_POST['description'], 0, 128)); 
		
		$gr_access = array();
		$groups = $this->Register['ACL']->getGroups();
		foreach ($groups as $grid => $grval) {
			if (isset($_POST['gr_access_' . $grid])) $gr_access[] = $grid;
		}
		
		
		// validate ...
		$error = '';
		$valobj = $this->Register['Validate'];
		if (empty($name))                  
			$error = $error. '<li>' . __('Empty field "theme"') . '</li>'."\n";
		elseif (!$valobj->cha_val($name, V_TITLE)) 
			$error = $error. '<li>' . __('Wrong chars in "theme"') . '</li>'."\n";

		// errors
		if (!empty($error)) {
			$_SESSION['editThemeForm'] = array();
			$_SESSION['editThemeForm']['error'] = '<p class="errorMsg">' . __('Some error in form') 
			. '</p>' . "\n" . '<ul class="errorMsg">'."\n".$error.'</ul>'."\n";
			$_SESSION['editThemeForm']['theme'] = $name;
			$_SESSION['editThemeForm']['description'] = $description;
			$_SESSION['editThemeForm']['gr_access'] = $gr_access;			
			redirect($this->getModuleURL('edit_theme_form/' . $id_theme));
		}
		
		
		
		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_themes'), false) 
		&& (empty($_SESSION['user']['id']) || $theme->getId_author() != $_SESSION['user']['id'] 
		|| !$this->ACL->turn(array($this->module, 'edit_mine_themes'), false))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $id_forum));
		}
		
		
		// update theme
		$theme->setTitle($name);
		$theme->setDescription($description);
		$theme->setId_forum($id_forum);
		$theme->setGroup_access($gr_access);
		$theme->save();
		
		
		//update forums info
		if ($from_forum != $id_forum) {
			$new_forum = $this->Model->getById($id_forum);
			if (!$new_forum) return $this->showInfoMessage(__('No forum for moving'), $this->getModuleURL());
			
			
			$postModel = $this->Register['ModManager']->getModelInstance();
			$posts_cnt = $postModel->getTotal(array('cond' => array('id_theme' => $id_theme)));
			$from_forum = $this->Model->getById($from_forum);
			$from_forum->setPosts($from_forum->getPosts() - $posts_cnt);
			$from_forum->setThemes($from_forum->getThemes() - 1);
			$from_forum->save();
			
			
			$from_forum = $this->Model->getById($id_forum);
			$from_forum->setPosts($from_forum->getPosts() + $posts_cnt);
			$from_forum->setThemes($from_forum->getThemes() + 1);
			$from_forum->save();

			
			$this->Model->upLastPost($from_forum, $id_forum);
		}

				
		//clean cahce
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('editing theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $id_forum));
		
	}


	
	


	/**
	 * Deleting theme
	 */
	public function delete_theme($id_theme = null) {
		$id_theme = intval($id_theme);
		if (empty($id_theme) || $id_theme < 1) redirect($this->getModuleURL());
		

		
		// if theme moved into another forum 
		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themeModel->getById($id_theme);
		if (!$theme) return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());
		
		
		//check access
		if (!$this->ACL->turn(array($this->module, 'delete_themes'), false) 
		|| (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id'] 
		&& !$this->ACL->turn(array($this->module, 'delete_mine_themes'), false))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}

		
		// delete colision ( this is paranoia )
		$this->Model->deleteCollisions();

		
		
		// Сперва мы должны удалить все сообщения (посты) темы;
		// начнем с того, что удалим файлы вложений
		$attachModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$posts = $postsModel->getCollection(array(
			'id_theme' => $id_theme,
		));
		if (is_array($posts)) {
			foreach ($posts as $post) {
				// Удаляем файл, если он есть
				$attach_files = $attachModel->getCollection(array('post_id' => $post->getId()));
				if (count($attach_files) > 0) {
					foreach ($attach_files as $attach_file) {
						if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
							if (@unlink(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
								$attach_file->delete();
							}
						}
					}
				}
				// заодно обновляем таблицу USERS
				if ($post->getId_author()) {
					$userModel = $this->Register['ModManager']->getModelInstance('Users');
					$user = $userModel->getById($post->getId_author());
					$user->setPosts($user->getPosts() - 1);
					$user->save();
				}
			}
		}
		
		
		$attach_files = $attachModel->getCollection(array('theme_id' => $id_theme));
		if (is_array($attach_files)) {
			foreach ($attach_files as $attach_file) {
				if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
					if (@unlink(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
						$attach_file->delete();
					}
				}
			}
		}
		
		
		
		//we must know id_forum
		$theme = $themeModel->getById($id_theme);
		//delete info
		$theme->delete();
		$postsModel->deleteByTheme($id_theme);
		
		
		//update info
		if ($theme) {
			$this->Model->upThemesPostsCounters($theme);
		}
		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme,));
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_forum', 'action_index'));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('delete theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Theme is deleted'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}




	/**
	 * Close Theme
	 */
	public function lock_theme($id_theme = null) {
		$this->ACL->turn(array($this->module, 'close_themes'));
		$id_theme = (int)$id_theme;
		if ($id_theme < 1) redirect($this->getModuleURL());
		
		
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme) return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL() );
		
		
		// Сначала заблокируем сообщения (посты) темы
		$posts = $postsModel->getCollection(array('id_theme' => $id_theme));
		if ($posts) {
			foreach ($posts as $post) {
				$post->setLocked('1');
				$post->save();
			}
		}

		// Теперь заблокируем тему
		$theme->setLocked('1');
		$theme->save();
		
		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('lock theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Theme is locked'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}





	/**
	 * Unlocking Theme
	 */
	public function unlock_theme($id_theme = null) {
		$this->ACL->turn(array($this->module, 'close_themes'));
		$id_theme = (int)$id_theme;
		if ($id_theme < 1) redirect($this->getModuleURL());
		

		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme) return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL() );
		
		
		// Сначала заблокируем сообщения (посты) темы
		$posts = $postsModel->getCollection(array('id_theme' => $id_theme));
		if ($posts) {
			foreach ($posts as $post) {
				$post->setLocked('0');
				$post->save();
			}
		}

		// Теперь заблокируем тему
		$theme->setLocked('0');
		$theme->save();
		
		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('unlock theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Theme is open'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}





	/**
	 * Create reply form
	 *
	 * @param array $theme Theme info
	 * @return string HTML reply form
	 */ 
	private function add_post_form($theme = null) {
		if (empty($theme)) return null;
		$id_theme = (int)$theme->getId();
		if ($id_theme < 1) return null;
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;


		if ($this->ACL->turn(array($this->module, 'add_posts'), false)) {
			if ($theme->getLocked() == 1) {
				$html = '<div class="not-auth-mess">' . __('Theme is locked') . '</div>';
			} else {


				$message = '';
				$html = '';
				$markers = array();
				if (isset($_SESSION['viewMessage']) and !empty($_SESSION['viewMessage'])) {
					$view = $this->render('previewmessage.html', array(
						'context' => array(
							'message' => $this->Textarier->print_page($_SESSION['viewMessage'], $writer_status),
						),
					));
					$html    = $html . $view . "<script>window.location.href=\"#preview\";</script>\n";
					$message = h($_SESSION['viewMessage']);
					unset($_SESSION['viewMessage']);
				}

				
				// Если при заполнении формы были допущены ошибки
				if (isset($_SESSION['addPostForm'])) {
					$info = $this->render('infomessage.html', array(
						'context' => array(
							'info_message' => $_SESSION['addPostForm']['error'],
						),
					));
					$html    = $html . $info . "\n";
					$message = h($_SESSION['addPostForm']['message']);
					unset($_SESSION['addPostForm']);
				}

				
				$source = $this->render('replyform.html', array(
					'context' => array(
						'action' => get_url($this->getModuleURL('add_post/' . $id_theme)),
						'main_text' => $message,
					),
				));
				$html = $html . $source;
			}
			
			
		} else {
			if (isset($_SESSION['user']))
				$html = '<div class="not-auth-mess">' . __('Dont have permission to write post') . '</div>';
			else
				$html = '<div class="not-auth-mess">' . __('Guests cant write posts') . '</div>';
		}
		
		return $html;
	}





	/**
	 * Adding new record into posts table
	 *
	 * @param int $id_theme
	 */
	public function add_post($id_theme = null) {
		$this->ACL->turn(array($this->module, 'add_posts'));
		if (empty($id_theme) || !isset($_POST['mainText'])) redirect($this->getModuleURL());
		$id_theme = (int)$id_theme;
		if ($id_theme < 1) redirect($this->getModuleURL());
		

		
		// Проверяем, не заблокирована ли тема?
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme) redirect($this->getModuleURL());
		if ($theme->getLocked() == 1)
			return $this->showInfoMessage(__('Can not write in a closed theme'), $this->getModuleURL('view_theme/' . $id_theme));
			
			
		
		// Check access to this forum. May be locked by pass or posts count
		$forum = $this->Model->getById($theme->getId_forum());
		if (!$forum)  return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL() );
		$this->__checkForumAccess($forum);
		

		// Обрезаем сообщение (пост) до длины $set[$this->module]['max_post_lenght']
		$message = trim($_POST['mainText']);
		// Если пользователь хочет посмотреть на сообщение перед отправкой
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage'] = $message;
			redirect($this->getModuleURL('view_theme/' . $id_theme));
		}
		
		
		
		// Проверяем, правильно ли заполнены поля формы
		$error = '';
		if (empty($message)) $error = $error . '<li>' . __('Empty field "message"') . '</li>'."\n";
		if (strlen($message) > Config::read('max_post_lenght', $this->module)) 
			$error = $error . '<li>' . sprintf(__('Field "message" contains more symbols')
			, Config::read('max_post_lenght', $this->module)) . '</li>'."\n";
		
		
		$gluing = true;
		for ($i = 1; $i < 6; $i++) {
			if (!empty($_FILES['attach' . $i]['name'])) {
				if ($_FILES['attach' . $i]['size'] > Config::read('max_file_size')) {
					$error = $error . '<li>' . sprintf(__('Wery big file'), $i
					, (Config::read('max_file_size')/1024)) . '</li>'."\n";
				}
				//if exists attach files we do not gluing posts
				$gluing = false;
			}
		}
		
		
		// errors
		if (!empty($error)) {
			$_SESSION['addPostForm'] = array();
			$_SESSION['addPostForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'."\n".
			'<ul class="errorMsg">'."\n".$error.'</ul>'."\n";
			$_SESSION['addPostForm']['message'] = $message;
			redirect($this->getModuleURL('view_theme/' . $id_theme));
		}

		
		$message = mb_substr($message, 0, Config::read('max_post_lenght', $this->module));
		$id_user = (!empty($_SESSION['user'])) ? $_SESSION['user']['id'] : 0;
		// Защита от того, чтобы один пользователь не добавил
		// 100 сообщений за одну минуту
		if (isset($_SESSION['unix_last_post']) && (time() - $_SESSION['unix_last_post'] < 10) ) {
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL('view_theme/' . $id_theme));
		}	
		
		
		//gluing posts
		if ($gluing === true) {
			$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
			$prev_post = $postsModel->getCollection(array(
				'id_theme' => $id_theme,
			), array(
				'order' => 'time DESC, id DESC',
				'limit' => 1,
			));
			if ($prev_post) {
				$prev_post_author = $prev_post[0]->getId_author();
			}
			
			if (empty($prev_post_author)) $gluing = false;
			if ((mb_strlen($prev_post[0]->getMessage() . $message)) > Config::read('max_post_lenght', $this->module)) $gluing = false;
			if ($prev_post_author != $id_user || empty($id_user)) $gluing = false;
		}		
		
		
		
		if ($gluing === true) {
			$message = $prev_post[0]->getMessage() . "\n\n" . '[color=939494]' 
			. __('Added') . date("Y.m.d  H-i") . "[/color]\n\n" . $message;
			
			$prev_post[0]->setMessage($message);
			$prev_post[0]->setTime(new Expr('NOW()'));
			$prev_post[0]->save();
			
			$theme->setLast_post(new Expr('NOW()'));
			$theme->save();

			$forum->setLast_theme_id($id_theme);
			$forum->save();

			
			
		} else {   // NOT GLUING MESSAGE
			// Все поля заполнены правильно - выполняем запрос к БД
			$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
			$post_data = array(
				'message'   => $message,
				'id_author' => $id_user,
				'time'      => new Expr('NOW()'),
				'id_theme'  => $id_theme
			);
			$post = new PostsEntity($post_data);
			$post->save();
			$post_id = mysql_insert_id();
			
			
			$attaches_exists = 0;
			// Массив недопустимых расширений файла вложения
			$extentions = $this->denyExtentions;
			$img_extentions = array('.png','.jpg','.gif','.jpeg', '.PNG','.JPG','.GIF','.JPEG');
			$file_types = array('image/jpeg','image/jpg','image/gif','image/png');
			/* delete collizions if exists */
			$this->deleteCollizions($post, true);
			for ($i = 1; $i < 6; $i++) {
				$attach_name = 'attach' . $i;
				if (!empty($_FILES[$attach_name]['name'])) {
					// Извлекаем из имени файла расширение
					$ext = strrchr($_FILES[$attach_name]['name'], ".");
                    $ext = strtolower($ext);
					// Формируем путь к файлу
					if (in_array(strtolower($ext), $extentions) || empty($ext)) {
						$file = $post_id . '-' . $i . '-' . date("YmdHi") . '.txt';
					} else {
						$file = $post_id . '-' . $i . '-' . date("YmdHi") . $ext;
					}

					$is_image = '0';
					if (in_array($_FILES[$attach_name]['type'], $file_types)) {
						$is_image = '1';
					}

					// Перемещаем файл из временной директории сервера в директорию files
					if (move_uploaded_file($_FILES[$attach_name]['tmp_name'], ROOT . '/sys/files/forum/' . $file)) {
						chmod(ROOT . '/sys/files/forum/' . $file, 0644);
						$attach_file_data = array(
							'post_id'       => $post_id,
							'theme_id'      => $id_theme,
							'user_id'       => $id_user,
							'attach_number' => $i,
							'filename'      => $file,
							'size'          => $_FILES[$attach_name]['size'],
							'date'          => new Expr('NOW()'),
						);
                        if($is_image) $attach_file_data['is_image'] = $is_image;
						
						$attach = new ForumAttachesEntity($attach_file_data);
						if ($attach->save()) {
							$attaches_exists = 1;
						}
					}
				}
			}
			
			
			if ($attaches_exists == 1) {
				$post->setAttaches(1);
				$post->save();
			}
			
			
			$cnt_posts_from_theme = $postsModel->getTotal(array('cond' => array('id_theme' => $id_theme)));
			$theme->setPosts(($cnt_posts_from_theme - 1));
			$theme->setId_last_author($id_user);
			$theme->save();


			// speed spam protected
			$_SESSION['unix_last_post'] = time();

			
			// Обновляем количество сообщений для зарегистрированного пользователя
			if (isset($_SESSION['user'])) {
				$usersModel = $this->Register['ModManager']->getModelInstance('Users');
				$user = $usersModel->getById($id_user);
				$user->setPosts($user->getPosts() + 1);
				$user->save();
			}
			
			
			//update forum info
			$forum->setPosts($forum->getPosts() + 1);
			$forum->setLast_theme_id($id_theme);
			$forum->save();
		}		
		
		

		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme, 'user_id_' . $id_user));
		$this->Register['DB']->cleanSqlCache();
		
		
		if ($gluing === false) {
			if ($this->Log) $this->Log->write('adding post', 'post id(' . $post_id . '), theme id(' . $id_theme . ')');
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL('view_theme/' 
			. $id_theme . '?page=999#post' . $cnt_posts_from_theme));
		} else {
			if ($this->Log) $this->Log->write('adding post', 'post id(*gluing), theme id(' . $id_theme . ')');
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL('view_theme/' 
			. $id_theme . '?page=999#post' . $prev_post[0]->getPosts()));
		}
	}





	/**
	 * Create Edit post form
	 *
	 * @param int $id Post ID
	 */
	public function edit_post_form($id = null) {
		$id = (int)$id;
		if ($id < 1) redirect($this->getModuleURL());
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;


		// Получаем из БД сообщение
		$postModel = $this->Register['ModManager']->getModelInstance('Posts');
		$post = $postModel->getById($id);
		if (!$post)
			return $this->showInfoMessage(__('Some error occurred'),  $this->getModuleURL() );
		
		$id_theme = $post->getId_theme();		
		
		
		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_posts'), false) 
		&& (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'edit_mine_posts'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		$message = $post->getMessage();
		$html    = '';
		$markers = array();
		
		//if user vant preview message
		if (isset($_SESSION['viewMessage']) and !empty($_SESSION['viewMessage'])) {
			$view = $this->render('previewmessage.html', array(
				'context' => array(
					'message' => $this->Textarier->print_page($_SESSION['viewMessage'], $writer_status),
				),
			));
			$html    = $html . $view . "\n";
			$message = $_SESSION['viewMessage'];
			unset($_SESSION['viewMessage']);
		}

		// errors
		if (isset($_SESSION['editPostForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editPostForm']['error'],
				),
			));
			$html    = $info . $html . "\n";
			$message = $_SESSION['editPostForm']['message'];
			unset($_SESSION['editPostForm']);
		}

		
		
		$markers = array(
			'action' => get_url($this->getModuleURL('update_post/' . $id)),
			'main_text' => h($message),
		);
		
		
		/****  ATTACH  ****/
		$unlinkfiles = array('att1' => '', 'att2' => '', 'att3' => '', 'att4' => '', 'att5' => '',);
		if ($post->getAttaches()) {
			$attahModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
			$attach_files = $attachModel->getCollection(array('post_id' => $post->getId()));
			if ($attach_files) {
				foreach ($attach_files as $attach_file) {
					if (file_exists(ROOT . '/sys/files/forum/' . $attach_file->getFilename())) {
						$unlinkfiles['att'.$attach_file->getAttach_number()] = '<input type="checkbox" name="unlink' . $attach_file->getAttach_number() 
						. '" value="1" />&nbsp;' . __('Delete') ."\n";
					}
				}
			}
		}
		$markers['unlinkfiles'] = $unlinkfiles;
		/****  END  ATTACH  ****/

		
		// nav block
		$navi = array();
		$navi['navigation'] = get_link(__('Home'), '/') . __('Separator') 
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator')
		. get_link('Просмотр темы', $this->getModuleURL('view_theme/' . $id_theme) . __('Separator') . __('Edit message'));
		$this->_globalize($navi);
		
		
		setReferer();
		$source = $html . $this->render('editpostform.html', array('context' => $markers));
		return $this->_view($source);
	}



	/**
	 * Update Post record
	 *
	 * @param int $id Post ID
	 */
	public function update_post($id = null) {
		// Если не переданы данные формы - значит функция была вызвана по ошибке
		if (empty($id) || !isset($_POST['mainText'])) redirect($this->getModuleURL());
		$id = (int)$id;
		if ($id < 1) redirect($this->getModuleURL());


		// Проверяем, имеет ли пользователь право редактировать это сообщение (пост)
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$post = $postsModel->getById($id);
		if (!$post) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL() );
		$id_theme = $post->getId_theme();
		
		
		
		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_posts'), false) 
		&& (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'edit_mine_posts'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}

		// Обрезаем сообщение до длины $set[$this->module]['max_post_lenght']
		$message = trim($_POST['mainText']);

		// Preview
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage'] = $message;
			redirect($this->getModuleURL('edit_post_form/' . $id));
		}
		
		

		// check fields...
		$error = '';
		if (empty($message))   
			$error = $error . '<li>' . __('Empty field "message"') . '</li>'."\n";
		if (mb_strlen($message) > Config::read('max_post_lenght', $this->module)) 
			$error = $error . '<li>' . sprintf(__('Very big message'), Config::read('max_post_lenght', $this->module)) . '</li>'."\n";
		// check attach 
		for ($i = 1; $i <= 5; $i++) {
			if (!empty($_FILES['attach' . $i]['name'])) {
				if ($_FILES['attach' . $i]['size'] > Config::read('max_file_size')) {
					$error = $error . '<li>' . sprintf(__('Wery big file'), $i
					, (Config::read('max_file_size') / 1024)) . '</li>'."\n";
				}
			}
		}
		
		
		/* if an error */
		if (!empty($error)) {
			$_SESSION['editPostForm'] = array();
			$_SESSION['editPostForm']['error'] = '<p class="errorMsg">' . __('Some error in form')
			. '</p>' . "\n" . '<ul class="errorMsg">'."\n".$error.'</ul>'."\n";
			$_SESSION['editPostForm']['message'] = $message;
			redirect($this->getModuleURL('edit_post_form/' . $id));
		}
		
		
		$user_id = (!empty($_SESSION['user'])) ? $_SESSION['user']['id'] : 0;
		
		
		
		/*****   ATTACH   *****/
		// Массив недопустимых расширений файла вложения
		$extentions = array('.php', '.phtml', '.php3', '.html', '.htm', '.pl', '.PHP', '.PHTML', '.PHP3', '.HTML', '.HTM', '.PL');
		$img_extentions = array('.png','.jpg','.gif','.jpeg', '.PNG','.JPG','.GIF','.JPEG');
		$allowed_types = array('image/jpeg','image/jpg','image/gif','image/png');
		$attachModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		for ($i = 1; $i <= 5; $i++) {
			if (!empty($_POST['unlink' . $i]) || !empty($_FILES['attach' . $i]['name'])) {
				$unlink_file = $attachModel->getCollection(array(
					'post_id' => $id,
					'attach_number' => $i,
				), array('limit' => 1));
				/* may be collizions */
				if (count($unlink_file) > 1) $this->deleteCollizions($post, true);
				if (!empty($unlink_file) && file_exists(ROOT . '/sys/files/forum/' . $unlink_file[0]->getFilename())) {
					if(unlink(ROOT . '/sys/files/forum/' . $unlink_file[0]->getFilename())) {	
						$unlink_file->delete(); 
					}
				}
			}
			
			$attach_name = 'attach' . $i;
			if (!empty($_FILES[$attach_name]['name'])) {
			
				// Извлекаем из имени файла расширение
				$ext = strrchr($_FILES[$attach_name]['name'], ".");
                $ext = strtolower($ext);
				// Формируем путь к файлу
				if (in_array( $ext, $extentions)) {
					$file = $id . '-' . $i . '-' . date("YmdHi") . '.txt';
				} else {
					$file = $id . '-' . $i . '-' . date("YmdHi") . $ext;
				}
				
				$is_image = 0;
				if (in_array($_FILES[$attach_name]['type'], $allowed_types)) {
					$is_image = 1;
				}
				
				
				// Перемещаем файл из временной директории сервера в директорию files
				if (move_uploaded_file($_FILES[$attach_name]['tmp_name'], ROOT . '/sys/files/forum/' . $file)) {
					chmod(ROOT . '/sys/files/forum/' . $file, 0644);
					$attach_file_data = array(
						'post_id'       => $id,
						'theme_id'      => $id_theme,
						'user_id'       => $user_id,
						'attach_number' => $i,
						'filename'      => $file,
						'size'          => $_FILES[$attach_name]['size'],
						'date'          => new Expr('NOW()'),
						'is_image'      => $is_image,
					);
					$newattach = new ForumAttachesEntity($attach_file_data);
					$newattach->save();
				}
				
			}
		}
		$attach_exists = $attachModel->getCollection(array('post_id' => $id));
		$attach_exists = ($attach_exists > 0) ? 1 : 0;
		/*****  END ATTACH   *****/

		
		
		// Все поля заполнены правильно - выполняем запрос к БД
		$message = mb_substr($message, 0, Config::read('max_post_lenght', $this->module));
		$post->setMessage($message);
		$post->setAttaches($attach_exists);
		$post->setId_editor($user_id);
		$post->setEdittime(new Expr('NOW()'));
		$post->save();
		

		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('post_id_' . $id));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('editing post', 'post id(' . $id . '), theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Operation is successful'), getReferer());
	}




	/**
	* deleting post from forum
	* @id     post ID
	* @return none
	*/
	public function delete_post($id = null) {
		$id = intval($id);
		if (empty($id) || $id < 1) redirect($this->getModuleURL());
		

		// Получаем из БД информацию об удаляемом сообщении - это нужно,
		// чтобы узнать, имеет ли право пользователь удалить это сообщение
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$post = $postsModel->getById($id);
		if (!$post) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL() );

		
		//check access
		if (!$this->ACL->turn(array($this->module, 'delete_posts'), false) 
		&& (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'delete_mine_posts'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}

		
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($post->getId_theme());

		// Удаляем файл, если он есть
		$attachModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		if ($post->getAttaches()) {
			$attach_files = $attachModel->getCollection(array('post_id' => $id));
			if (count($attach_files) > 0) {
				foreach ($attach_files as $attach_file) {
					if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
						if (@unlink(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
							$attach_file->delete();
						}
					}
				}
			}
		}
		$post->delete();
		
		// Если это - единственное сообщение темы, то надо удалить и тему
		$postscnt = $postsModel->getTotal(array('cond' => array('id_theme' => $post->getId_theme())));
		
		
		if ($theme->getId_author()) {
			$userModel = $this->Register['ModManager']->getModelInstance('Users');
			$user = $userModel->getById($theme->getId_author());
		}
		
		
		$deleteTheme = false;
		if ($postscnt == 0) {
			if ($user) {
				// Прежде чем удалять тему, надо обновить таблицу TABLE_USERS
				$user->setThemes($user->getThemes() - 1); 
				$user->save();
			}
			
			$theme->delete();
			// Если мы удалили тему, то мы не можем в нее вернуться;
			// поэтому редирект будет на страницу форума, а не страницу темы
			$deleteTheme = true;
		}
		
		
		if ($user) {
			// Обновляем количество сообщений, оставленных автором сообщения ...
			$user->setPosts($user->getPosts() - 1);
			$user->save();
		}
		
		
		// ... и таблицу .themes
		if (!$deleteTheme) {
			$lastPost = $postsModel->getCollection(array(
				'id_theme' => $post->getId_theme(),
			), array(
				'limit' => 1, 
				'order' => 'id DESC'
			));

			$id_last_author = $lastPost[0]->getId_author();
			$last_post = $lastPost[0]->getTime();
			$theme->setId_last_author($id_last_author);
			$theme->setLast_post($last_post);
			$theme->setPosts($postscnt - 1);
			$theme->save();

		}

		//clean cache
		$cahceKey = array('post_id_' . $id);
		if (isset($deleteTheme)) $cahceKey[] = 'theme_id_' . $post->getId_theme();
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, $cahceKey);
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('delete post', 'post id(' . $id . '), theme id(' . $post->getId_theme() . ')');
		
		
		//update forum info
		$lastTheme = $themesModel->getCollection(array(
			'id_forum' => $theme->getId_forum(),
		), array(
			'limit' => 1,
			'order' => '`last_post` DESC',
		));
		$forum = $this->Model->getById($theme->getId_forum());
		if ($deleteTheme) {
			$forum->setThemes($forum->getThemes() - 1);
			$forum->setPosts($forum->getPosts() - 1);
			$forum->setLast_theme_id(count($lastTheme) > 0 ? $lastTheme[0]->getId() : '0');
			$forum->save();
			return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
			
		} else {
			$forum->setPosts($forum->getPosts() - 1);
			$forum->setLast_theme_id(count($lastTheme) > 0 ? $lastTheme[0]->getId() : '0');
			$forum->save();
			return $this->showInfoMessage(__('Operation is successful'), getReferer());
		}
	}



	
	/**
	 * View post for users
	 *
	 * @param ind $user_id
	 * @return none
	 */
	public function user_posts($user_id) 
	{
		$this->page_title .= ' - ' . __('User messages');
		$html = '';
    
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$html = $this->Cache->read($this->cacheKey);
			return $this->_view($html);
        }
		
		
		
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$total = $themesModel->getTotal(array('cond' => array('id_author' => $user_id)));
		$perPage = Config::read('themes_per_page', $this->module);
        list($pages, $page) = pagination($total, $perPage, '/forum/user_posts/' . $user_id);
		
		
		// Page nav
		$nav = array();
        $nav['pagination'] = $pages;
        $this->page_title .= ' (' . $page . ')';


		
		$recOnPage = ($page == $this->Register['pagecnt']) ? ($total % $perPage) : $perPage;
		if ($recOnPage > $total) $recOnPage = $total;
		$nav['navigation'] = get_link(__('Home'), '/') . __('Separator') 
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('User messages');
		$nav['meta'] = __('Count all topics') . $total . '. ' . __('Count visible') . $recOnPage;
		$this->_globalize($nav);
		
		if ($total < 1) return $this->_view(__('No topics'));
		
		
		
		//get records
		$themesModel->bindModel('author');
		$themesModel->bindModel('last_author');
		$themesModel->bindModel('postslist');
		$themesModel->bindModel($this->module);
		$themes = $themesModel->getCollection(array(
			'id_author' => $user_id,
		), array(
			'order' => 'time DESC',
			'group' => 'id',
			'page' => $page,
			'limit' => Config::read('themes_per_page', $this->module),
		));
		

		foreach ($themes as $theme) {
			$parent_forum = get_link($theme->getForum()->getTitle()
				, $this->getModuleURL('view_forum/' . $theme->getId_forum()));
			$theme->setParent_forum($parent_forum);
			$theme = $this->__parseThemeTable($theme);

			
			//set cache tags
			$this->setCacheTag(array(
				'theme_id_' . $theme->getId(),
			));
		}			
		
		
		// write into cache
		if ($this->cached) {
			$this->Cache->write($html, $this->cacheKey, $this->cacheTags);
		}
		
		//pr($themes); die();
		$source = $this->render('lastposts_list.html', array(
			'context' => array(
				'forum_name' => __('User messages'),
			),
			'themes' => $themes
		));
		$this->_view($source);
	}
	

	
	/**
	* @return forum statistic block
	*/
	protected function _get_stat() {
		$markers = array();
		$result = $this->Model->getStats();
		
		
		if (!empty($result[0]['last_user_id']) && !empty($result[0]['last_user_name'])) {
			$markers['new_user'] = get_link(h($result[0]['last_user_name']), 
			'/users/info/' . $result[0]['last_user_id']);
		}
		$markers['count_users'] = getAllUsersCount();
		$markers['count_posts'] = (!empty($result[0]['posts_cnt'])) ? $result[0]['posts_cnt'] : 0;
		$markers['count_themes'] = (!empty($result[0]['themes_cnt'])) ? $result[0]['themes_cnt'] : 0;

		
		$html = $this->render('get_stat.html', $markers);
		return $html;
	}



	
	public function download_file($file = null, $mimetype = 'application/octet-stream') {
		if (empty($file)) redirect('/');
		
		$path = ROOT . $this->getFilesPath($file);
		if (!file_exists($path)) die(__('File not found'));
		$from = 0;
		$size = filesize($path);
		$to = $size;
		$range = array();
		if (isset($_SERVER['HTTP_RANGE'])) {
			if (preg_match ('#bytes=-([0-9]*)#',$_SERVER['HTTP_RANGE'],$range)) {// если указан отрезок от конца файла
				$from = $size-$range[1];
				$to = $size;
			} elseif(preg_match('#bytes=([0-9]*)-#',$_SERVER['HTTP_RANGE'],$range)) {// если указана только начальная метка

				$from = $range[1];
				$to = $size;
			} elseif(preg_match('#bytes=([0-9]*)-([0-9]*)#',$_SERVER['HTTP_RANGE'],$range)) {// если указан отрезок файла

				$from = $range[1];
				$to = $range[2];
			}
			header('HTTP/1.1 206 Partial Content');

			$cr='Content-Range: bytes '.$from .'-'.$to.'/'.$size;
		} else
			header('HTTP/1.1 200 Ok');
		
		$etag = md5($path);
		$etag = substr($etag, 0, 8) . '-' . substr($etag, 8, 7) . '-' . substr($etag, 15, 8);
		header('ETag: "'.$etag.'"');
		header('Accept-Ranges: bytes');
		header('Content-Length: ' .($to-$from));
		if (isset($cr)) header($cr);
		header('Connection: close');	
		header('Content-Type: ' . $mimetype);
		header('Last-Modified: ' . gmdate('r', filemtime($path)));
		header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime($path))." GMT");
		header("Expires: ".gmdate("D, d M Y H:i:s", time() + 3600)." GMT");
		$f=fopen($path, 'rb');


		if (preg_match('#^image/#',$mimetype))
			header('Content-Disposition: filename="'.$file.'";');
		else
			header('Content-Disposition: attachment; filename="'.$file.'";');

		fseek($f, $from, SEEK_SET);
		$size = $to;
		$downloaded=0;
		while(!feof($f) and ($downloaded<$size)) {
			$block = min(1024*8, $size - $downloaded);
			echo fread($f, $block);
			$downloaded += $block;
			flush();
		}
		fclose($f);
	}

	
	public function important($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'important_themes'));
		$id = (int)$id;
		if ($id < 1) redirect($this->getModuleURL());
		
		
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id);
		if (!$theme) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		
		$theme->setImportant('1');
		$theme->save();

		/* clean cache DB*/
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('important post', 'theme id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}
	
	
	public function unimportant($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'important_themes'));
		$id = (int)$id;
		if ($id < 1) redirect($this->getModuleURL());
		
		
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id);
		if (!$theme) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		
		$theme->setImportant('0');
		$theme->save();

		/* clean cache DB */
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('unimportant post', 'theme id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}
	
	
	
	/**
	* deleting attaches  collizion 
	*
	* @post (array)   reply data
	* @clean(boolean) clean all or only collizions
	* @return         none
	*/
	private function deleteCollizions($post, $clean = false) {
		/* DB has file */
		$attachModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		$attachments = $attachModel->getCollection(array('post_id' => $post->getId()));
		if ($clean === true) {
			if (count($attachments) && is_array($attachments))
				foreach ($attachments as $attach)
					$attach->delete();
					
					
		} else {
			if (count($attachments) && is_array($attachments)) {
				foreach ($attachments as $key => $attach) {
					if (file_exists(ROOT . $this->getFilesPath($attach->getFilename()))) {
						clearstatcache();
						continue;
					}
					$attach->delete();
					unset($attachments[$key]);
				}
			}
		}
		
		
		/* File has DB record */
		$attach_files = glob(ROOT . $this->getFilesPath($post->getId() . '-*'));
		if (!empty($attach_files)) {
			foreach ($attach_files as $_key => $attach_file) {
				if ($clean === true) {
					@unlink($attach_file);
					continue;
				}
				$record_exists = false;
				if (count($attachments) && is_array($attachments)) {
					foreach ($attachments as $attach) {
						if (strrchr($attach_file, '/') == $attach->getFilename()) {
							$record_exists = true;
							break;
						}
					}
				}
				if ($record_exists === false) {
					unset($attach_files[$_key]);
					@unlink($attach_file);
				}
			}
		}
		if ($clean === true) return;
		/* posts.attaches flag */
		$flag = (!empty($attach_files) && !empty($attachments)) ? '1' : '0';
		if ($flag != $post->getAttaches()) {
			$post->setAttaches($flag);
			$post->save();
		}
		return;
	}



	//delete theme
	private function __delete_theme($id_theme) {
		$usersModel = $this->Register['ModManager']->getModelInstance('Users');
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$attachesModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		
		
		//we must know id_forum
		$theme = $themesModel->getById($id_theme);
	
		// delete colision ( this is paranoia )
		$themes = $this->Model->deleteThemesPostsCollisions();


		// Сперва мы должны удалить все сообщения (посты) темы;
		// начнем с того, что удалим файлы вложений
		$posts = $postsModel->getCollection(array('id_theme' => $id_theme));
		if (count($posts) && is_array(posts)) {
			foreach ($posts as $file) {
				// Удаляем файл, если он есть
				$attach_files = $attachesModel->getCollection(array('post_id' => $file->getId()));
				if (count($attach_files) && is_array($attach_files)) {
					foreach ($attach_files as $attach_file) {
						if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
							if (@unlink(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
								$attach_file->delete();
							}
						}
					}
				}
				// заодно обновляем таблицу USERS
				if ($file->getId_author()) {
					$user = $usersModel->getById($file->getId_author());
					if ($user) {
						$user->setPosts($user->getPosts() - 1);
						$user->save();
					}
				}
			}
		}
		
		
		$attach_files = $attachesModel->getCollection(array('theme_id' => $id_theme));
		if (count($attach_files) && is_array($attach_files)) {
			foreach ($attach_files as $attach_file) {
				if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
					if (@unlink(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
						$attach_file->delete();
					}
				}
			}
		}

		
		
		if (count($theme) && is_array($theme)) {
			// Обновляем таблицу TABLE_USERS - надо обновить поле themes
			$themes_cnt = $themesModel->getTotal(array('cond' => array('id_author' => $theme->getId_author())));
			$posts_cnt = $postsModel->getTotal(array('cond' => array('id_author' => $theme->getId_author())));
			$user = $usersModel->getById($theme->getId_author());
			if ($user) {
				$user->setThemes($themes_cnt);
				$user->setPosts($posts_cnt);
				$user->save();
			}

			
			//update forum info
			$this->Model->updateForumCounters($theme->getId_forum());
		}
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme,));
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_forum', 'action_index'));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('delete theme(because error uccured)', 'theme id(' . $id_theme . ')');
	}	
	
}