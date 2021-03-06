<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2011
 * @author     Leo Feyer <http://www.contao.org>
 * @package    Newsletter
 * @license    LGPL
 * @filesource
 */


/**
 * Table tl_newsletter_recipients
 */
$GLOBALS['TL_DCA']['tl_newsletter_recipients'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_newsletter_channel',
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_newsletter_recipients', 'checkPermission')
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('email'),
			'panelLayout'             => 'filter;sort,search,limit',
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'useSMTP'),
			'child_record_callback'   => array('tl_newsletter_recipients', 'listRecipient'),
			'child_record_class'      => 'no_padding'
		),
		'global_operations' => array
		(
			'import' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['import'],
				'href'                => 'key=import',
				'class'               => 'header_css_import',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this, %s);"',
				'button_callback'     => array('tl_newsletter_recipients', 'toggleIcon')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{email_legend},email,active',
	),

	// Fields
	'fields' => array
	(
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['email'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'maxlength'=>128, 'decodeEntities'=>true),
			'save_callback' => array
			(
				array('tl_newsletter_recipients', 'checkUniqueRecipient')
			)
		),
		'active' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['active'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true)
		),
		'source' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['source'],
			'eval'                    => array('fieldType'=>'checkbox', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'csv', 'class'=>'mandatory')
		),
		'addedOn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['addedOn'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 8,
			'eval'                    => array('rgxp'=>'datim')
		),
		'ip' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter_recipients']['ip'],
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 11
		)
	)
);


/**
 * Class tl_newsletter_recipients
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2011
 * @author     Leo Feyer <http://www.contao.org>
 * @package    Controller
 */
class tl_newsletter_recipients extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}


	/**
	 * Check permissions to edit table tl_newsletter_recipients
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (!is_array($this->User->newsletters) || count($this->User->newsletters) < 1)
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->newsletters;
		}

		$id = strlen($this->Input->get('id')) ? $this->Input->get('id') : CURRENT_ID;

		// Check current action
		switch ($this->Input->get('act'))
		{
			case 'create':
				if (!strlen($this->Input->get('pid')) || !in_array($this->Input->get('pid'), $root))
				{
					$this->log('Not enough permissions to create newsletters recipients in channel ID "'.$this->Input->get('pid').'"', 'tl_newsletter_recipients checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}
				break;

			case 'edit':
			case 'show':
			case 'copy':
			case 'delete':
			case 'toggle':
				$objRecipient = $this->Database->prepare("SELECT pid FROM tl_newsletter_recipients WHERE id=?")
											   ->limit(1)
											   ->execute($id);

				if ($objRecipient->numRows < 1)
				{
					$this->log('Invalid newsletter recipient ID "'.$id.'"', 'tl_newsletter_recipients checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}

				if (!in_array($objRecipient->pid, $root))
				{
					$this->log('Not enough permissions to '.$this->Input->get('act').' recipient ID "'.$id.'" of newsletter channel ID "'.$objRecipient->pid.'"', 'tl_newsletter_recipients checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}
				break;

			case 'select':
			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
				if (!in_array($id, $root))
				{
					$this->log('Not enough permissions to access newsletter channel ID "'.$id.'"', 'tl_newsletter_recipients checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}

				$objRecipient = $this->Database->prepare("SELECT id FROM tl_newsletter_recipients WHERE pid=?")
											 ->execute($id);

				if ($objRecipient->numRows < 1)
				{
					$this->log('Invalid newsletter recipient ID "'.$id.'"', 'tl_newsletter_recipients checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}

				$session = $this->Session->getData();
				$session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $objRecipient->fetchEach('id'));
				$this->Session->setData($session);
				break;

			default:
				if (strlen($this->Input->get('act')))
				{
					$this->log('Invalid command "'.$this->Input->get('act').'"', 'tl_newsletter_recipients checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}
				elseif (!in_array($id, $root))
				{
					$this->log('Not enough permissions to access newsletter recipient ID "'.$id.'"', 'tl_newsletter_recipients checkPermission', TL_ERROR);
					$this->redirect('contao/main.php?act=error');
				}
				break;
		}
	}


	/**
	 * Check if recipients are unique per channel
	 * @param mixed
	 * @param object
	 * @return mixed
	 */
	public function checkUniqueRecipient($varValue, DataContainer $dc)
	{
		$objRecipient = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_recipients WHERE email=? AND pid=(SELECT pid FROM tl_newsletter_recipients WHERE id=?) AND id!=?")
									   ->execute($varValue, $dc->id, $dc->id);

		if ($objRecipient->count > 0)
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['unique'], $GLOBALS['TL_LANG'][$dc->table][$dc->field][0]));
		}

		return $varValue;
	}


	/**
	 * List a recipient
	 * @param array
	 * @return string
	 */
	public function listRecipient($row)
	{
		$label = $row['email'];

		if ($row['addedOn'])
		{
			$label .= ' <span style="color:#b3b3b3; padding-left:3px;">(' . sprintf($GLOBALS['TL_LANG']['tl_newsletter_recipients']['subscribed'], $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $row['addedOn'])) . ')</span>';
		}
		else
		{
			$label .= ' <span style="color:#b3b3b3; padding-left:3px;">(' . $GLOBALS['TL_LANG']['tl_newsletter_recipients']['manually'] . ')</span>';
		}

		return sprintf('<div style="float:left;"><div class="list_icon" style="background-image:url(\'%ssystem/themes/%s/images/%s.gif\');">%s</div></div>', TL_SCRIPT_URL, $this->getTheme(), ($row['active'] ? 'member' : 'member_'), $label) . "\n";
	}


	/**
	 * Return the "toggle visibility" button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if (strlen($this->Input->get('tid')))
		{
			$this->toggleVisibility($this->Input->get('tid'), ($this->Input->get('state') == 1));
			$this->redirect($this->getReferer());
		}

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_newsletter_recipients::active', 'alexf'))
		{
			return '';
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['active'] ? '' : 1);

		if (!$row['active'])
		{
			$icon = 'invisible.gif';
		}		

		return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}


	/**
	 * Disable/enable a user group
	 * @param integer
	 * @param boolean
	 */
	public function toggleVisibility($intId, $blnVisible)
	{
		// Check permissions to edit
		$this->Input->setGet('id', $intId);
		$this->Input->setGet('act', 'toggle');
		$this->checkPermission();

		// Check permissions to publish
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_newsletter_recipients::active', 'alexf'))
		{
			$this->log('Not enough permissions to publish/unpublish newsletter recipient ID "'.$intId.'"', 'tl_newsletter_recipients toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		$this->createInitialVersion('tl_newsletter_recipients', $intId);
	
		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_newsletter_recipients']['fields']['active']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_newsletter_recipients']['fields']['active']['save_callback'] as $callback)
			{
				$this->import($callback[0]);
				$blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
			}
		}

		// Update the database
		$this->Database->prepare("UPDATE tl_newsletter_recipients SET tstamp=". time() .", active='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
					   ->execute($intId);

		$this->createNewVersion('tl_newsletter_recipients', $intId);
	}
}

?>