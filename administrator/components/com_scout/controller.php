<?php
/**
 * @version	0.1
 * @package	Scout
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class ScoutController extends JController
{
	/**
	 * @var array() instances of Models to be used by the controller
	 */
	public $_models = array();
	
    /**
     * @var string the default view
     */
    public $_defaultView = 'logs';

	/**
	 * constructor
	 */
	function __construct()
	{
		parent::__construct();
		$this->set('suffix', $this->get('_defaultView') );

		// Register Extra tasks
		$this->registerTask( 'list', 'display' );
		$this->registerTask( 'close', 'cancel' );
		$this->registerTask( 'add', 'edit' );
		$this->registerTask( 'new', 'edit' );
		$this->registerTask( 'apply', 'save' );
		$this->registerTask( 'savenew', 'save' );
		$this->registerTask( 'remove', 'delete' );
		$this->registerTask( 'publish', 'enable' );
		$this->registerTask( 'unpublish', 'enable' );
		$this->registerTask( 'disable', 'enable' );
		$this->registerTask( 'saveorder', 'ordering' );
		$this->registerTask( 'page_tooltip_enable', 'pagetooltip_switch' );
		$this->registerTask( 'page_tooltip_disable', 'pagetooltip_switch' );
	}

	/**
	* 	display the view
	*/
	function display($cachable=false)
	{
		// this sets the default view
		JRequest::setVar( 'view', JRequest::getVar( 'view', $this->get('_defaultView') ) );

		$document =& JFactory::getDocument();

		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd( 'view', $this->getName() );
		$viewLayout	= JRequest::getCmd( 'layout', 'default' );

		$view = & $this->getView( $viewName, $viewType, '', array( 'base_path'=>$this->_basePath));

		// Get/Create the model
		if ($model = & $this->getModel($viewName))
		{
			// controller sets the model's state - this is why we override parent::display()
			$this->_setModelState();
			// Push the model into the view (as default)
			$view->setModel($model, true);
		}

		// Set the layout
		$view->setLayout($viewLayout);

		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('onBeforeDisplayAdminComponentScout', array() );

		// Display the view
		if ($cachable && $viewType != 'feed') {
			global $option;
			$cache =& JFactory::getCache($option, 'view');
			$cache->get($view, 'display');
		} else {
			$view->display();
		}

		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('onAfterDisplayAdminComponentScout', array() );

		$this->footer();
	}

    /**
     * Gets the view's namespace for state variables
     * @return string
     */
    function getNamespace()
    {
    	$app = JFactory::getApplication();
    	$model = $this->getModel( $this->get('suffix') );
		$ns = $app->getName().'::'.'com.scout.model.'.$model->getTable()->get('_suffix');
    	return $ns;
    }

	/**
	 * Sets the model's default state based on values in the request
	 *
	 * @return array()
	 */
    function _setModelState()
    {
		$app = JFactory::getApplication();
		$model = $this->getModel( $this->get('suffix') );
		$ns = $this->getNamespace();

		$state = array();

        $state['limit']  	= $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $state['limitstart'] = $app->getUserStateFromRequest($ns.'limitstart', 'limitstart', 0, 'int');
        $state['order']     = $app->getUserStateFromRequest($ns.'.filter_order', 'filter_order', 'tbl.'.$model->getTable()->getKeyName(), 'cmd');
        $state['direction'] = $app->getUserStateFromRequest($ns.'.filter_direction', 'filter_direction', 'ASC', 'word');
        $state['filter']    = $app->getUserStateFromRequest($ns.'.filter', 'filter', '', 'string');
        $state['filter_enabled'] 	= $app->getUserStateFromRequest($ns.'enabled', 'filter_enabled', '', '');
        $state['id']        = JRequest::getVar('id', 'post', JRequest::getVar('id', 'get', '', 'int'), 'int');
        $state['filter_id_from']    = $app->getUserStateFromRequest($ns.'id_from', 'filter_id_from', '', '');
        $state['filter_id_to']      = $app->getUserStateFromRequest($ns.'id_to', 'filter_id_to', '', '');
        $state['filter_name']       = $app->getUserStateFromRequest($ns.'name', 'filter_name', '', '');
        $state['filter_value']       = $app->getUserStateFromRequest($ns.'value', 'filter_value', '', '');
        
        // TODO santize the filter
        // $state['filter']   	=

    	foreach (@$state as $key=>$value)
		{
			$model->setState( $key, $value );
		}
  		return $state;
    }

    /**
     * Gets the model
     * We override parent::getModel because parent::getModel always creates a new Model instance
     *
     */
	function getModel( $name = '', $prefix = '', $config = array() )
	{
		if ( empty( $name ) ) {
			$name = $this->getName();
		}

		if ( empty( $prefix ) ) {
			$prefix = $this->getName() . 'Model';
		}

		$fullname = strtolower( $prefix.$name );
		if (empty($this->_models[$fullname]))
		{
			if ( $model = & $this->_createModel( $name, $prefix, $config ) )
			{
				// task is a reserved state
				$model->setState( 'task', $this->_task );

				// Lets get the application object and set menu information if its available
				$app	= &JFactory::getApplication();
				$menu	= &$app->getMenu();
				if (is_object( $menu ))
				{
					if ($item = $menu->getActive())
					{
						$params	=& $menu->getParams($item->id);
						// Set Default State Data
						$model->setState( 'parameters.menu', $params );
					}
				}
			}
				else
			{
				$model = new JModel();
			}
			$this->_models[$fullname] = $model;
		}

		return $this->_models[$fullname];
	}

	/**
	 * Displays item
	 * @return void
	 */
	function view()
	{
		JRequest::setVar( 'view', $this->get('suffix') );
		JRequest::setVar( 'layout', 'view' );
		parent::display();
		$this->footer();
	}

	/**
	 * Checks if an item is checked out, and if so, redirects to layout for viewing item
	 * Otherwise, displays a form for editing item
	 *
	 * @return void
	 */
	function edit()
	{
		JRequest::setVar( 'view', $this->get('suffix') );
		$model 	= $this->getModel( $this->get('suffix') );
	    $row = $model->getTable();
	    $row->load( $model->getId() );
	    $userid = JFactory::getUser()->id;

	    // Checks if item is checkedout, and if so, redirects to view
		if (!JTable::isCheckedOut($userid, $row->checked_out))
		{
			if ($row->checkout( $userid ))
			{
				JRequest::setVar( 'hidemainmenu', '1' );
				JRequest::setVar( 'layout', 'form' );
				parent::display();
			}
		}
			else
		{
			JRequest::setVar( 'layout', 'view' );
			parent::display();
		}
		
		$this->footer();
	}

	/**
	 * Releases an item from being checked out for editing
	 * @return unknown_type
	 */
	function release()
	{
		$model 	= $this->getModel( $this->get('suffix') );
	    $row = $model->getTable();
	    $row->load( $model->getId() );
		if (isset($row->checked_out) && !JTable::isCheckedOut( JFactory::getUser()->id, $row->checked_out) )
		{
			if ($row->checkin())
			{
				$this->message = JText::_( "Item Released" );
			}
		}

    	$redirect = "index.php?option=com_scout&controller=".$this->get('suffix')."&view=".$this->get('suffix')."&task=view&id=".$model->getId()."&donotcheckout=1";
    	$redirect = JRoute::_( $redirect, false );
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}

	/**
	 * Cancels operation and redirects to default page
	 * If item is checked out, releases it
	 * @return void
	 */
	function cancel()
	{
        if (!isset($this->redirect)) {
            $this->redirect = 'index.php?option=com_scout&view='.$this->get('suffix');        
        }

		$task = JRequest::getVar( 'task' );
		switch (strtolower($task))
		{
			case "cancel":
				$msg = JText::_( 'Operation Cancelled' );
				$type = "notice";
			  break;
			case "close":
			default:
				$model 	= $this->getModel( $this->get('suffix') );
			    $row = $model->getTable();
			    $row->load( $model->getId() );
				if (isset($row->checked_out) && !JTable::isCheckedOut( JFactory::getUser()->id, $row->checked_out) )
				{
					$row->checkin();
				}
				$msg = "";
				$type = "";
			  break;
		}

	    $this->setRedirect( $this->redirect, $msg, $type );
	}

	/**
	 * Saves an item and redirects based on task
	 * @return void
	 */
	function save()
	{
		$model 	= $this->getModel( $this->get('suffix') );
	    $row = $model->getTable();
	    $row->load( $model->getId() );
		$row->bind( $_POST );

		if ( $row->save() )
		{
			$model->setId( $row->id );
			$this->messagetype 	= 'message';
			$this->message  	= JText::_( 'Saved' );

			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger( 'onAfterSave'.$this->get('suffix'), array( $row ) );
		}
			else
		{
			$this->messagetype 	= 'notice';
			$this->message 		= JText::_( 'Save Failed' )." - ".$row->getError();
		}

    	$redirect = "index.php?option=com_scout";
    	$task = JRequest::getVar('task');
    	switch ($task)
    	{
    		case "savenew":
    			$redirect .= '&view='.$this->get('suffix').'&task=add';
    		  break;
    		case "apply":
    			$redirect .= '&view='.$this->get('suffix').'&task=edit&id='.$model->getId();
    		  break;
    		case "save":
    		default:
    			$redirect .= "&view=".$this->get('suffix');
    		  break;
    	}

    	$redirect = JRoute::_( $redirect, false );
		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}

	/**
	 * Deletes record(s) and redirects to default layout
	 */
	function delete()
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
        if (!isset($this->redirect)) {
            $this->redirect = JRequest::getVar( 'return' )
                ? base64_decode( JRequest::getVar( 'return' ) )
                : 'index.php?option=com_scout&view='.$this->get('suffix');
            $this->redirect = JRoute::_( $this->redirect, false );
        }

		$model = $this->getModel($this->get('suffix'));
		$row = $model->getTable();

		$cids = JRequest::getVar('cid', array (0), 'request', 'array');
		foreach (@$cids as $cid)
		{
			if (!$row->delete($cid))
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}

		if ($error)
		{
			$this->message = JText::_('Error') . " - " . $this->message;
		}
			else
		{
			$this->message = JText::_('Items Deleted');
		}

		$this->setRedirect( $this->redirect, $this->message, $this->messagetype );
	}

	/**
	 * Reorders a single item either up or down (based on arrow-click in list) and redirects to default layout
	 * @return void
	 */
	function order()
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$redirect = 'index.php?option=com_scout&view='.$this->get('suffix');
		$redirect = JRoute::_( $redirect, false );

		$model = $this->getModel($this->get('suffix'));
		$row = $model->getTable();
		$row->load( $model->getId() );

		$change	= JRequest::getVar('order_change', '0', 'post', 'int');

		if ( !$row->move( $change ) )
		{
			$this->messagetype 	= 'notice';
			$this->message 		= JText::_( 'Ordering Failed' )." - ".$row->getError();
		}

		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}

	/**
	 * Reorders multiple items (based on form input from list) and redirects to default layout
	 * @return void
	 */
	function ordering()
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$redirect = 'index.php?option=com_scout&view='.$this->get('suffix');
		$redirect = JRoute::_( $redirect, false );

		$model = $this->getModel($this->get('suffix'));
		$row = $model->getTable();

		$ordering = JRequest::getVar('ordering', array(0), 'post', 'array');
		$cids = JRequest::getVar('cid', array (0), 'post', 'array');
		foreach (@$cids as $cid)
		{
			$row->load( $cid );
			$row->ordering = @$ordering[$cid];

			if (!$row->store())
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}

		$row->reorder();

		if ($error)
		{
			$this->message = JText::_('Error') . " - " . $this->message;
		}
			else
		{
			$this->message = JText::_('Items Ordered');
		}

		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}

	/**
	 * Changes the value of a boolean in the database
	 * Expects the task to be in the format: {field}_{action}
	 * where {field} = the name of the field in the database
	 * and {action} is either switch/enable/disable
	 *
	 * @return unknown_type
	 */
	function boolean()
	{
		$error = false;
		$this->messagetype	= '';
		$this->message 		= '';
		$redirect = 'index.php?option=com_scout&view='.$this->get('suffix');
		$redirect = JRoute::_( $redirect, false );

		$model = $this->getModel($this->get('suffix'));
		$row = $model->getTable();

		$cids = JRequest::getVar('cid', array (0), 'post', 'array');
		$task = JRequest::getVar( 'task' );
		$vals = explode('.', $task);

		$field = $vals['0'];
		$action = $vals['1'];

		switch (strtolower($action))
		{
			case "switch":
				$switch = '1';
			  break;
			case "disable":
				$enable = '0';
				$switch = '0';
			  break;
			case "enable":
				$enable = '1';
				$switch = '0';
			  break;
			default:
				$this->messagetype 	= 'notice';
				$this->message 		= JText::_( "Invalid Task" );
				$this->setRedirect( $redirect, $this->message, $this->messagetype );
				return;
			  break;
		}

		if ( !in_array( $field, array_keys( $row->getProperties() ) ) )
		{
			$this->messagetype 	= 'notice';
			$this->message 		= JText::_( "Invalid Field" ).": {$field}";
			$this->setRedirect( $redirect, $this->message, $this->messagetype );
			return;
		}

		foreach (@$cids as $cid)
		{
			unset($row);
			$row = $model->getTable();
			$row->load( $cid );

			switch ($switch)
			{
				case "1":
					$row->$field = $row->$field ? '0' : '1';
				  break;
				case "0":
				default:
					$row->$field = $enable;
				  break;
			}

			if ( !$row->save() )
			{
				$this->message .= $row->getError();
				$this->messagetype = 'notice';
				$error = true;
			}
		}

		if ($error)
		{
			$this->message = JText::_('Error') . ": " . $this->message;
		}
			else
		{
			$this->message = JText::_('Status Changed');
		}

		$this->setRedirect( $redirect, $this->message, $this->messagetype );
	}

	/*
	 * Wrapper for boolean() for easy backwards compatability
	 */
	function enable()
	{
		$task = JRequest::getVar( 'task' );
		switch (strtolower($task))
		{
			case "switch_publish":
				$field = 'published';
				$action = 'switch';
			  break;
			case "switch":
			case "switch_enable":
				$field = 'enabled';
				$action = 'switch';
			  break;
			case "unpublish":
				$field = 'published';
				$action = 'disable';
			  break;
			case "disable":
				$field = 'enabled';
				$action = 'disable';
			  break;
			case "publish":
				$field = 'published';
				$action = 'enable';
			  break;
			case "enable":
			default:
				$field = 'enabled';
				$action = 'enable';
			  break;
		}
		JRequest::setVar( 'task', $field.'.'.$action );
		$this->boolean();
	}

	/**
	 * Hides a tooltip message
	 * @return unknown_type
	 */
	function pagetooltip_switch()
	{
		$msg = new stdClass();
		$msg->type 		= '';
		$msg->message 	= '';
		$view = JRequest::getVar('view');
		$msg->link 		= 'index.php?option=com_scout&view='.$view;

		$key = JRequest::getVar('key');
		$constant = 'page_tooltip_'.$key;
		$config_title = $constant."_disabled";

			$database = &JFactory::getDBO();
			JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_scout'.DS.'tables'.DS );
			unset($table);
			$table = JTable::getInstance( 'config', 'Table' );
			$table->load( $config_title );
			$table->config_name = $config_title;
			$table->value = '1';

		if (!$table->save())
		{
			$msg->message = JText::_('Error') . ": " . $table->getError();
		}

		$this->setRedirect( $msg->link, $msg->message, $msg->type );
	}

	/**
	 * Displays the footer
	 * 
	 * @return unknown_type
	 */
	function footer()
	{
		$model	= $this->getModel( 'dashboard' );
		$view	= $this->getView( 'dashboard', 'html' );
		$view->hidemenu = true;
		$view->hidestats = true;
		$view->setModel( $model, true );
		$view->setLayout('footer');
		$view->display();
	}

	/**
	 * Executes a specified task from within a plugin.
	 * Usage: index.php?option=com_scout&task=doTask&element=pluginname&elementTask=pluginfunction
	 *
	 * @return HTML output from plugin
	 */
	function doTask()
	{
		$success = true;
		$msg = new stdClass();
		$msg->message = '';
		$msg->error = '';

		// expects $element in URL and $elementTask
		$element = JRequest::getVar( 'element', '', 'request', 'string' );
		$elementTask = JRequest::getVar( 'elementTask', '', 'request', 'string' );

		$msg->error = '1';
		// $msg->message = "element: $element, elementTask: $elementTask";

		// gets the plugin named $element
		$import 	= JPluginHelper::importPlugin( 'scout', $element );
		$dispatcher	=& JDispatcher::getInstance();
		// executes the event $elementTask for the $element plugin
		// returns the html from the plugin
		// passing the element name allows the plugin to check if it's being called (protects against same-task-name issues)
		$result 	= $dispatcher->trigger( $elementTask, array( $element ) );
		// This should be a concatenated string of all the results,
			// in case there are many plugins with this eventname
			// that return null b/c their filename != element)
		$msg->message = implode( '', $result );
			// $msg->message = @$result['0'];

		echo $msg->message;
		$success = $msg->message;

		return $success;
	}

	/**
	 * Executes a specified task from within a plugin and returns results json_encoded (for ajax implementation).
	 * Usage: index.php?option=com_scout&task=doTaskAjax&element=pluginname&elementTask=pluginfunction
	 *
	 * @return array(msg=>HTML output from plugin)
	 */
	function doTaskAjax()
	{
		JLoader::import( 'com_scout.library.json', JPATH_ADMINISTRATOR.DS.'components' );

		$success = true;
		$msg = new stdClass();
		$msg->message = '';

		// get elements $element and $elementTask in URL
			$element = JRequest::getVar( 'element', '', 'request', 'string' );
			$elementTask = JRequest::getVar( 'elementTask', '', 'request', 'string' );

		// get elements from post
			// $elements = json_decode( preg_replace('/[\n\r]+/', '\n', JRequest::getVar( 'elements', '', 'post', 'string' ) ) );

		// for debugging
			// $msg->message = "element: $element, elementTask: $elementTask";

		// gets the plugin named $element
			$import 	= JPluginHelper::importPlugin( 'scout', $element );
			$dispatcher	=& JDispatcher::getInstance();

		// executes the event $elementTask for the $element plugin
		// returns the html from the plugin
		// passing the element name allows the plugin to check if it's being called (protects against same-task-name issues)
			$result 	= $dispatcher->trigger( $elementTask, array( $element ) );
		// This should be a concatenated string of all the results,
			// in case there are many plugins with this eventname
			// that return null b/c their filename != element)
			$msg->message = implode( '', $result );
			// $msg->message = @$result['0'];

		// set response array
			$response = array();
			$response['msg'] = $msg->message;

		// encode and echo (need to echo to send back to browser)
			echo ( json_encode( $response ) );

		return $success;
	}

	/**
	 * For displaying a searchable list of articles in a lightbox
	 * Usage:
	 */
	function elementArticle()
	{
		$model	= $this->getModel( 'elementarticle' );
		$view	= $this->getView( 'elementarticle' );
		include_once( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_content'.DS.'helper.php' );
		$view->setModel( $model, true );
		$view->display();
	}

	/**
	 * For displaying a searchable list of users in a lightbox
	 * Usage:
	 */
	function elementUser()
	{
		$model 	= $this->getModel( 'elementuser' );
		$view	= $this->getView( 'elementuser' );
		$view->setModel( $model, true );
		$view->display();
	}

	/**
	 * For displaying a searchable list of products in a lightbox
	 * Usage:
	 */
	function elementProduct()
	{
		$model 	= $this->getModel( 'elementproduct' );
		$view	= $this->getView( 'elementproduct' );
		$view->setModel( $model, true );
		$view->display();
	}

	/**
	 * For displaying a searchable list of images in a lightbox
	 * Usage:
	 */
	function elementImage()
	{
		$model 	= $this->getModel( 'elementimage' );
		$view	= $this->getView( 'elementimage' );
		$view->setModel( $model, true );
		$view->display();
	}
}

?>