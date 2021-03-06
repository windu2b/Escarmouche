<?php
/**
 *  Application_Model_Story
 *  
 *  LICENSE
 *  
 *  Copyright (C) 2011  windu.2b
 *  
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *  
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *  
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *  
 *  @author windu.2b
 *  @license AGPL v3
 *  @since 0.1
 */

class Application_Model_Story extends Application_Model_AbstractModel
{
	/**
	 *
	 * The status of the story.
	 * By default, equals <code>Application_Model_Status::SUGGESTED</code>
	 * @var int
	 */
	protected $_status;
	

	protected $_sprint = null;

	
	protected $_priority = 0;
	
	
	protected $_points = 0;
	
	
	protected $_feature = null;
	
	
	protected $_type = null;
	
	
	/**
	 *
	 * The list of <code>Task</code> that the story has.
	 * Cannot be <code>null</code>. Can be empty.
	 * @var array[Application_Model_Task]
	 */
	protected $_tasks = null;


	public function __construct( $options = array() )
	{
		$this->setStatus( Application_Model_Status::SUGGESTED );
		parent::__construct( $options );
	}


	/**
	 * 
	 * @param int $status the status of the story
	 * @throws InvalidArgumentException if $status is NaN or not a valid status
	 * TODO définir les status autorisés
	 */
	public function setStatus( $status )
	{
		if( !$status instanceof Application_Model_Status && !intval( $status, 10 ) )
			throw new InvalidArgumentException( "\$status' is 'NaN' !" );
		if( !Application_Model_Status::isValid( $status ) )
			throw new InvalidArgumentException( "'\$status' is not a valid status !" );
	
		$this->_status = $status;

		return $this;
	}


	/**
	 * @todo renvoyer un statut dépendant du statut des tasks, si le statut de la story est >= Application_Model_Status::WIP
	 * Enter description here ...
	 */
	public function getStatus()
	{
		if( !$this->_status instanceof Application_Model_Status )
			$this->_status = Application_Model_StatusMapper::getInstance()->find( $this->_status );
		
		return $this->_status;
	}


	public function setSprint( $sprint = null )
	{
		$this->_sprint = $sprint;

		return $this;
	}


	public function getSprint()
	{
		if( is_int( $this->_sprint ) )
		{
			$this->_sprint = Application_Model_SprintMapper::getInstance()->find( $this->_sprint );
		}
		
		return $this->_sprint;
	}
	
	
	/**
	 * Return the id of the sprint, or <code>null</code> if there's no sprint.
	 * @return the id of the sprint, or <code>null</code>.
	 */
	public function getSprintId()
	{
		if( $this->_sprint instanceof Application_Model_Sprint )
			return $this->_sprint->getId();
		else if( is_int( $this->_sprint ) )
			return $this->_sprint;
		else
			return null;
	}


	public function setPriority( $priority )
	{
		$this->_priority = $priority;
		
		return $this;
	}


	public function getPriority()
	{
		return $this->_priority;
	}
	
	
	/**
	 *
	 * @param array[Application_Model_Task] | Application_Model_Task $task the task (or array of tasks) we want to add to this story
	 * @throws InvalidArgumentException if $task is 'null' or empty
	 */
	public function addTask( $task )
	{
		if( $task === null || empty( $task ) )
			throw new InvalidArgumentException( "'\$task' cannot be 'null' or empty !" );

		if( is_array( $task ) )
			foreach( $task as $t )
				$this->_addTask( $t );
		else
			$this->_addTask( $task );

		return $this;
	}

	
	protected function _addTask( Application_Model_Task $task )
	{
		$index = array_search( $task, $this->_tasks );
		if( $index === false )
		{
			$this->_tasks[] = $task;
			$task->addStory( $this );
		}
		
		return $this;
	}
	

	/**
	 *
	 * @param array[Application_Model_Task] | Application_Model_Task $task the task (or array of tasks ) we want to remove to this story
	 * @throws InvalidArgumentException if $task is 'null' or empty
	 */
	public function removeTask( $task )
	{
		if( $task === null || empty( $task ) )
			throw new InvalidArgumentException( "'\$task' cannot be 'null' or empty !" );

		if( !is_array( $task ) )
			foreach( $task as $t )
				$this->_removeTask( $t );
		else
			$this->_removeTask( $task );

		return $this;
	}
	
	
	protected function _removeTask( Application_Model_Task $task )
	{
		$index = array_search( $task, $this->_tasks );
		if( $index !== false )
		{
			unset( $this->_tasks[$index] );
			$task->removeStory( $this );
		}
		
		return $this;
	}

	
	protected function _loadTasks()
	{
		if( null === $this->_tasks )
		{
			$tm = Application_Model_TaskMapper::getInstance();
			$selectLastStatus = $tm->getDbTable()
								   ->select()
								   ->setIntegrityCheck( false )
								   ->from(	array( 'sta'	=> 'status' ),
											'id' )
								   ->join(	array( 'ts'		=> 'task_status'	),
											'sta.id = ts.status',
											null )
								   ->where( 'ts.task = t.id' )
								   ->order( 'ts.changed DESC' )
								   ->limit( 1 );
							
			$selectTasks = $tm->getDbTable()
							  ->select()
							  ->setIntegrityCheck( false )
							  ->from(	array(	't'		=> 'task' ),
										array(	't.*',
												'status' => '(' . $selectLastStatus->__toString() . ')' ) )
							  ->join(	array(	'st'	=> 'story_task' ),
										'st.task_id = t.id',
										null )
							  ->join(	array(	's'		=> 'story' ),
										's.id = st.story_id',
										null )
							  ->where( 's.id = ?', $this->_id );

			$this->_tasks = $tm->fetchAll( $selectTasks  );
		}
	}

	/**
	 *
	 * Enter description here ...
	 * @param int $index
	 * @return Application_Model_Task
	 * @throws InvalidArgumentException if $index is 'NaN'
	 * @throws OutOfRangeException if $index is negative or higher  than or equal to the size of the array
	 */
	public function getTask( $index )
	{
		if( !is_numeric( $index ) )
			throw new InvalidArgumentException( "'\$index' is NaN !" );
		if( $index < 0 || $index >= sizeof( $this->_tasks ) )
			throw new OutOfRangeException( "'\$index' cannot be negative, greater than or equal to the size of the array !" );

		return $this->_tasks[$index];
	}


	/**
	 * 
	 * Enter description here ...
	 * @param int $status
	 * @return array[Application_Model_Task]
	 */
	public function getTasks( $status = null )
	{
		$this->_loadTasks();
		
		if( Application_Model_Status::isValid( $status ) )
			return $this->_filter( $status );
		else
			return $this->_tasks;
	}
	
	
	protected function _filter( $status )
	{
		$tasks = array();
		foreach( $this->_tasks as $t )
			if( Application_Model_Status::equals( $t->getStatus(), $status ) )
				$tasks[] = $t;
		
		return $tasks;
	}
	
	
	public function setFeature( $feature = null )
	{
		if( $feature === 0 )
			$this->_feature = null;
		else
		{
			$this->_feature = $feature;
		}
		
		return $this;
	}
	
	
	protected function _loadFeature()
	{
		if( !$this->_feature instanceof Application_Model_Feature )
			$this->_feature = Application_Model_FeatureMapper::getInstance()->find( $this->_feature );
	}
	
	
	public function getFeature()
	{
		$this->_loadFeature();
		
		return $this->_feature;
	}
	
	
	public function getFeatureId()
	{
		$this->_loadFeature();
		
		if( $this->_feature instanceof Application_Model_Feature )
			return $this->_feature->getId();
		else if( is_int( $this->_feature ) )
			return $this->_feature;
		else
			return null;
	}
	
	
	/**
	 * TODO Gérer le paramétrage de la couleur par défaut, i.e. si pas associée à une feature
	 * Returns the color associated
	 */
	public function getColor()
	{
		$this->_loadFeature();
		
		if( $this->_feature instanceof Application_Model_Feature )
			return $this->_feature->getColor();
		else
			return Zend_Registry::get( 'config' )->color->border->default;
	}
	
	
	/**
	 * 
	 * Enter description here ...
	 * @param int $points
	 */
	public function setPoints( $points )
	{
		$this->_points = ( int ) $points;
			
		return $this;
	}
	
	
	public function getPoints()
	{
		return $this->_points;
	}
	
	
	public function setType( $type )
	{
		$this->_type = ( int ) $type;
	}
	
	
	protected function _getType()
	{
		$this->_type = Application_Model_TypeMapper::getInstance()->find( $this->_type );
	}
	
	public function getType()
	{
		if( is_int( $this->_type ) )
			$this->_getType();
		
		return $this->_type;
	}
	
	
	public function getTypeId()
	{
		if( !isset( $this->_type ) )
			return null;

		if( is_int( $this->_type ) )
			$this->_getType();
			
		return $this->_type->getId();
	}
	
	
	public function toArray()
	{
		$data = parent::toArray();
		
		return array_merge( $data, array(	'status'	=> $this->getStatus(),
											'sprint'	=> $this->getSprintId(),
											'feature'	=> $this->getFeatureId(),
											'priority'	=> $this->getPriority(),
											'type'		=> $this->getTypeId() ) );
	}
}