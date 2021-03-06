<?php
/**
 *  Application_Model_StoryMapper
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

class Application_Model_StoryMapper extends Application_Model_AbstractMapper
{
	protected static $_instance = null;
	
	
	
	protected function __construct()
	{
		
	}
	
	
	public function getDbTable()
	{
		if( null === $this->_dbTable )
			$this->setDbTable( 'Application_Model_Db_Table_Story' );

		return $this->_dbTable;
	}
    
    
    /**
     * 
     * @see Application_Model_AbstractMapper::save()
     */
	public function save( Application_Model_AbstractModel $story )
	{
		if( !$story instanceof Application_Model_Story )
			throw new InvalidArgumentException( "'\$status\' is not an instance of Application_Model_Story !" );
		
		$data = array(	'name'			=> $story->getName(),
						'description'	=> $story->getDescription(),
						'status'		=> $story->getStatus()->getId(),
						'sprint'		=> $story->getSprintId(),
						'feature'		=> $story->getFeatureId(),
						'priority'		=> $story->getPriority(),
						'points'		=> $story->getPoints(),
						'type'			=> $story->getTypeId() );
		if( null === ( $id = $story->getId() ) )
		{
			unset( $data['id'] );
			$this->getDbTable()->insert( $data );
		}
		else
			$this->getDbTable()->update( $data, array( 'id = ?' => $id ) );
	}
	
	
	public function find( $id )
	{
		if( !$id )
			return null;

		if( isset( $this->_loadedMap[$id] ) )
			return $this->_loadedMap[$id];
		
		$selectLastStatus = $this->getDbTable()
								 ->select()
								 ->setIntegrityCheck( false )
								 ->from(	array( 'sta'	=> 'status' ),
											'id' )
								 ->join(	array( 'ss'		=> 'story_status'	),
											'sta.id = ss.status',
											null )
								 ->where( 'ss.story = s.id' )
								 ->order( 'ss.changed DESC' )
								 ->limit( 1 );
		$selectStory = $this->getDbTable()
							->select()
							->setIntegrityCheck( false )
							->from(	array(	's'		=> 'story' ),
									array(	's.*',
											'status' => '(' . $selectLastStatus->__toString() . ')' ) )
							->where( 's.id = ?', $id );
		$row = $this->getDbTable()->fetchRow( $selectStory );
		if( null !== $row )
		{
			$this->_loadedMap[$id] = new Application_Model_Story( $row );
			
			return $this->_loadedMap[$id];
		}
		else
			return null;
	}
	
	
	public function fetchAll( $where = null, $order = null, $count = null, $offset = null )
	{
		$resultSet = $this->getDbTable()->fetchAll( $where, $order, $count, $offset );
		$entries = array();
		foreach( $resultSet as $row )
		{
			$entry = new Application_Model_Story( $row );
			$entries[] = $entry;
		}
		
		return $entries;
	}
	
	
	/**
	 * 
	 * @see Application_Model_AbstractMapper::delete()
	 * @param int | Application_Model_Story $story
	 * @return void
	 */
	public function delete( Application_Model_AbstractModel $story )
	{
		if( !$story instanceof Application_Model_Story || null === ( $id = $story->getId( ) ) )
				throw new Exception( 'Object ID not set' );
		
		unset( $this->_loadedMap[$id] );
		$this->getDbTable()->delete( array( 'id = ?' => $id ) );
	}
	
	
	public static function getInstance()
	{
		if( null === self::$_instance )
			self::$_instance = new Application_Model_StoryMapper();
		
		return self::$_instance;
	}
} 