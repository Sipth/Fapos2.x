<?php####################################################												#### Author:       Andrey Brykin (Drunya)         #### Version:      1.0                            #### Project:      CMS                            #### package       CMS Fapos                      #### subpackege    Admin Panel module             #### copyright     ©Andrey Brykin 2010-2012       #### last mod.     2012/07/18                     ########################################################################################################												#### any partial or not partial extension         #### CMS Fapos,without the consent of the         #### author, is illegal                           ###################################################### Любое распространение                        #### CMS Fapos или ее частей,                     #### без согласия автора, является не законным    ####################################################include_once '../sys/boot.php';include_once ROOT . '/admin/inc/adm_boot.php';$pageTitle = 'Настройки ЧПУ';$set = Config::read('all');$TempSet = $set;if (isset($_POST['send'])) {	$TempSet['hlu_extention']   	  = $_POST['hlu_extention'];	$TempSet['hlu']       	          = (!empty($_POST['hlu'])) ? 1 : 0;	$TempSet['hlu_understanding']     = (!empty($_POST['hlu_understanding'])) ? 1 : 0;		$TempSet['autotags_active']     = (!empty($_POST['autotags_active'])) ? 1 : 0;	$TempSet['autotags_exception']  = $_POST['autotags_exception'];	$TempSet['autotags_priority']   = $_POST['autotags_priority'];			//save data	Config::write($TempSet);	redirect("/admin/settings_seo.php");}$pageNav = $pageTitle;$pageNavl = '';include_once ROOT . '/admin/template/header.php';?><form method="POST" action="settings_seo.php"><table class="settings-tb"><tr><td class="left">Включить ЧПУ:<br></td><td><input type="checkbox" name="hlu" value="1" <?php echo (!empty($set['hlu'])) ? 'checked="checked"' : '' ?>><br></td></tr><tr><td class="left">Разбор ЧПУ:<br><span class="comment">Новые ссылки будут обычными, но обращение<br /> через ЧПУ будет поддерживаться для работоспособности старых ссылок</span><br></td><td><input type="checkbox" name="hlu_understanding" value="1" <?php echo (!empty($set['hlu_understanding'])) ? 'checked="checked"' : '' ?>><br></td></tr><tr><td class="left">Окончание URL:<br><span class="comment">Например .html</span><br></td><td><input type="text" name="hlu_extention" value="<?php echo (!empty($set['hlu_extention'])) ? h($set['hlu_extention']) : ''; ?>"><br></td></tr><tr class="small"><td class="group" colspan="2"><?php echo __('Auto tags settings');  ?></td></tr><tr><td class="left"><?php echo __('Status'); ?>:<br></td><td><input type="checkbox" name="autotags_active" value="1" <?php if (!empty($set['autotags_active'])) echo 'checked="checked"'; ?> /><br></td></tr><tr><td class="left"><?php echo __('Exceptions'); ?>:<br><span class="comment"><?php echo __('Throught coma'); ?></span><br></td><td><input type="text" name="autotags_exception" value="<?php echo (!empty($set['autotags_exception'])) ? h($set['autotags_exception']) : ''; ?>"><br></td></tr><tr><td class="left"><?php echo __('Priority'); ?>:<br><span class="comment"><?php echo __('Throught coma'); ?></span><br></td><td><input type="text" name="autotags_priority" value="<?php echo (!empty($set['autotags_priority'])) ? h($set['autotags_priority']) : ''; ?>"><br></td></tr><tr><td colspan="2" align="center"><input type="submit" name="send" value="Сохранить"><br></td></tr></table></form><?phpinclude_once 'template/footer.php';?>