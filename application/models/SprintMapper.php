<?php
class Application_Model_SprintMapper extends Application_Model_AbstractMapper
{
	public function getDbTable()
	{
		if( null === $this->_dbTable )
		$this->setDbTable('Application_Model_Db_Table_Sprint');

		return $this->_dbTable;
	}


	/**
	 * 
	 * @see Application_Model_AbstractMapper::save()
	 * @param Application_Model_Sprint $sprint
	 */
	public function save( Application_Model_AbstractModel $sprint )
	{
		if( !$sprint instanceof Application_Model_Sprint )
			throw new InvalidArgumentException( "'\$sprint' is not an instance of Application_Model_Sprint !" );

		$data = array(	'name'			=> $sprint->getName(),
						'description'	=> $sprint->getDescription(),
						'status'		=> $sprint->getStatusId(),
						'startDate'		=> $sprint->getStartDate( Zend_Date::ISO_8601),
						'endDate'		=> $sprint->getEndDate( Zend_Date::ISO_8601 ),
						'release'		=> $sprint->getReleaseId() );

		if( null === ( $id = $sprint->getId() ) )
		{
			unset( $data['id'] );
			$this->getDbTable()->insert( $data );
		}
		else
		$this->getDbTable()->update( $data, array( 'id = ?' => $id ) );
	}


	/**
	 * 
	 * @see Application_Model_AbstractMapper::find()
	 * @param int $id
	 */
	public function find( $id )
	{
		if( !$id )
			return null;

		if( isset( $this->_loadedMap[$id] ) )
			return $this->_loadedMap[$id];
		
		$rowset = $this->getDbTable()->find( array( 'id = ?' => $id ) );
		if( 0 === $rowset->count() )
			return null;
			
		$row = $rowset->current();
		$sprint = new Application_Model_Sprint( $row );
		
		$rsStories = $row->findDependentRowset( 'Application_Model_Db_Table_Story' );
		while( $rsStories->next() )
			$sprint->addStory( new Application_Model_Story( $rsStories->current() ) );
		
		$this->_loadedMap[$id] = $sprint;
		
		return $this->_loadedMap[$id];
	}


	public function fetchAll( $where = null, $order = null, $count = null, $offset = null )
	{
		$resultSet = $this->getDbTable()->fetchAll( $where, $order, $count, $offset );
		$entries = array();
		foreach( $resultSet as $row )
		{
			$entry = new Application_Model_Sprint( $row );
			
			$rsStories = $row->findDependentRowset( 'Application_Model_Db_Table_Story' );
			foreach( $rsStories as $rwStory )
				$entry->addStory( new Application_Model_Story( $rwStory ) );
			
			$entries[] = $entry;
		}

		return $entries;
	}
	
	
	/**
	 * 
	 * @see Application_Model_AbstractMapper::delete()
	 * @param int | Application_Model_Sprint $sprint
	 * @return void
	 */
	public function delete( $sprint )
	{
		if( $sprint instanceof Application_Model_Sprint )	
			if( null === ( $id = $sprint->getId( ) ) )
				throw new Exception( 'Object ID not set' );
		else
			$id = $sprint;
		
		unset( $this->_loadedMap[$id] );
		$this->getDbTable()->delete( array( 'id = ?' => $id ) );
	}
}