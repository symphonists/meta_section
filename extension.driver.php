<?php

	require_once(TOOLKIT . '/class.sectionmanager.php');

	class extension_meta_section extends Extension {

		var $sectionManager;
		var $callback;

		public function __construct($args){
			$this->_Parent =& $args['parent'];

			$this->sectionManager = new SectionManager($this->_Parent);
			$this->callback = Administration::instance()->getPageCallback();
		}
		
		public function about() {
			return array(
				'name' => 'Meta Section',
				'version' => '0.1',
				'release-date' => '2010-11-23',
				'author' => array(
					'name' => 'Rainer Borene',
					'website' => 'http://rainerborene.com',
					'email' => 'me@rainerborene.com'
				)
			);
		}
		
		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePostGenerate',
					'callback' => 'manipulateOutput'
				),
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => 'appendResources'
				),
				array(
					'page' => '/publish/edit/',
					'delegate' => 'EntryPostEdit',
					'callback' => 'entryPostEdit'
				)
			);
		}
		
		public function manipulateOutput($context) {
			if ($this->inBlueprints()) {
				$this->customizeEssentials($context);
			}
			else if ($_REQUEST['ms_filtering'] === 'yes'){
				$this->sendElementsToTrash($context);
			}
		}

		public function appendResources($context) {
			if ($this->inPublish()) {
				$section_handle = $this->callback['context']['section_handle'];
				$section = $this->sectionFromHandle($section_handle);
				
				$meta_section = $this->sectionManager->fetch($section->get('meta_section'));

				if (!is_object($meta_section)){
					return;
				}
				
				$handle = $meta_section->get('handle');
				
				$page = Administration::instance()->Page;
				$page->addStylesheetToHead(URL . '/extensions/meta_section/assets/metasection.css', 'screen', 190);
				$page->addScriptToHead(URL . "/extensions/meta_section/assets/metasection.js?section={$handle}", 195);
			}
		}
		
		public function entryPostEdit($context) {
			$section = $context['section'];
			$entry = $context['entry'];
			
			if ($section->get('static') === 'yes' && $_REQUEST['ms_filtering'] === 'yes'){
				$prepopulate_field_id = $prepopulate_value = NULL;
				if(isset($_POST['prepopulate'])){
					$prepopulate_field_id = array_shift(array_keys($_POST['prepopulate']));
					$prepopulate_value = stripslashes(rawurldecode(array_shift($_POST['prepopulate'])));
				}

	  		   	redirect(sprintf(
					'%s/symphony/publish/%s/edit/%d/saved/%s',
					URL,
					$section->get('handle'),
					$entry->get('id'),
					(!is_null($prepopulate_field_id) ? ":{$prepopulate_field_id}:{$prepopulate_value}" : NULL) . "?ms_filtering=yes"
				));
			}
		}
	
		
		public function sectionFromHandle($handle){
			$section_id = $this->sectionManager->fetchIDFromHandle($handle);
			return $this->sectionManager->fetch($section_id);
		}
		
		public function getSection() {
			if ($this->callback['driver'] === 'blueprintssections' && is_array($this->callback['context']) && isset($this->callback['context'][1])){
				return $this->sectionManager->fetch($this->callback['context'][1]);
			}
			
			return false;
		}
		
		public function customizeEssentials($context) {
			$dom = @DOMDocument::loadHTML($context['output']);
			$xpath = new DOMXPath($dom);
			
			$nav_group = $xpath->query("/html/body/form/fieldset/div/div[2]")->item(0);
			
			$sectionManager = new SectionManager($this->_Parent);
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');

			$select = $dom->createElement('select');
			$select->setAttribute('name', 'meta[meta_section]');
			
			$none_option = $dom->createElement('option');
			$select->appendChild($none_option);

			$current_section = $this->getSection();
			$errors = Administration::instance()->Page->_errors;
			
			foreach ($sections as $section) {
				if ($section->get('static') === 'yes') {
					if (is_object($current_section) && $section->get('id') === $current_section->get('id')){
						continue;
					}
					
					$option = $dom->createElement('option');
					$option->setAttribute('value', $section->get('id'));
					
					if ((is_object($current_section) && $current_section->get('meta_section') === $section->get('id')) || 
						(is_array($errors) && !empty($errors) && $_POST['meta']['meta_section'] === $section->get('id'))) {
							$option->setAttribute('selected', 'selected');
					}
					
					$option->appendChild(new DOMText($section->get('name')));
					$select->appendChild($option);
				}
			}
			
			$label = $dom->createElement('label', 'Meta section');
			$label->appendChild($select);
			
			$nav_group->appendChild($label);
			
			$context['output'] = $dom->saveHTML();
		}
		
		public function sendElementsToTrash($context){
			$dom = @DOMDocument::loadHTML($context['output']);
			$xpath = new DOMXPath($dom);

			$body = $xpath->query("//body")->item(0);
			$body->setAttribute('style', 'background:transparent');

			$h1 = $xpath->query("//form/h1")->item(0);
			$h1->parentNode->removeChild($h1);
			
			$h2 = $xpath->query("//form/h2")->item(0);
			$h2->parentNode->removeChild($h2);
			
			$nav = $xpath->query("//ul[@id='nav']")->item(0);
			$nav->parentNode->removeChild($nav);
			
			$version = $xpath->query("//p[@id='version']")->item(0);
			$version->parentNode->removeChild($version);
			
			$usr = $xpath->query("//ul[@id='usr']")->item(0);
			$usr->parentNode->removeChild($usr);
			
			$form = $xpath->query("//form")->item(0);
			$form->setAttribute('style', 'margin-top:0px; padding:0px 19px 0px 19px; min-height:100px;');

			$new_form_url = $form->getAttribute('action') . '?ms_filtering=yes';
			$form->setAttribute('action', $new_form_url);
			
			$notice = $xpath->query("//p[@id='notice']")->item(0);
			if ($notice) {
				$notice->parentNode->removeChild($notice);
				// $notice->setAttribute('style', 'margin:0px; position:absolute; left:0px; top:0px; width:100%;');
				// $form->setAttribute('style', 'margin-top:0px; padding:35px 19px 0px 19px; min-height:100px;');
			}
			
			$context['output'] = $dom->saveHTML();
		}
		
		// Returns true if user is creating or editing a section
		public function inBlueprints() {
			return ($this->callback['driver'] === 'blueprintssections' && is_array($this->callback['context']));
		}
		
		public function inPublish() {
			return ($this->callback['driver'] === 'publish' && is_array($this->callback['context']) && $this->callback['context']['page'] === 'index');
		}
		
		public function install(){
			return Administration::instance()->Database->query("ALTER TABLE `tbl_sections` ADD `meta_section` int UNSIGNED NULL DEFAULT NULL AFTER `hidden`");
		}
		
		public function uninstall(){
			return Administration::instance()->Database->query("ALTER TABLE `tbl_sections` DROP `meta_section`");
		}
	
	}