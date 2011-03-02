<?php
class ErrorController extends Zend_Controller_Action
{


	/*public function errorAction()
	{
		$errors = $this->_getParam( 'error_handler' );
		if( ! $errors )
		{
			$this->view->message = 'You have reached the error page';
			return;
		}
		if( $errors->exception instanceof Zend_Controller_Exception )
		{
			$log = 'notice';
			// 404 error -- controller or action not found
			$this->getResponse()->setHttpResponseCode( 404 );
			$this->view->setTitle( 'Page not found' );
			$this->view->message = $this->view->translate( 'La page que vous demandez n\'a pu être trouvée !' );
		}
		else if( $errors->exception instanceof Zend_Db_Exception )
		{
			$log = 'emerg';
			$this->getResponse()->setHttpResponseCode( 503 );
			$this->view->setTitle( 'Database problem' );
			$this->view->message = $this->view->translate( 'Un problème de base de données nous empêche de servir votre requête.' );
		}
		else
		{
			$log = 'emerg';
			$this->getResponse()->setHttpResponseCode( 503 );
			$this->view->setTitle( 'Erreur de l\'application' );
			$this->view->message = $this->view->translate( 'Notre site est momentanément indisponible.' );
		}

		// Vide le contenu de la réponse
		$this->_response->clearBody();

		//Si mode debug
		if( $this->getInvokeArg( 'debug' ) == 1 )
		{
			// Écrase le message, affiche l'exception complète
			$this->view->message = $errors->exception;
		}

		Zend_Registry::get( 'log' )->$log( $errors->exception );
	}*/
	
	public function errorAction()
	{
		$errors = $this->_getParam('error_handler');

		switch( $errors->type )
		{
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				// 404 error -- controller or action not found
				$this->getResponse()->setHttpResponseCode(404);
				$this->view->message = 'Page not found';
			break;
			
			default:
				// application error
				$this->getResponse()->setHttpResponseCode(500);
				$this->view->message = 'Application error';
			break;
		}

		// Log exception, if logger available
		if( $log = Zend_Registry::get( 'log' ) )
		{
			$exception = $errors->exception;
			//$log->crit($this->view->message, $errors->exception );
			$log->debug( $exception->getMessage()
				. PHP_EOL
				. $exception->getTraceAsString()
				. PHP_EOL );
		}

		// conditionally display exceptions
		if( $this->getInvokeArg('displayExceptions') == true )
			$this->view->exception = $errors->exception;

		$this->view->request = $errors->request;
	}



	/**
	 * Action lors d'un refus des ACLs
	 */
	public function unauthorizedAction()
	{
		$this->_response->setHttpResponseCode( 403 );
		$this->view->setTitle( "Accès refusé" );
	}

}

